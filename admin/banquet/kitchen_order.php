<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
$week = array('日', '月', '火', '水', '木', '金', '土');
$ex_rate = 1.21; // 税率
$ex_pice = 160; //コロナ対策費

$mdate = new DateTime('+7 days');
$mdate->modify('next monday'); // 必ず14日より後の月曜になる
$mdate->format('Y-m-d');

$today = new DateTime();

// 今日を含む週の月曜日を基準に
$baseMonday = clone $today;
$baseMonday->modify('monday this week');

$mondays = [];

for ($i = -1; $i <= 4; $i++) {
  $monday = clone $baseMonday;
  $monday->modify("$i week");
  $mondays[] = $monday->format('Y-m-d');
}
if( isset($_REQUEST['startdate']) && $_REQUEST['startdate'] != '') {
  $s_date = new DateTime($_REQUEST['startdate']);
  
} else {
  $s_date = $mdate;
}
$start_date = $s_date->format('Y-m-d');
$end_date = date('Y-m-d', strtotime($start_date . ' + 6 days'));

$results = array();
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = 'select * from banquet_schedules where (date BETWEEN ? AND ?) AND status IN( 1,2,3)  order by start ASC, branch ASC';
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

}


// 結果表示


?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>厨房発注（<?=$start_date ?>の週）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/kitchen_order.css">
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
        <select name="startdate" id="startdate">
        <?php foreach ($mondays as $monday): ?>
          <option value="<?= $monday ?>" <?= ($start_date == $monday) ? 'selected' : '' ?>><?= date('Y年n月j日', strtotime($monday)) ?> (<?=$week[date('w', strtotime($monday))] ?>)</option>
        <?php endforeach; ?>
        </select>の週
        <button type="submit">日付変更</button>
      </form>

    </div>
    <div id="controller_right">

    </div>
    
  </div>
  <?php for($i = 0; $i < 7; $i++): ?>
    <div class="day">
      <h2><?= date('Y年n月j日', strtotime($start_date . " +$i days")) ?> (<?=$week[date('w', strtotime($start_date . " +$i days"))] ?>)</h2>
      <?php
      $count = 0;
      foreach ($results as $result){
        if ($result['date'] == date('Y-m-d', strtotime($start_date . " +$i days"))){
          $count++;
        }
      }
      ?>
      <?php if($count == 0): ?>
        <p>予約はありません。</p>
      <?php else: ?>
      
      <table class="order_table">
        <thead>
          <tr>
            <th class="kind">種類</th>
            <th class="event_name">宴席名</th>
            <th class="pic">担当</th>
            <th class="room">会場</th>
            <th class="time">開始時間</th>
            <th class="meal">料理</th>
            <th class="item_code">item ID</th>
            <th class="unit_price">単価</th>
            <th class="people">人数</th>
            <th class="">卓数</th>
            <th class="">備考</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $result): ?>
            <?php if ($result['date'] == date('Y-m-d', strtotime($start_date . " +$i days"))): ?>
              <tr<?=$result['banquet_category_id']==1 ? ' class="redrow"':'' ?>>
                <td class="kind">
                  <?= $result['category_name'] ?>
                  <!--<p><?=$result['banquet_category_id'] ?></p>-->
                </td>
                <td class="event_name">
                  <p class="rsv_id"><?= $result['reservation_id']."-".$result['branch'] ?></p>
                  <!--<p><?= $result['reservation_name'] ?></p>-->
                  <p class="title">
                    <?php if($result['status'] == 2): ?>
                      <span class="status_2">仮）</span>
                    <?php elseif($result['status'] == 3): ?>
                      <span class="status_3">営）</span>
                    <?php endif; ?>
                    <?= $result['event_name'] ?>
                  </p>
                </td>
                <td class="pic"><?=$result['pic'] ?></td>
                <td class="room">
                  <p class="room_id"><?= $result['room_id'] ?></p>
                  <p class="room_name"><?= $result['room_name'] ?></p>
                </td>
                <td class="time"><?= date('H:i',strtotime($result['start'])) ?></td>
                <td class="meal">
                  <?php foreach ($result['meal'] as $meal): ?>
                    <?php if($meal['item_gene_id'] == 'F17-0001' || $meal['item_gene_id'] == 'F17-0004' || $meal['item_gene_id'] == 'F17-0012'): ?>
                      <p class="red_bold">
                    <?php else: ?>
                    <p>
                    <?php endif; ?><?= $meal['short_name'] ?></p>
                  <?php endforeach; ?>
                </td>
                <td class="item_code">
                  <?php foreach ($result['meal'] as $meal): ?>
                    <p><?= $meal['item_gene_id'] ?></p>
                  <?php endforeach; ?>
                </td>
                <td class="unit_price">
                  <?php foreach ($result['meal'] as $meal): ?>
                    <p><?= number_format($meal['net_unit_price']) ?></p>
                  <?php endforeach; ?>
                </td>
                
                <td class="people">
                  <?php foreach ($result['meal'] as $meal): ?>
                    <p><?= number_format($meal['qty']) ?></p>
                  <?php endforeach; ?>
                </td>
                <td> </td>
                <td> </td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
        
        

    </div>
  <?php endfor; ?>

  <?php #var_dump($results); ?>
  
  
  
  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>
