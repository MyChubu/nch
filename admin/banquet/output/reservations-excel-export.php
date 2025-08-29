<?php

require_once('../../../common/conf.php');

//エクセル出力のライブラリを読み込む
require_once ('../../../phpspreadsheet/vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$week = ['日','月','火','水','木','金','土'];

$ym = $_REQUEST['ym'] ?? date('Y-m');
$mon = $_REQUEST['mon'] ?? 3;
$sts = $_REQUEST['sts'] ?? 'all';

if($sts == 'final'){
  $status_arr = "(1)";
}elseif($sts == 'tentative') {
  $status_arr = "(2)";
}else{
  $status_arr = "(1,2)";
}

$sd = $ym . '-01';
$edate = new DateTime($sd);
switch($mon){
  case 1:
    $ed = $edate->modify('last day of this month')->format('Y-m-d');
    break;
  case 2://翌月の最終日
    $ed = $edate->modify('last day of next month')->format('Y-m-d');
    break;
  case 3: //2ヶ月後の最終日
    $ed = $edate->modify('last day of +2 month')->format('Y-m-d');
    break;
  case 6:
    $ed = $edate->modify('last day of +5 month')->format('Y-m-d');
    break;
  case 12:
    $ed = $edate->modify('last day of +11 month')->format('Y-m-d');
    break;
  default:
    $ed = date('Y-m-t', strtotime($sd . ' +2 month'));
}

$sql ="SELECT
    `reservation_id`,
    `reservation_name`,
    `status`,
    `status_name`,
    `agent_id`,
    `agent_name`,
    `agent_short`,
    `agent_name2`,
    `reserver`,
    `pic`,
    `reservation_date`,
    `due_date`,
    `sales_category_id`,
    MIN(`start`) as `start`, 
    MAX(`end`) as `end`, 
    MAX(`people`) as `people`,
    SUM(`gross`) as `gross`,
    SUM(`net`) as `net`
  FROM `view_daily_subtotal` 
  WHERE 
    `reservation_date` BETWEEN :sd AND :ed
    AND `status` IN " . $status_arr . "
    AND `additional_sales` = 0
  GROUP BY `reservation_id`, `status`
  ORDER BY `reservation_date`,`reservation_id`;";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':sd', $sd, PDO::PARAM_STR);
$stmt->bindValue(':ed', $ed, PDO::PARAM_STR);
$stmt->execute();
$count = $stmt->rowCount();
if($count > 0){
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $reservations = [];
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Reservations');

// ヘッダーの設定
$header = [
  '予約ID',
  '予約名',
  'ステータス',
  'ステータス名',
  'エージェントID',
  'エージェント名',
  'エージェント略称',
  'エージェント名2',
  '予約者',
  'PIC',
  '予約日',
  '期限日',
  '売上カテゴリID',
  '開始日',
  '終了日',
  '人数',
  '売上（税抜）',
  '売上（税込）'
];
$sheet->fromArray($header, NULL, 'A1');

// データの設定
$row = 2;
foreach ($reservations as $reservation) {
  $sheet->setCellValue('A' . $row, $reservation['reservation_id']);
  $sheet->setCellValue('B' . $row, $reservation['reservation_name']);
  $sheet->setCellValue('C' . $row, $reservation['status']);
  $sheet->setCellValue('D' . $row, $reservation['status_name']);
  $sheet->setCellValue('E' . $row, $reservation['agent_id']);
  $sheet->setCellValue('F' . $row, $reservation['agent_name']);
  $sheet->setCellValue('G' . $row, $reservation['agent_short']);
  $sheet->setCellValue('H' . $row, $reservation['agent_name2']);
  $sheet->setCellValue('I' . $row, $reservation['reserver']);
  $sheet->setCellValue('J' . $row, $reservation['pic']);
  $sheet->setCellValue('K' . $row, $reservation['reservation_date']);
  $sheet->setCellValue('L' . $row, $reservation['due_date']);
  $sheet->setCellValue('M' . $row, $reservation['sales_category_id']);
  $sheet->setCellValue('N' . $row, $reservation['start']);
  $sheet->setCellValue('O' . $row, $reservation['end']);
  $sheet->setCellValue('P' . $row, $reservation['people']);
  $sheet->setCellValue('Q' . $row, $reservation['gross']);
  $sheet->setCellValue('R' . $row, $reservation['net']);
  $row++;
}

// Excelファイルの出力
$writer = new Xlsx($spreadsheet);
$filename = 'reservations_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$writer->save('php://output');
exit;
