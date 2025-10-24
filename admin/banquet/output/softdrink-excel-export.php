<?php
// 開発中断。必要があれば再開する。
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
$today = date('Y-m-d');
$d1 = $_GET['d1'] ? (new DateTime($_GET['d1']))->format('Y-m-d') : null;
$d2 = $_GET['d2'] ? (new DateTime($_GET['d2']))->format('Y-m-d') : null;
if( !$d1 ){
  if( $d2 ){
    $d1 = $d2;
    $date1 = $d1;
    $date2 = $d1;
    $update_msg = '（'.$date1.'）';
    $d2 = '';
  }else{
    $date1 = $today;
    $date2 = $today;
    $update_msg = '（'.$date1.'）';
    $d1 = $today;
  }
}else{
  if( !$d2 ){
    $date1 = $d1;
    $date2 = $d1;
    $update_msg = '（'.$date1.'）';
  }else{
    if( $d1 == $d2 ){
      $date1 = $d1;
      $date2 = $d1;
      $update_msg = '（'.$date1.'）';
      $d2='';
    }else{
      if( $d1 > $d2 ){
        $tmp = $d1;
        $d1 = $d2;
        $d2 = $tmp; 
      } 
      $date1 = $d1;
      $date2 = $d2;
      $update_msg = '（'.$date1.'～'.$date2.'）';
    }
  }
}

function rsvOneLetter($s) {
  return match($s) {
    1 => '決', 2 => '仮', 3 => '営', 4 => '待', 5 => 'C', default => '他'
  };
}

function fetchDrinkOrders($dbh, $d1, $d2) {
  $sql='SELECT * FROM `view_softdrink_order`
  WHERE `date` BETWEEN :date1 AND :date2
  ORDER BY `date` ASC,`item_gene_id` ASC, `reservation_id` ASC';
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':date1', $d1);
    $stmt->bindValue(':date2', $d2);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// データ取得と分類
$teas = $wtrs = [];
$tea_total = $tea_net_total = $tea_gross_total = 0;
$wtr_total = $wtr_net_total = $wtr_gross_total = 0;
$water_types = ['B02-0007'];
foreach (fetchDrinkOrders($dbh, $d1, $d2) as $order) {
  if (in_array($order['item_gene_id'], $water_types)) {
    $wtrs[] = $order;
    $wtr_total += $order['qty'];
    $wtr_net_total += $order['net'];
    $wtr_gross_total += $order['gross'];
  } else {
    $teas[] = $order;
    $tea_total += $order['qty'];
    $tea_net_total += $order['net'];  
    $tea_gross_total += $order['gross'];
  }
}

$spreadsheet = new Spreadsheet();
$spreadsheet->getDefaultStyle()->getFont()->setName('ＭＳ Ｐゴシック')->setSize(10);
$sheet = $spreadsheet->getActiveSheet();
$sheetName = 'ペット茶・水'.$update_msg;
$sheet->setTitle($sheetName);

// 共通ヘッダー
$headers = [
  '実施日','状態','部門','予約ID','予約名','会場','種類',
  '単価','数量','GROSS','NET','担当者','代理店','最終'
];

