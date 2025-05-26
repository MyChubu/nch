<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
$week = array('日', '月', '火', '水', '木', '金', '土');
$ex_rate = 1.21; // 税率
$ex_pice = 160; //コロナ対策費

$start_date = '2025-05-26';
$end_date = date('Y-m-d', strtotime($start_date . ' + 6 days'));

$results = array();
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = 'select * from banquet_schedules where (date BETWEEN ? AND ?) AND status IN( 1,2)  order by start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$count = $stmt->rowCount();
if ($count == 0) {
  
  exit;
}else{
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $date = $row['date'];
    $w = date('w', strtotime($date));
    $reservation_id = $row['reservation_id'];
    $branch = $row['branch'];
    $reservation_name = $row['reservation_name'];
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
        'amount_gross' => 1100 * $row['people']
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
        $net_unit_price = ($unit_price - $ex_pice) / $ex_rate;
        $qty = intval($row5['Qty']);
        $amount_gross = intval($row5['Gross']);
        $amount_net = $row5['Net'];
        $service_fee = $row5['ServiceFee'];
        $tax = $row5['Tax'];
        $discount_name = '';
        $discount_rate = 0;
        $discount_amount = $row5['Discount'];
        $meal[] =array(
          'name' => $package_name,
          'short_name' => $package_name,
          'unit_price' => $unit_price,
          'net_unit_price' => $net_unit_price,
          'qty' => $qty,
          'amount_gross' => $amount_gross,
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
          $unit_price = $row6['unit_price'];
          $net_unit_price = $unit_price/$ex_rate;
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
          );
        }
      }

    if(sizeof($meal) > 0){
      $results[]=array(
        'date' => $date,
        'w' => $week[$w],
        'reservation_id' => $reservation_id,
        'branch' => $branch,
        'reservation_name' => $reservation_name,
        'event_name' => $event_name,
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

}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>厨房発注（<?=$date ?>）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <!--<script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>-->
  <!--<script src="js/admin_banquet.js"></script>-->
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <div id="controller">
    <div id="controller_left">
      <form  enctype="multipart/form-data" id="schedate_change">
        <select name="start_date" id="start_date">
        </select>
        <button type="submit">日付変更</button>
      </form>

    </div>
    <div id="controller_right">

    </div>
    
  </div>
  <?php var_dump($results); ?>
  
  
  
  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>
