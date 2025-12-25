<?php
require_once('../common/conf.php');
$date = date('Y-m-d');
if(isset($_REQUEST['event_date']) && $_REQUEST['event_date'] != '') {
  $date = $_REQUEST['event_date'];
}
$min_time = "06:00";
$max_time = "22:00";
if(isset($_REQUEST['time']) && $_REQUEST['time'] != '') {
  $time = $_REQUEST['time'];
  if($time < $min_time){
    $time = $min_time;
  }elseif($time > $max_time){
    $time = $max_time;
  }
}else{
  $time = $min_time;
}

$now = date('Y-m-d H:i:s', strtotime($date . ' ' . $time));
$hizuke =date('Y年m月d日 ', strtotime($date));
$week = array('日', '月', '火', '水', '木', '金', '土');
$hizuke .= '(' . $week[date('w', strtotime($date))] . '曜日)';

$minus_1h = (new DateTime($now))->modify('-1 hour')->format('H:i');
$minus_halfh = (new DateTime($now))->modify('-30 minutes')->format('H:i');
$minus_quarterh = (new DateTime($now))->modify('-15 minutes')->format('H:i');
$plus_quarterh = (new DateTime($now))->modify('+15 minutes')->format('H:i');
$plus_halfh = (new DateTime($now))->modify('+30 minutes')->format('H:i');
$plus_1h = (new DateTime($now))->modify('+1 hour')->format('H:i');

$dbh = new PDO(DSN, DB_USER, DB_PASS);

$sql = 'select * from banquet_schedules where date = ? and end > ? and enable = ? order by start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$date, $now, 1]);
$count = $stmt->rowCount();
$events=array();
if($count > 0){
  foreach ($stmt as $row) {
    $scheid = $row['banquet_schedule_id'];
    //例外表示があるかチェック
    $sql_ext = 'select * from banquet_ext_sign where sche_id = :scheid and enable = 1 order by start ASC, end ASC';
    $stmt_ext = $dbh->prepare($sql_ext);
    $stmt_ext->bindParam(':scheid', $scheid, PDO::PARAM_INT);
    $stmt_ext->execute();
    $ext_count = $stmt_ext->rowCount();
    if($ext_count > 0){
      foreach($stmt_ext as $ext_row){
        if( $ext_row['end'] > $now){
          $event_start = (new DateTime($ext_row['start']))->format('H:i');
          $event_end = (new DateTime($ext_row['end']))->format('H:i');
          $event_name = mb_convert_kana($ext_row['event_name'], 'KVas');
          break;
        }
      }
    }else{
      $event_start = (new DateTime($row['start']))->format('H:i');
      $event_end = (new DateTime($row['end']))->format('H:i');
      $event_name = mb_convert_kana($row['event_name'], 'KVas');
    }
    if(!isset($event_start)){
      continue;
    }
    $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
    $stmt2 = $dbh->prepare($sql2);
    $stmt2->execute([$row['room_id']]);
    $room = $stmt2->fetch();
    $room_name = $room['name'];
    $room_name_en = $room['name_en'];
    $floor = $room['floor'];

    $events[] = array(
      'event_name' => $event_name,
      'date' => $row['date'],
      'start' => $event_start,
      'end' => $event_end,
      'room_name' => $room_name,
      'room_name_en' => $room_name_en,
      'floor' => $floor
    );
  }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="300">
  <!-- reset.css ress -->
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css?<?= time() ?>">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js"></script>
  <script src="js/particles.js"></script>
  <title>会議・宴会予定-<?=$hizuke ?></title>
</head>
<body class="preview">
  
  <div id="wrapper" class="preview">
    <header>
      <div class="headbox">
        <div class="headbox_left">
          <h1>本日のご案内</h1>
          <h2><?= $hizuke ?></h2>
          <div id="realtime"><?= $now ?></div>
          <div class="time_nav">
            <a href="?event_date=<?= date('Y-m-d', strtotime($date . ' -1 day')) ?>">前日</a>
            <a href="?event_date=<?= date('Y-m-d', strtotime($date . ' +1 day')) ?>">翌日</a>
          </div>
          <div class="time_nav">
            <a href="?event_date=<?=$date ?>&time=<?=$min_time ?>">S</a>
            <a href="?event_date=<?=$date ?>&time=<?=$minus_1h ?>">-1H</a>
            <a href="?event_date=<?=$date ?>&time=<?=$minus_halfh ?>">-30</a>
            <a href="?event_date=<?=$date ?>&time=<?=$minus_quarterh ?>">-15</a>
            <a href="?event_date=<?=$date ?>&time=<?=$plus_quarterh ?>">+15</a>
            <a href="?event_date=<?=$date ?>&time=<?=$plus_halfh ?>">+30</a>
            <a href="?event_date=<?=$date ?>&time=<?=$plus_1h ?>">+1H</a>
            <a href="?event_date=<?=$date ?>&time=<?=$max_time ?>">E</a>
          </div>
          
        </div>
        <div class="headbox_right">
          <div class="headbox_logo">
            <img src="images/NCH_symbol.png" alt="Nagoya Crown Hotel">
          </div>
        </div>
      </div>
      
    </header>
    <main>
      <div id="events">
      <?php if(sizeof($events) > 0){ ?>
        <?php foreach($events as $event){ ?>
          <div class="eventbox">
            <div class="eventbox_left">
              <?=$event['event_name'] ?>
            </div>
            <div class="eventbox_right">
              <div class="eventbox_room">
                <?=$event['room_name'] ?> <span class="eventbox_floor">【<?=$event['floor'] ?>】</span>
              </div>
              <div class="eventbox_time">
                <?=$event['start'] ?> ～
              </div>
            </div>
          </div>
          <div class="eventbox_line"><img src="images/line001.png" alt=""></div>
        <?php } ?>

      <?php }else{ ?>
        <p>本日の予定は終了いたしました。</p>
      <?php } ?>
      </div>

    </main>
    <!--<footer id="footer">
      <div class="footer_logo"><img src="images/NCH_enmusubi_logo.png" alt="CROWNえんむすび"></div>
    </footer>-->
    
  </div>
  <!--<div id="snow_particlesjs"></div>-->
</body>
</html>