function writeSection(&$sheet, $title, $data, $headers, $startRow) {
  // タイトル
  $sheet->setCellValue("A{$startRow}", $title);
  $startRow++;

  // ヘッダー
  $headerRow = $startRow;
  $sheet->fromArray($headers, null, "A{$startRow}");
  $startRow++;

  $dataStartRow = $startRow;

  // データ行
  foreach ($data as $r) {
    $agentName="";
    if($r['agent_id'] > 0){
      if(!empty($r['agent_name2'])){
        $agentName = $r['agent_name2'];
      }else{
        $agentName = $r['agent_name'];
      }
    }


    // 1行ずつ型を意識して書き込み
    $sheet->setCellValue("A{$startRow}", !empty($r['reservation_date']) ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($r['reservation_date'])) : null);
    $sheet->getStyle("A{$startRow}")->getNumberFormat()->setFormatCode('yyyy/mm/dd');

    $sheet->setCellValueExplicit("B{$startRow}", rsvOneLetter($r['status']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("C{$startRow}", $r['sales_category_name'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("D{$startRow}", cleanLanternName2($r['reservation_name'],30) ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("E{$startRow}", cleanLanternName2($agentName,20) ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

    // F: 人数（整数）
    $sheet->setCellValue("F{$startRow}", is_numeric($r['people'] ?? null) ? (int)$r['people'] : null);
    $sheet->getStyle("F{$startRow}")->getNumberFormat()->setFormatCode('#,##0');

    // G: 金額（整数）
    $sheet->setCellValue("G{$startRow}", is_numeric($r['net'] ?? null) ? (int)$r['net'] : null);
    $sheet->getStyle("G{$startRow}")->getNumberFormat()->setFormatCode('#,##0');

    $sheet->setCellValueExplicit("H{$startRow}", cleanLanternName($r['pic']) ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue("I{$startRow}", $r['reservation_id'] ?? '');

    $sheet->setCellValueExplicit("J{$startRow}", cleanLanternName2($r['agent_name'],30) ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

    // K～O: 日付列（ある場合だけ書式設定）
    foreach (['K'=>'due_date','L'=>'d_created','M'=>'d_tentative','N'=>'cancel_date','O'=>'d_decided'] as $col => $key) {
      if (!empty($r[$key])) {
        $sheet->setCellValue("{$col}{$startRow}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($r[$key])));
        $sheet->getStyle("{$col}{$startRow}")->getNumberFormat()->setFormatCode('yyyy/mm/dd');
      }
    }

    // P: メモ 折り返し・左寄せ
    $sheet->setCellValue("P{$startRow}", $r['memo'] ?? '');
    $sheet->getStyle("P{$startRow}")->getAlignment()
          ->setWrapText(true)
          ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
          ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    // Q: 最終（元ステータス1文字）
    $sheet->setCellValueExplicit("Q{$startRow}", $r['orig_status_name'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

    $startRow++;
  }

  $endRow = $startRow - 1;

  // 合計行（データがある場合）
  $totalRow = $startRow;
  if ($endRow >= $dataStartRow) {
    // D列：件数 ＝ 行数を「○件」で
    $sheet->setCellValue("D{$totalRow}", "=ROWS(A{$dataStartRow}:A{$endRow})&\"件\"");

    // F列：人数合計（SUM、整数）
    $sheet->setCellValue("F{$totalRow}", "=SUM(F{$dataStartRow}:F{$endRow})");
    $sheet->getStyle("F{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');

    // G列：金額合計（SUM、整数）
    $sheet->setCellValue("G{$totalRow}", "=SUM(G{$dataStartRow}:G{$endRow})");
    $sheet->getStyle("G{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');

    // 合計行スタイル（太字・縦中央）
    $sheet->getStyle("A{$totalRow}:Q{$totalRow}")->applyFromArray([
      'font' => ['bold' => true],
      'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
      'borders' => [
        'allBorders' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
          'color' => ['rgb' => '000000'],
        ],
      ],
    ]);

    $startRow++; // 合計行の次へ
  } else {
    // データが無ければ合計行は作らず、そのまま
  }

  // ▼ テーブル罫線：ヘッダー～合計（合計があれば含める）
  $tableEnd = ($endRow >= $dataStartRow) ? $totalRow : $headerRow;
  $sheet->getStyle("A{$headerRow}:Q{$tableEnd}")->applyFromArray([
    'borders' => [
      'allBorders' => [
        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        'color' => ['rgb' => '000000'],
      ],
    ],
    'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
  ]);

  // ▼ ヘッダー行（濃グレー・白文字・中央）
  $sheet->getStyle("A{$headerRow}:Q{$headerRow}")->applyFromArray([
    'fill' => [
      'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
      'startColor' => ['rgb' => '4F4F4F'],
    ],
    'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
    'alignment' => [
      'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
      'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    ],
  ]);

  // ▼ B,C,F,H,Q 列のセンタリング（セクション範囲に限定）
  foreach (['B','C','F','H','Q'] as $col) {
    $sheet->getStyle($col.$headerRow . ':' . $col.$tableEnd)
          ->getAlignment()
          ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
          ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
  }

  return $startRow + 1; // 1行空けて次のセクションへ
}

$sheet->setCellValue("A1", $year_month);
$sheet->getStyle("A1")->getFont()->setBold(true)->setSize(12);
$row = 2;
$row = writeSection($sheet, '仮予約', $tentatives, $headers, $row);
$row = writeSection($sheet, '決定予約', $finals, $headers, $row);

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
$sheet->getColumnDimension('E')->setWidth(20); // 代理店名
$sheet->getColumnDimension('F')->setWidth(6.5);  // 人数
$sheet->getColumnDimension('G')->setWidth(12); // 金額
$sheet->getColumnDimension('H')->setWidth(12); // 担当名
$sheet->getColumnDimension('I')->setWidth(10); // 予約ID
$sheet->getColumnDimension('J')->setWidth(12); // 代理店
$sheet->getColumnDimension('K')->setWidth(12); // 仮期限
$sheet->getColumnDimension('L')->setWidth(12); // 予約登録
$sheet->getColumnDimension('M')->setWidth(12); // 仮予約日
$sheet->getColumnDimension('N')->setWidth(12); // キャンセル日
$sheet->getColumnDimension('O')->setWidth(12); // 決定日
$sheet->getColumnDimension('P')->setWidth(45); // メモ（広め）
$sheet->getColumnDimension('Q')->setWidth(8);  // 最終

// D,E,J列をセルに合わせて縮小
foreach (['D','E','J'] as $col) {
  $sheet->getStyle($col . '1:' . $col . $sheet->getHighestRow())
    ->getAlignment()
    ->setShrinkToFit(true)
    ->setWrapText(false); // 念のため折り返しは無効化（併用不可）
}

$sheet->getStyle("F2:F" . $sheet->getHighestRow())
  ->getNumberFormat()
  ->setFormatCode('#,##0');

// I列～O列をグループ化して非表示
// $sheet->getColumnDimension('I')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
// $sheet->getColumnDimension('J')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
// $sheet->getColumnDimension('K')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
// $sheet->getColumnDimension('L')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
// $sheet->getColumnDimension('M')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
// $sheet->getColumnDimension('N')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
// $sheet->getColumnDimension('O')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
// $sheet->getColumnDimension('Q')->setOutlineLevel(1)->setVisible(false)->setCollapsed(true);
// アウトラインを有効にする
$sheet->setShowSummaryRight(true); // グループボタンを右側に表示（任意）

// ▼ 印刷設定：A4縦・全体を1ページに収める
$sheet->getPageSetup()
    ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)  // 縦
    ->setPaperSize(PageSetup::PAPERSIZE_A4)            // A4
    ->setFitToWidth(1)                                 // 横を1ページ
    ->setFitToHeight(1);                               // 縦も1ページに収める

// ▼ 余白を狭く（単位はインチ）
$sheet->getPageMargins()
    ->setTop(0.3)
    ->setBottom(0.3)
    ->setLeft(0.3)
    ->setRight(0.3);

// ダウンロード出力
$filename = "ペット茶・水.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
