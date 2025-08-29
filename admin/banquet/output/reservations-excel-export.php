<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

require_once('../../../common/conf.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);

// PhpSpreadsheet 読み込み
require_once ('../../../phpspreadsheet/vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet; // ← 追加
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$ym  = $_REQUEST['ym']  ?? date('Y-m');
$mon = (int)($_REQUEST['mon'] ?? 3);
$sts = $_REQUEST['sts'] ?? 'all';

// 抽出するステータス
if ($sts === 'final') {
  $status_arr = "(1)";
} elseif ($sts === 'tentative') {
  $status_arr = "(2)";
} else {
  $status_arr = "(1,2)";
}

// 期間算出（開始：当月1日、終了：monに応じた最終日）
$sd = $ym . '-01';
$edate = new DateTime($sd);
switch ($mon) {
  case 1:  $ed = $edate->modify('last day of this month')->format('Y-m-d'); break;
  case 2:  $ed = $edate->modify('last day of next month')->format('Y-m-d'); break;
  case 3:  $ed = $edate->modify('last day of +2 month')->format('Y-m-d'); break;
  case 6:  $ed = $edate->modify('last day of +5 month')->format('Y-m-d'); break;
  case 12: $ed = $edate->modify('last day of +11 month')->format('Y-m-d'); break;
  default: $ed = (new DateTime($sd))->modify('last day of +2 month')->format('Y-m-d');
}

// 集計（予約ID×ステータスでユニークになる想定。非集約列は ANY_VALUE/MIN/MAX）
$sql = "
SELECT
  reservation_id,
  ANY_VALUE(reservation_name)   AS reservation_name,
  status,
  ANY_VALUE(status_name)        AS status_name,
  ANY_VALUE(agent_id)           AS agent_id,
  ANY_VALUE(agent_name)         AS agent_name,
  ANY_VALUE(agent_short)        AS agent_short,
  ANY_VALUE(agent_name2)        AS agent_name2,
  ANY_VALUE(reserver)           AS reserver,
  ANY_VALUE(pic)                AS pic,
  MIN(reservation_date)         AS reservation_date,
  MIN(due_date)                 AS due_date,
  ANY_VALUE(sales_category_id)  AS sales_category_id,
  MIN(`start`)                  AS `start`,
  MAX(`end`)                    AS `end`,
  MAX(people)                   AS people,
  SUM(gross)                    AS gross,
  SUM(net)                      AS net
FROM view_daily_subtotal
WHERE reservation_date BETWEEN :sd AND :ed
  AND status IN {$status_arr}
  AND additional_sales = 0
GROUP BY reservation_id, status
ORDER BY reservation_date, reservation_id;
";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':sd', $sd, PDO::PARAM_STR);
$stmt->bindValue(':ed', $ed, PDO::PARAM_STR);
$stmt->execute();

// rowCount() は SELECT では信用できないため、直接 fetchAll
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Excel 生成 =====
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Reservations');

// ヘッダ
$header = [
  '予約日','予約ID','予約名','ステータス','ステータス名','エージェント名',
  'エージェント名2','予約者','PIC','売上カテゴリID',
  '人数','売上（税抜）','売上（税込）','期限日'
];
$sheet->fromArray($header, null, 'A1');

// 見栄え（ヘッダ太字・固定・オートフィットは最後に）
$sheet->getStyle('A1:R1')->getFont()->setBold(true);
$sheet->freezePane('A2');

$row = 2;
if (!empty($reservations)) {
  foreach ($reservations as $r) {
    // 日付/日時は Excel シリアルに変換しておく
    $kDate = $r['reservation_date'] ? ExcelDate::stringToExcel($r['reservation_date']) : null;
    $lDate = $r['due_date']         ? ExcelDate::stringToExcel($r['due_date'])         : null;
    $nDate = $r['start']            ? ExcelDate::stringToExcel($r['start'])            : null;
    $oDate = $r['end']              ? ExcelDate::stringToExcel($r['end'])              : null;

    if ($kDate !== null) { $sheet->setCellValue('A'.$row, $kDate); }
    $sheet->getStyle("A{$row}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
    $sheet->setCellValue('B'.$row, $r['reservation_id']);
    $sheet->setCellValue('C'.$row, $r['reservation_name']);
    $sheet->setCellValue('D'.$row, $r['status']);
    $sheet->setCellValue('E'.$row, $r['status_name']);
    $sheet->setCellValue('F'.$row, $r['agent_short']);
    $sheet->setCellValue('G'.$row, $r['agent_name2']);
    $sheet->setCellValue('H'.$row, $r['reserver']);
    $sheet->setCellValue('I'.$row, $r['pic']);

    // 期限日（yyyy-mm-dd）
    
    

    $sheet->setCellValue('J'.$row, $r['sales_category_id']);


    // 人数（整数）
    $sheet->setCellValue('K'.$row, is_null($r['people']) ? null : (int)$r['people']);
    $sheet->getStyle("K{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

    // 金額（カンマ区切り。通貨記号は環境依存なので数値書式のみ）
    $sheet->setCellValue('L'.$row, is_null($r['gross']) ? null : (float)$r['gross']);
    $sheet->setCellValue('M'.$row, is_null($r['net'])   ? null : (float)$r['net']);
    $sheet->getStyle("L{$row}:M{$row}")->getNumberFormat()->setFormatCode('#,##0');
    if ($lDate !== null) { $sheet->setCellValue('N'.$row, $lDate); }
    $sheet->getStyle("N{$row}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
    $row++;
  }
} else {
  $sheet->setCellValue('B'.$row, '該当データなし');
  $row++;
}

// 列幅オートフィット
foreach (range('A', 'N') as $col) {
  $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ダウンロード出力（余分な出力を殺してからヘッダ送出）
$filename = 'reservations_' . date('YmdHis') . '.xlsx';
if (ob_get_length()) { ob_end_clean(); } // ← 余分な空白で壊れるのを防止
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>