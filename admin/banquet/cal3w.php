<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');

$weeks = 3;
$add_days = $weeks * 7 -1;
$date = date('Y-m-d');

$start_date =$date;
$w = date('w', strtotime($date));
if($w == 2) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
} elseif($w == 3) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -2 day'));
} elseif($w == 4) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -3 day'));
} elseif($w == 5) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -4 day'));
} elseif($w == 6) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -5 day'));
} elseif($w == 0) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -6 day'));
}

$end_date = date('Y-m-d', strtotime($start_date . ' +'.$add_days.' day'));

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = "SELECT
        `reservation_id`,
        `reservation_name`,
        `date`,
        min(`start`) as `start`,
        max(`end`) as `end`,
        count(`reservation_id`) as `count`,
        `pic`
        FROM `banquet_schedules`
        WHERE `date` BETWEEN :start_date AND :end_date
        AND `status` <> 5
        AND `reservation_name` not like '朝食会場'
        AND `reservation_name` not like '倉庫'
        GROUP BY `reservation_id`, `date`
        ORDER BY `date`, `start`, `end`, `reservation_id`";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':start_date', $start_date, PDO::PARAM_STR);
$stmt->bindValue(':end_date', $end_date, PDO::PARAM_STR); 
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
$stmt->closeCursor();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>３週分カレンダー</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <!--<script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>-->
  <!--<script src="js/admin_banquet.js"></script>-->
  <style>
    table.cal3w {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
    }
    table.cal3w th {
      background-color: #f2f2f2;
      padding: 10px;
      font-size: 14px;
      font-weight: bold;
      text-align: center;
    }
    table.cal3w td {
      border: 1px solid #ddd;
      padding: 2px;
      vertical-align: top;
    }
    table.cal3w tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    td.today {
      background-color: #ffeb3b;
    }
    td.pastday {
      background-color: #e0e0e0;
    }
    div.event_date {
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 5px;
    }
  </style>
</head>
<body>
<?php include("header.php"); ?>
<main>
<table class="cal3w">
  <tr>
    <th>月</th>
    <th>火</th>
    <th>水</th>
    <th>木</th>
    <th>金</th>
    <th>土</th>
    <th>日</th>
  </tr>
  <?php
  for($i=0; $i<21; $i++){
    $event_date = date('Y-m-d', strtotime($start_date . ' +'.$i.' day'));
    $w = date('w', strtotime($event_date));
    if($w == 1) {
      echo "<tr>";
    }
    if($date == $event_date) {
      echo "<td class='today'>";
    } elseif($event_date < $date) {
      echo "<td class='pastday'>";
    } else {
      echo "<td>";
    }
    echo "<div class='event_date'>【".date('m/d', strtotime($event_date))."】</div>";
    $store_time="";
    $store_name="";
    foreach($events as $event) {
      if($event['date'] == $event_date) {
        $event_time = date('H:i', strtotime($event['start']));
        $event_name = cleanLanternName($event['reservation_name']);
        if(($store_time != $event_time) || ($store_name != $event_name)) {
          echo "<div class='event'>";
          echo "<span class='event-time'>".date('H:i', strtotime($event_time))."～</span>&nbsp;";
          echo "<span class='event-name'>".cleanLanternName($event_name)."</span>";
          echo "</div>";
          $store_time = $event_time;
          $store_name = $event_name;
        }
      }
    }
    echo "</td>";
    if($w == 0) {
      echo "</tr>";
    }
  }
  ?>
</table>
</main>
<?php include("footer.php"); ?>
</body>
</html>