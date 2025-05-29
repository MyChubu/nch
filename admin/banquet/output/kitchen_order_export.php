<?php

require_once('../../../common/conf.php');
require_once ('../../../phpspreadsheet/vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$week = ['日','月','火','水','木','金','土'];
$ex_rate = 1.21; // 税率
$ex_pice = 160; //コロナ対策費

$mdate = new DateTime('+7 days');
$mdate->modify('next monday'); // 必ず14日より後の月曜になる

if( isset($_REQUEST['startdate']) && $_REQUEST['startdate'] != '') {
  $s_date = new DateTime($_REQUEST['startdate']);
} else {
  $s_date = $mdate;
}

$start_date = $s_date->format('Y-m-d');
$end_date = date('Y-m-d', strtotime($start_date . ' + 6 days'));


$results = array();
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = 'select * from banquet_schedules where (date BETWEEN ? AND ?) AND status IN( 1,2,3)  order by start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$dcount = $stmt->rowCount();

if($dcount > 0){
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $date = $row['date'];
    $w = date('w', strtotime($date));
    $reservation_id = $row['reservation_id'];
    $branch = $row['branch'];
    $status = $row['status'];
    $reservation_name = $row['reservation_name'];
    $pic = mb_convert_kana($row['pic'], "KVas");
    $pic= explode(' ', $pic);
    $event_name = $row['event_name'];
    $start = $row['start'];
    $end = $row['end'];
    $people = $row['people'];
    $room_id = $row['room_id'];
    $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
    $stmt2 = $dbh->prepare($sql2);
    $stmt2->execute([$room_id]);
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    $room_name = $row2['name'];
    $purpose_id = $row['purpose_id'];
    $sql3 = 'select * from banquet_purposes where banquet_purpose_id = ?';
    $stmt3 = $dbh->prepare($sql3);
    $stmt3->execute([$purpose_id]);
    $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    $purpose_name = $row3['banquet_purpose_name'];
    $purpose_short = $row3['banquet_purpose_short'];
    $banquet_category_id = $row3['banquet_category_id'];
    $summary_category = $row3['summary_category'];
    $sql4 = 'select * from banquet_categories where banquet_category_id = ?';
    $stmt4 = $dbh->prepare($sql4);
    $stmt4->execute([$banquet_category_id]);
    $row4 = $stmt4->fetch();
    $category_name = $row4['banquet_category_name'];
    $meal = array();

    
    if($purpose_id == 35 && $reservation_name != '朝食会場'){
      $meal[] =array(
        'name' => '朝食バイキング',
        'short_name' => '朝バ',
        'unit_price' => 1100,
        'net_unit_price' => 1000,
        'qty' => $row['people'],
        'amount_gross' => 1100 * $row['people'],
        'item_group_id' => '',
        'item_id' => '',
        'item_gene_id' => '',
      );
    }

    //パッケージ料理
    $sql5 = 'select * from `view_package_charges` where `reservation_id` = ? AND `branch`= ? ';
    $stmt5 = $dbh->prepare($sql5);
    $stmt5->execute([$reservation_id, $branch]);
    $mcount = $stmt5->rowCount();
    if ($mcount > 0) {
      $rows5 = $stmt5->fetchAll(PDO::FETCH_ASSOC);
      foreach ($rows5 as $row5) {
        $package_name = mb_convert_kana($row5['NameShort'], "KVas");
        $unit_price = intval($row5['UnitP']);
        $net_unit_price = ($unit_price - $ex_pice) / $ex_rate ;
        $qty = intval($row5['Qty']);
        $amount_gross = intval($row5['Gross']);
        $amount_net = $row5['Net'];
        $service_fee = $row5['ServiceFee'];
        $tax = $row5['Tax'];
        $discount_name = '';
        $discount_rate = 0;
        $discount_amount = $row5['Discount'];
        $item_group_id = $row5['package_category'];
        $item_id = $row5['package_id'];
        $item_gene_id = $row5['banquet_pack_id'];
        $meal[] =array(
          'name' => $package_name,
          'short_name' => $package_name,
          'unit_price' => $unit_price,
          'net_unit_price' => $net_unit_price,
          'qty' => $qty,
          'amount_gross' => $amount_gross,
          'item_group_id' => $item_group_id,
          'item_id' => $item_id,
          'item_gene_id' => $item_gene_id,
        );
      }
    }

    //パッケージ以外
    $sql6= 'select * from `view_charges` where `reservation_id` = ? AND `branch`= ?  AND `meal` = 1 AND (`package_id` = "" OR `package_id` IS NULL OR `package_id` = " ") AND `item_group_id` LIKE "F%"';
      $rows6 = $dbh->prepare($sql6);
      $rows6->execute([$reservation_id, $branch]);
      $f_count = $rows6->rowCount();
      if($f_count > 0){
        foreach ($rows6 as $row6) {
          $item_name = mb_convert_kana($row6['item_name'], "KVas");
          $short_name = mb_convert_kana($row6['name_short'], "KVas");
          $item_group_id = $row6['item_group_id'];
          $item_id = $row6['item_id'];
          $item_gene_id = $row6['item_gene_id'];
          $unit_price = $row6['unit_price'];
          if($item_gene_id == 'F17-0001'){
            $net_unit_price = $unit_price/$ex_rate;
          }elseif($item_gene_id == 'F03-0022'){
            $net_unit_price = ($unit_price - $ex_pice)/$ex_rate;
          }else{
            $net_unit_price = $unit_price/$ex_rate; 
          }
          
          $qty = $row6['qty'];
          $amount_gross = $row6['amount_gross'];
          $amount_net = $row6['amount_net'];
          $meal[] =array(
            'name' => $item_name,
            'short_name' => $short_name,
            'unit_price' => $unit_price,
            'net_unit_price' => $net_unit_price,
            'qty' => $qty,
            'amount_gross' => $amount_gross,
            'item_group_id' => $item_group_id,
            'item_id' => $item_id,
            'item_gene_id' => $item_gene_id,
          );
        }
      }

    if(sizeof($meal) > 0){
      $results[]=array(
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
      );
    }
    
  }


  // 読み込み確認
  $templatePath = './templates/kitchen-order-tmp.xlsx';
  if (!file_exists($templatePath)) {
    die('テンプレートファイルが見つかりません');
  }

  $objSpreadsheet = IOFactory::load($templatePath);

  // 7日分のシートを作成
  for($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime($start_date . ' + ' . $i . ' days'));
    $w = date('w', strtotime($date));
    $sheetTitle = date('Y年m月d日', strtotime($date)) . ' (' . $week[$w] . ')';

    $baseSheet = $objSpreadsheet->getSheetByName('template');
    if (!$baseSheet) {
      die('テンプレートシートが見つかりません');
    }

    $newSheet = clone $baseSheet;
    $newSheet->setTitle($sheetTitle);
    $objSpreadsheet->addSheet($newSheet);
    $objSpreadsheet->setActiveSheetIndex($objSpreadsheet->getIndex($newSheet));
    $sheet = $objSpreadsheet->getActiveSheet();
    $sheet->setCellValue('B1', $sheetTitle);

    $count = 0;
    foreach ($results as $result){
      if ($result['date'] == date('Y-m-d', strtotime($start_date . " +$i days"))){
        $count++;
      }
    }
    if($count == 0){
      $sheet->setCellValue('B3', '予約はありません。');
      continue;
    }else{
      $rowIndex = 3; // データの開始行
      foreach($results as $result){
        if ($result['date'] == date('Y-m-d', strtotime($start_date . " +$i days"))){
          $sheet->setCellValue('A' . $rowIndex, $result['category_name']);
          $sheet->setCellValue('B' . $rowIndex, $result['event_name']);
          $sheet->setCellValue('C' . $rowIndex, $result['pic']);
          $sheet->setCellValue('D' . $rowIndex, $result['room_name']);
          $sheet->setCellValue('E' . $rowIndex, date('H:i',strtotime($result['start'])));
          $meals = $result['meal'];
          $meal_name = '';
          $meal_qty = '';
          $meal_net_unit_price = '';
          $meal_count=0;
          foreach ($meals as $meal){
            if ($meal_count > 0) {
              $meal_name .= "\n".$meal['name'] ;
              $meal_qty .= "\n".$meal['qty'];
              $meal_net_unit_price .= "\n".number_format($meal['net_unit_price']);
            }else {
              $meal_name .= $meal['name'];
              $meal_qty .= $meal['qty'] ;
              $meal_net_unit_price .= number_format($meal['net_unit_price']);
            }
            
            $meal_count++;
          }
          $sheet->setCellValue('F' . $rowIndex, $meal_name);
          $sheet->setCellValue('G' . $rowIndex, $meal_net_unit_price);
          $sheet->setCellValue('H' . $rowIndex, $meal_qty);

          $rowIndex++;
        }
      }
    }
  }
  
  // テンプレートシートを削除
  $templateIndex = $objSpreadsheet->getIndex($objSpreadsheet->getSheetByName('template'));
  $objSpreadsheet->removeSheetByIndex($templateIndex);

 

  // 出力
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment;filename="kitchenOrder-'.$start_date.'の週_'.date("Ymd-His").'.xlsx"');
  header('Cache-Control: max-age=0');

  $writer = new Xlsx($objSpreadsheet);
  $writer->save('php://output');
  exit;
} else {
  echo '指定された期間にデータがありません。';
}
?>