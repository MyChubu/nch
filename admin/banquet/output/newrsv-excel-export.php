<?php
require_once('../../../common/conf.php');
require_once('../functions/admin_banquet.php');
require_once('../../../phpspreadsheet/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;


$dbh = new PDO(DSN, DB_USER, DB_PASS);
$ym = $_GET['ym'] ?? date('Y-m');
$sd = $ym . '-01';
$ed = (new DateTime($sd))->modify('last day of +0 month')->format('Y-m-d');

$cxl=$_GET['cxl'] ?? 'off'; // キャンセル表示


function rsvOneLetter($s) {
  return match($s) {
    1 => '決', 2 => '仮', 3 => '営', 4 => '待', 5 => 'C', default => '他'
  };
}

function fetchReservations($dbh, $sd, $ed) {
  $sql ="SELECT
    `reservation_id`,
    `reservation_date`,
    `reservation_name`,
    MIN(`status`) AS `status`,
    `sales_category_id`,
    `sales_category_name`,
    `agent_id`,
    `agent_name`,
    `agent_short`,
    `agent_name2`,
    MAX(`people`) AS `people`,
    SUM(`gross`) AS `gross`,
    SUM(`net`) AS `net`,
    `pic_id`,
    `pic`,
    `d_created`,
    `d_decided`,
    `d_tentative`,
    `due_date`,
    `cancel_date`,
    `memo`
  FROM `view_monthly_new_reservation2` WHERE `reservation_date` >= :sd AND `d_created` BETWEEN :sdt AND :edt
  GROUP BY
    `reservation_id`,
    `reservation_date`,
    `reservation_name`,
    `sales_category_id`,
    `sales_category_name`,
    `agent_id`,
    `agent_name`,
    `agent_short`,
    `pic_id`,
    `pic`,
    `d_created`,
    `d_decided`,
    `d_tentative`,
    `due_date`,
    `cancel_date`
  ORDER BY
    `reservation_date`,
    `reservation_id`";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':sd', $sd);
  $stmt->bindValue(':sdt', $sd);
  $stmt->bindValue(':edt', $ed);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// データ取得と分類
$finals = $tentatives = $cancelleds = [];
foreach (fetchReservations($dbh, $sd, $ed) as $rsv) {
  $rsv['orig_status'] = $rsv['status'];
  $rsv['orig_status_name'] = rsvOneLetter($rsv['orig_status']);
  if ($rsv['status'] == 1 && $rsv['d_decided'] > $ed) $rsv['status'] = 2;
  if ($rsv['status'] == 5 && $rsv['cancel_date'] > $ed) $rsv['status'] = 2;
  if ($rsv['status'] == 2 && $rsv['d_decided'] && $rsv['d_decided'] <= $ed) $rsv['status'] = 1;
  $rsv['status_name'] = rsvOneLetter($rsv['status']);

  if($rsv['status'] == 1){
    $finals[] = $rsv;
  }elseif($rsv['status'] == 2){
    $tentatives[] = $rsv;
  }elseif($rsv['status'] == 5 && $rsv['reservation_name'] != '倉庫'){
    $cancelleds[] = $rsv;
  }
}

$spreadsheet = new Spreadsheet();
$spreadsheet->getDefaultStyle()->getFont()->setName('ＭＳ Ｐゴシック')->setSize(10);
$sheet = $spreadsheet->getActiveSheet();
$sheetName = $ym . '実績';
$sheet->setTitle($sheetName);

// 共通ヘッダー
$headers = [
  '実施日','状態','種類','予約名','販売','人数','金額','担当名','予約ID',
  '代理店名','仮期限','予約登録','仮予約日','キャンセル日','決定日','メモ','最終'
];

function writeSection(&$sheet, $title, $data, $headers, $startRow) {
  $sheet->setCellValue("A{$startRow}", $title);
  $startRow++;

  $headerRow = $startRow;
  $sheet->fromArray($headers, null, "A{$startRow}");
  $startRow++;

  $dataStartRow = $startRow;

  foreach ($data as $r) {
    $agentName = ($r['agent_id'] == 2999)
      ? cleanLanternName2($r['agent_name2'], 10)
      : cleanLanternName2($r['agent_name']);

    // ▼ 各セルを個別に指定（setCellValueExplicitを使う）
    $colIndex = 0;
    $fields = [
      'reservation_date', 'status_name', 'sales_category_name', 'reservation_name',
      'agent_name', 'people', 'net', 'pic', 'reservation_id', 'agent_name2',
      'due_date', 'd_created', 'd_tentative', 'cancel_date', 'd_decided',
      'memo', 'orig_status_name'
    ];

    foreach ($fields as $i => $key) {
      $col = chr(ord('A') + $i);
      $val = $r[$key] ?? '';

      // 日付形式にしたい列（A, K〜O）
      $dateCols = ['A', 'K', 'L', 'M', 'N', 'O'];
      if (in_array($col, $dateCols) && !empty($val)) {
        try {
          $dt = new DateTime($val);
          $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dt);
          $sheet->setCellValue($col . $startRow, $excelDate);
          $sheet->getStyle($col . $startRow)
            ->getNumberFormat()
            ->setFormatCode('yyyy/mm/dd');
        } catch (Exception $e) {
          $sheet->setCellValue($col . $startRow, $val);
        }
      }

      // 金額（G列 = col G = index 6）
      elseif ($col === 'G') {
        $sheet->setCellValue($col . $startRow, intval($val));
        $sheet->getStyle($col . $startRow)
          ->getNumberFormat()
          ->setFormatCode('#,##0');
      }

      // メモ（P列 = col P = index 15）
      elseif ($col === 'P') {
        $sheet->setCellValue($col . $startRow, $val);
        $sheet->getStyle($col . $startRow)
          ->getAlignment()
          ->setWrapText(true)
          ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
          ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
      }

      // 通常の文字列列
      else {
        $sheet->setCellValueExplicit($col . $startRow, $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      }
    }

    $startRow++;
  }

  $endRow = $startRow - 1;

  // ▼ 罫線
  $range = "A{$headerRow}:Q{$endRow}";
  $sheet->getStyle($range)->applyFromArray([
    'borders' => [
      'allBorders' => [
        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        'color' => ['rgb' => '000000'],
      ],
    ],
    'alignment' => [ // 上下中央揃え
      'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    ],
  ]);

  // ▼ ヘッダー行（背景・白文字・中央揃え）
  $sheet->getStyle("A{$headerRow}:Q{$headerRow}")->applyFromArray([
    'fill' => [
      'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
      'startColor' => ['rgb' => '4F4F4F'],
    ],
    'font' => [
      'color' => ['rgb' => 'FFFFFF'],
      'bold' => true,
    ],
    'alignment' => [
      'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
      'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    ],
  ]);

  return $startRow + 1;
}



$row = 1;
$row = writeSection($sheet, '決定予約', $finals, $headers, $row);
$row = writeSection($sheet, '仮予約', $tentatives, $headers, $row);
if($cxl === 'on'){
  $row = writeSection($sheet, 'キャンセル', $cancelleds, $headers, $row);
}

foreach (['B','C','F','H','Q'] as $col) {
  $sheet->getStyle($col . '1:' . $col . $sheet->getHighestRow())
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);
}

// 列幅調整
// 列幅を固定で調整
$sheet->getColumnDimension('A')->setWidth(12); // 実施日
$sheet->getColumnDimension('B')->setWidth(12);  // 状態
$sheet->getColumnDimension('C')->setWidth(12); // 種類
$sheet->getColumnDimension('D')->setWidth(45); // 予約名
$sheet->getColumnDimension('E')->setWidth(12); // 販売
$sheet->getColumnDimension('F')->setWidth(5);  // 人数
$sheet->getColumnDimension('G')->setWidth(12); // 金額
$sheet->getColumnDimension('H')->setWidth(12); // 担当名
$sheet->getColumnDimension('I')->setWidth(10); // 予約ID
$sheet->getColumnDimension('J')->setWidth(40); // 代理店名
$sheet->getColumnDimension('K')->setWidth(12); // 仮期限
$sheet->getColumnDimension('L')->setWidth(12); // 予約登録
$sheet->getColumnDimension('M')->setWidth(12); // 仮予約日
$sheet->getColumnDimension('N')->setWidth(12); // キャンセル日
$sheet->getColumnDimension('O')->setWidth(12); // 決定日
$sheet->getColumnDimension('P')->setWidth(45); // メモ（広め）
$sheet->getColumnDimension('Q')->setWidth(8);  // 最終

$sheet->getStyle("F2:F" . $sheet->getHighestRow())
  ->getNumberFormat()
  ->setFormatCode('#,##0');

// I列～O列をグループ化して非表示
$sheet->getColumnDimension('I')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
$sheet->getColumnDimension('J')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
$sheet->getColumnDimension('K')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
$sheet->getColumnDimension('L')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
$sheet->getColumnDimension('M')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
$sheet->getColumnDimension('N')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
$sheet->getColumnDimension('O')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
$sheet->getColumnDimension('Q')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
// アウトラインを有効にする
$sheet->setShowSummaryRight(true); // グループボタンを右側に表示（任意）


// ダウンロード出力
$filename = "新規獲得リスト_{$ym}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
