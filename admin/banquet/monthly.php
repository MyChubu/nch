<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');

$today = date('Y-m-d');

$ym = date('Y-m');
if(isset($_REQUEST['ym']) && $_REQUEST['ym'] != '') {
  $ym = $_REQUEST['ym'];
}


$yearmonth = explode('-', $ym);
$first_day = date('Y-m-01', strtotime($ym));
$last_day = date('t', strtotime($first_day));

$before_month = date('Y-m', strtotime($first_day . ' -1 month'));
$next_month = date('Y-m', strtotime($first_day . ' +1 month'));

$fw= date('w', strtotime($first_day));
$ew= date('w', strtotime($first_day . ' +'.$last_day.' day'));

$start_date = $first_day;
if($fw == 2) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
} elseif($fw == 3) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -2 day'));
} elseif($fw == 4) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -3 day'));
} elseif($fw == 5) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -4 day'));
} elseif($fw == 6) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -5 day'));
} elseif($fw == 0) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -6 day'));
}
$end_date = date('Y-m-d', strtotime($first_day . ' +'.$last_day.' day'));
$days = 35;
if($ew == 1) {
  $end_date = date('Y-m-d', strtotime($end_date . ' +13 day'));
  $days = 42;
} elseif($ew == 2) {
  $end_date = date('Y-m-d', strtotime($end_date . ' +12 day'));
  $days = 42;
} elseif($ew == 3) {
  $end_date = date('Y-m-d', strtotime($end_date . ' +11 day'));
  $days = 42;
} elseif($ew == 4) {
  $end_date = date('Y-m-d', strtotime($end_date . ' +3 day'));
  } elseif($ew == 5) {
  $end_date = date('Y-m-d', strtotime($end_date . ' +2 day'));
} elseif($ew == 6) {
  $end_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
} 


$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = "SELECT
        `reservation_id`,
        `reservation_name`,
        `date`,
        `status`,
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
  <title><?=$ym ?>予定</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <!--<script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>-->
  <!--<script src="js/admin_banquet.js"></script>-->
  <link rel="stylesheet" href="css/monthly.css">
</head>
<body>
<?php include("header.php"); ?>
<main>
  <div id="controller_month">
    <div id="before_month"><a href="?ym=<?= $before_month ?>" title="<?=$before_month ?>"><i class="fa-solid fa-arrow-left"></i>前月</a></div>
    <div id="this_month"><span><?=$yearmonth[0] ?>年<?=$yearmonth[1] ?>月</span></div>
    <div id="next_month"><a href="?ym=<?= $next_month ?>" title="<?=$next_month ?>">翌月<i class="fa-solid fa-arrow-right"></i></a></div>
  </div>
<?php if($count > 0): ?>
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
  for($i=0; $i<$days; $i++){
    $event_date = date('Y-m-d', strtotime($start_date . ' +'.$i.' day'));
    $w = date('w', strtotime($event_date));
    if($w == 1) {
      echo "<tr>";
    }
    if($today == $event_date) {
      echo "<td class='today'>";
    } elseif($event_date < $today) {
      echo "<td class='pastday'>";
    } else {
      echo "<td>";
    }
    echo "<div class='event_date'>【<a href='ka_en_list.php?event_date=".$event_date."' target='_blank' title='".$event_date."の予定'>".date('m/d', strtotime($event_date))."</a>】</div>";
    $store_time="";
    $store_name="";
    foreach($events as $event) {
      if($event['date'] == $event_date) {
        $purpose = "";
        if (strpos($event['reservation_name'], '下見') !== false) {
          $purpose = '下見';
        }
        $event_time = date('H:i', strtotime($event['start']));
        $event_name = cleanLanternName($event['reservation_name']);
        $pic = mb_convert_kana($event['pic'], 'KVas');
        $pic = explode(' ',$pic);
        
        if(($store_time != $event_time) || ($store_name != $event_name)) {
          if($event['status'] == 2) {
            echo "<div class='event event-kari'>";
          }else {
            echo "<div class='event'>";
          }
          echo "<span class='event-time'>".date('H:i', strtotime($event_time))."</span>";
          if($purpose == '下見') {
            echo '<span class="pur"><i class="fa-regular fa-eye"></i></span>';
          }
          echo "<span class='event-name'>".cleanLanternName($event_name)."</span>";
          if($pic[0] !=""){
            echo "<span class='pic'>".$pic[0]."</span>";
          }
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
<div>
  <div class="legend">凡例：
    <span class="event-kari">仮予約</span>
    <span class="pur"><i class="fa-regular fa-eye"></i>下見</span>
  </div>
</div>
<?php else: ?>
  <div class="no_event">データはありません。</div>
<?php endif; ?>
</main>
<?php include("footer.php"); ?>
</body>
</html>