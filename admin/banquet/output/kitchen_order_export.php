<?php

require_once('../../../common/conf.php');

//エクセル出力のライブラリを読み込む
require_once ('../../../phpspreadsheet/vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$week = ['日','月','火','水','木','金','土'];
$ex_rate = 1.21; //　税・サービス料（1.1の2乗）
$ex_pice = 160; // コロナ対策費

$mdate = new DateTime('+7 days');
$mdate->modify('next monday');

if (isset($_REQUEST['startdate']) && $_REQUEST['startdate'] != '') {
  $s_date = new DateTime($_REQUEST['startdate']);
} else {
  $s_date = $mdate;
}

$start_date = $s_date->format('Y-m-d');
$endDateObj = clone $s_date;
$endDateObj->add(new DateInterval('P6D'));// 6日後を追加
$end_date = $endDateObj->format('Y-m-d');

$results = array();
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = 'SELECT * FROM banquet_schedules WHERE (date BETWEEN ? AND ?) AND status IN (1,2,3) ORDER BY start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$dcount = $stmt->rowCount();

if ($dcount > 0) {
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $date = $row['date'];
    $dateObj = new DateTime($date);
    $w = (int)$dateObj->format('w');

    $reservation_id = $row['reservation_id'];
    $branch = $row['branch'];
    $status = $row['status'];
    $reservation_name = $row['reservation_name'];
    $pic = mb_convert_kana($row['pic'], "KVas");
    $pic = explode(' ', $pic);
    $event_name = str_replace('///', ' ', $row['event_name']);
    $start = $row['start'];
    $end = $row['end'];
    $people = $row['people'];
    $room_id = $row['room_id'];

    // room 会場
    $stmt2 = $dbh->prepare('SELECT * FROM banquet_rooms WHERE banquet_room_id = ?');
    $stmt2->execute([$room_id]);
    $room_name = $stmt2->fetch(PDO::FETCH_ASSOC)['name'];

    // purpose 目的
    $purpose_id = $row['purpose_id'];
    $stmt3 = $dbh->prepare('SELECT * FROM banquet_purposes WHERE banquet_purpose_id = ?');
    $stmt3->execute([$purpose_id]);
    $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    $purpose_name = $row3['banquet_purpose_name'];
    $purpose_short = $row3['banquet_purpose_short'];
    $banquet_category_id = $row3['banquet_category_id'];
    $summary_category = $row3['summary_category'];

    // category カテゴリー
    $stmt4 = $dbh->prepare('SELECT * FROM banquet_categories WHERE banquet_category_id = ?');
    $stmt4->execute([$banquet_category_id]);
    $category_name = $stmt4->fetch(PDO::FETCH_ASSOC)['banquet_category_name'];

    // 料理
    $meal = [];
    // 朝食バイキング
    if ($purpose_id == 35 && $reservation_name != '朝食会場') {
      $meal[] = [
        'name' => '朝食バイキング',
        'short_name' => '朝バ',
        'unit_price' => 1100,
        'net_unit_price' => 1000,
        'qty' => $people,
        'amount_gross' => 1100 * $people,
        'item_group_id' => '',
        'item_id' => '',
        'item_gene_id' => '',
      ];
    }

    // パッケージ料理
    $stmt5 = $dbh->prepare('SELECT * FROM view_package_charges WHERE reservation_id = ? AND branch = ?');
    $stmt5->execute([$reservation_id, $branch]);
    foreach ($stmt5->fetchAll(PDO::FETCH_ASSOC) as $row5) {
      $unit_price = (int)$row5['UnitP'];
      $meal[] = [
        'name' => mb_convert_kana($row5['NameShort'], "KVas"),
        'short_name' => mb_convert_kana($row5['NameShort'], "KVas"),
        'unit_price' => $unit_price,
        'net_unit_price' => ($unit_price - $ex_pice) / $ex_rate,
        'qty' => (int)$row5['Qty'],
        'amount_gross' => (int)$row5['Gross'],
        'item_group_id' => $row5['package_category'],
        'item_id' => $row5['package_id'],
        'item_gene_id' => $row5['banquet_pack_id'],
      ];
    }

    // 単品料理など
    $stmt6 = $dbh->prepare('SELECT * FROM view_charges WHERE reservation_id = ? AND branch = ? AND meal = 1 AND (package_id IS NULL OR package_id = "" OR package_id = " ") AND item_group_id LIKE "F%"');
    $stmt6->execute([$reservation_id, $branch]);
    foreach ($stmt6->fetchAll(PDO::FETCH_ASSOC) as $row6) {
      $item_gene_id = $row6['item_gene_id'];
      $unit_price = $row6['unit_price'];
      if ($item_gene_id == 'F17-0001') {
        $net_unit_price = $unit_price / $ex_rate;
      } elseif ($item_gene_id == 'F03-0022') {
        $net_unit_price = ($unit_price - $ex_pice) / $ex_rate;
      } else {
        $net_unit_price = $unit_price / $ex_rate;
      }
      $meal[] = [
        'name' => mb_convert_kana($row6['item_name'], "KVas"),
        'short_name' => mb_convert_kana($row6['name_short'], "KVas"),
        'unit_price' => $unit_price,
        'net_unit_price' => $net_unit_price,
        'qty' => $row6['qty'],
        'amount_gross' => $row6['amount_gross'],
        'item_group_id' => $row6['item_group_id'],
        'item_id' => $row6['item_id'],
        'item_gene_id' => $item_gene_id,
      ];
    }
    // 料理がない場合はスキップ
    if (count($meal) > 0) {
      $results[] = [
        'date' => $date,
        'w' => $week[$w],
        'reservation_id' => $reservation_id,
        'branch' => $branch,
        'status' => $status,
        'reservation_name' => $reservation_name,
        'event_name' => $event_name,
        'pic' => $pic[0],
        'people' => $people,
        'start' => $start,
        'end' => $end,
        'room_id' => $room_id,
        'room_name' => $room_name,
        'purpose_id' => $purpose_id,
        'purpose_name' => $purpose_name,
        'purpose_short' => $purpose_short,
        'banquet_category_id' => $banquet_category_id,
        'summary_category' => $summary_category,
        'category_name' => $category_name,
        'meal' => $meal,
      ];
    }
  }

  //　excelテンプレートを読み込む
  $templatePath = './templates/kitchen-order-tmp.xlsx';
  if (!file_exists($templatePath)) {
    die('テンプレートファイルが見つかりません');
  }

  $objSpreadsheet = IOFactory::load($templatePath);

  for ($i = 0; $i < 7; $i++) { // 7日分のシートを作成します
    // 日付の計算
    $sheetDate = clone $s_date;
    $sheetDate->add(new DateInterval("P{$i}D")); 
    $date = $sheetDate->format('Y-m-d');
    $w = (int)$sheetDate->format('w');
    $sheetTitle = $sheetDate->format('Y年m月d日') . ' (' . $week[$w] . ')';

    $baseSheet = $objSpreadsheet->getSheetByName('template');
    if (!$baseSheet) {
      die('テンプレートシートが見つかりません');
    }

    //シートの追加・記入
    $newSheet = clone $baseSheet; // テンプレートシートをコピー
    $newSheet->setTitle($sheetTitle); // シート名を設定
    $objSpreadsheet->addSheet($newSheet); //コピーしたシートを追加
    // アクティブシートを新しいシートに設定
    $objSpreadsheet->setActiveSheetIndex($objSpreadsheet->getIndex($newSheet));
    $sheet = $objSpreadsheet->getActiveSheet();
    $sheet->setCellValue('B1', $sheetTitle); // シートのタイトルを設定

    $count = 0;
    foreach ($results as $result) {
      if ($result['date'] == $date) {
        $count++;
      }
    }

    if ($count == 0) {
      $sheet->setCellValue('B3', '予約はありません。');
      continue;
    }

    $rowIndex = 3; // データの書き込み開始行（3行目から）
    foreach ($results as $result) { // 各予約のデータをループ
      if ($result['date'] == $date) {
        $sheet->setCellValue("A$rowIndex", $result['category_name']);
        $sheet->setCellValue("B$rowIndex", $result['event_name']);
        $sheet->setCellValue("C$rowIndex", $result['pic']);
        $sheet->setCellValue("D$rowIndex", $result['room_name']);
        $timeObj = DateTime::createFromFormat('Y-m-d H:i:s', $result['start']);
        $sheet->setCellValue("E$rowIndex", $timeObj ? $timeObj->format('H:i') : '');

        $meal_name = '';
        $meal_qty = '';
        $meal_net_unit_price = '';
        foreach ($result['meal'] as $index => $meal) {
          $sep = $index > 0 ? "\n" : '';
          $meal_name .= $sep . $meal['name'];
          $meal_qty .= $sep . $meal['qty'];
          $meal_net_unit_price .= $sep . number_format($meal['net_unit_price']);
        }

        $sheet->setCellValue("F$rowIndex", $meal_name)->getStyle("F$rowIndex")->getAlignment()->setWrapText(true);
        $sheet->setCellValue("G$rowIndex", $meal_net_unit_price)->getStyle("G$rowIndex")->getAlignment()->setWrapText(true);
        $sheet->setCellValue("H$rowIndex", $meal_qty)->getStyle("H$rowIndex")->getAlignment()->setWrapText(true);

        // 備考欄
        $memo = '';
        if ($result['status'] == 2) $memo .= "仮予約\n";
        elseif ($result['status'] == 3) $memo .= "営業押さえ\n";
        if ($result['banquet_category_id'] == 1) $memo .= "会議で問題ないでしょうか？\n";
        if ($memo) {
          $sheet->setCellValue("J$rowIndex", $memo);
          $sheet->getStyle("J$rowIndex")->getAlignment()->setWrapText(true);
        }

        $rowIndex++;
      }
    }
  }

  $templateIndex = $objSpreadsheet->getIndex($objSpreadsheet->getSheetByName('template'));
  $objSpreadsheet->removeSheetByIndex($templateIndex);

  
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment;filename="kitchenOrder-' . $start_date . 'の週_' . date("YmdHis") . '.xlsx"');
  header('Cache-Control: max-age=0');

  $writer = new Xlsx($objSpreadsheet);
  $writer->save('php://output');
  exit;

} else {
  echo '指定された期間にデータがありません。';
}
?>
