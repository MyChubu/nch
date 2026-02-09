<?php
require_once('../../common/conf.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
session_name('_NCH_ADMIN');
session_start();
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : '';
$user_name = $_SESSION['name'];

if (empty($user_id) || empty($user_name)) {
  header('Location: ../login.php?error=2');
  exit;
}else{
  $sql = "SELECT * FROM users WHERE user_id = :user_id AND status = 1";
  $stmt = $dbh->prepare($sql);
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $u_count = $stmt->rowCount();
  if ($u_count < 1) {
    header('Location: ../login.php?error=3');
    exit;
  }
}
$user_mail = $_SESSION['mail'];
$admin = $_SESSION['admin'];
require_once('functions/admin_banquet.php');
$week = array('日', '月', '火', '水', '木', '金', '土');
$ex_rate = 1.21; // 税率
$ex_pice = 160; //コロナ対策費

$mdate = new DateTime('+7 days');
$mdate->modify('next monday'); // 必ず14日より後の月曜になる

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

if (isset($_REQUEST['startdate']) && $_REQUEST['startdate'] != '') {
  $s_date = new DateTime($_REQUEST['startdate']);
} else {
  $s_date = $mdate;
}

$start_date = $s_date->format('Y-m-d');

$end_date_obj = clone $s_date;
$end_date_obj->modify('+6 days');
$end_date = $end_date_obj->format('Y-m-d');

$week_before_obj = clone $s_date;
$week_before_obj->modify('-7 days');
$week_before = $week_before_obj->format('Y-m-d');

$week_after_obj = clone $s_date;
$week_after_obj->modify('+7 days');
$week_after = $week_after_obj->format('Y-m-d');

$results = array();
$sql = 'select * from banquet_schedules where (date BETWEEN ? AND ?) AND status IN(1,2,3) order by start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$count = $stmt->rowCount();

if ($count == 0) {
  exit;
} else {
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $date = $row['date'];
    $w = (new DateTime($date))->format('w');
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
    $stmt2 = $dbh->prepare('select * from banquet_rooms where banquet_room_id = ?');
    $stmt2->execute([$room_id]);
    $room_name = $stmt2->fetch(PDO::FETCH_ASSOC)['name'];
    $purpose_id = $row['purpose_id'];
    $stmt3 = $dbh->prepare('select * from banquet_purposes where banquet_purpose_id = ?');
    $stmt3->execute([$purpose_id]);
    $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    $purpose_name = $row3['banquet_purpose_name'];
    $purpose_short = $row3['banquet_purpose_short'];
    $banquet_category_id = $row3['banquet_category_id'];
    $summary_category = $row3['summary_category'];
    $stmt4 = $dbh->prepare('select * from banquet_categories where banquet_category_id = ?');
    $stmt4->execute([$banquet_category_id]);
    $category_name = $stmt4->fetch(PDO::FETCH_ASSOC)['banquet_category_name'];

    $meal = array();
    if ($purpose_id == 35 && $reservation_name != '朝食会場') {
      $meal[] = array(
        'name' => '朝食バイキング',
        'short_name' => '朝バ',
        'unit_price' => 1100,
        'net_unit_price' => 1000,
        'qty' => $people,
        'amount_gross' => 1100 * $people,
        'item_group_id' => '',
        'item_id' => '',
        'item_gene_id' => '',
      );
    }

    $stmt5 = $dbh->prepare('select * from view_package_charges where reservation_id = ? AND branch = ?');
    $stmt5->execute([$reservation_id, $branch]);
    foreach ($stmt5->fetchAll(PDO::FETCH_ASSOC) as $row5) {
      $unit_price = intval($row5['UnitP']);
      $meal[] = array(
        'name' => mb_convert_kana($row5['NameShort'], "KVas"),
        'short_name' => mb_convert_kana($row5['NameShort'], "KVas"),
        'unit_price' => $unit_price,
        'net_unit_price' => ($unit_price - $ex_pice) / $ex_rate,
        'qty' => intval($row5['Qty']),
        'amount_gross' => intval($row5['Gross']),
        'item_group_id' => $row5['package_category'],
        'item_id' => $row5['package_id'],
        'item_gene_id' => $row5['banquet_pack_id'],
      );
    }

    $stmt6 = $dbh->prepare('select * from view_charges where reservation_id = ? AND branch = ? AND meal = 1 AND (package_id = "" OR package_id IS NULL OR package_id = " ") AND item_group_id LIKE "F%"');
    $stmt6->execute([$reservation_id, $branch]);
    foreach ($stmt6->fetchAll(PDO::FETCH_ASSOC) as $row6) {
      $unit_price = $row6['unit_price'];
      if ($row6['item_gene_id'] == 'F17-0001') {
        $net_unit_price = $unit_price / $ex_rate;
      } elseif ($row6['item_gene_id'] == 'F03-0022') {
        $net_unit_price = ($unit_price - $ex_pice) / $ex_rate;
      } else {
        $net_unit_price = $unit_price / $ex_rate;
      }
      $meal[] = array(
        'name' => mb_convert_kana($row6['item_name'], "KVas"),
        'short_name' => mb_convert_kana($row6['name_short'], "KVas"),
        'unit_price' => $unit_price,
        'net_unit_price' => $net_unit_price,
        'qty' => $row6['qty'],
        'amount_gross' => $row6['amount_gross'],
        'item_group_id' => $row6['item_group_id'],
        'item_id' => $row6['item_id'],
        'item_gene_id' => $row6['item_gene_id'],
      );
    }

    if (count($meal) > 0) {
      $results[] = array(
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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>厨房発注（<?=$start_date ?>の週）</title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/form.css">
  <link rel="stylesheet" href="css/kitchen_order.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">

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
            <?php
              $monday_obj = new DateTime($monday);
            ?>
            <option value="<?= $monday ?>" <?= ($start_date == $monday) ? 'selected' : '' ?>>
              <?= $monday_obj->format('Y年n月j日') ?> (<?= $week[$monday_obj->format('w')] ?>)
            </option>
          <?php endforeach; ?>
        </select>の週
        <button type="submit">日付変更</button>
      </form>
      <div id="controller_date">
        <div id="week_before"><a href="?startdate=<?= $week_before ?>" title="前週：<?= $week_before ?>"><i class="fa-solid fa-arrow-left"></i><i class="fa-solid fa-arrow-left"></i>前週</a></div>
        <div id="week_after"><a href="?startdate=<?= $week_after ?>" title="翌週：<?= $week_after ?>">翌週<i class="fa-solid fa-arrow-right"></i><i class="fa-solid fa-arrow-right"></i></a></div>
        <div id="download"><a href="output/kitchen_order_export.php?startdate=<?=$start_date ?>" target="_blank"><i class="fa-solid fa-file-excel"></i>EXCEL</a></div>
      </div>
    </div>
    <div id="controller_right">

    </div>
    
  </div>
  <?php for($i = 0; $i < 7; $i++): ?>
    <?php
      $current_day_obj = clone $s_date;
      $current_day_obj->add(new DateInterval("P{$i}D"));
      $current_day = $current_day_obj->format('Y-m-d');
      $w = $current_day_obj->format('w');
    ?>
    <div class="day">
      <h2><?= $current_day_obj->format('Y年n月j日') ?> (<?= $week[$w] ?>)</h2>
      <?php
      $count = 0;
      foreach ($results as $result){
        if ($result['date'] == $current_day){
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
            <?php if ($result['date'] == $current_day): ?>
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
                <td class="time"><?= (new DateTime($result['start']))->format('H:i') ?></td>
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
<?php include("../common/footer.php"); ?>

</body>
</html>
