<?php
require_once('../common/conf.php');
$date = date('Y-m-d');
$now = date('Y-m-d H:i:s');
if(isset($_REQUEST['event_date']) && $_REQUEST['event_date'] != '') {
  $date = $_REQUEST['event_date'];
  $now = date('Y-m-d H:i:s', strtotime($date));
}
$hizuke =date('Y年m月d日 ', strtotime($date));
$week = array('日', '月', '火', '水', '木', '金', '土');
$hizuke .= '(' . $week[date('w', strtotime($date))] . '曜日)';


$dbh = new PDO(DSN, DB_USER, DB_PASS);

$sql = 'select * from banquet_schedules where date = ? and end > ? and enable = ? order by start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$date, $now, 1]);
$count = $stmt->rowCount();
$events=array();
if($count > 0){
  foreach ($stmt as $row) {
    $event_start = date('H:i', strtotime($row['start']));
    $event_end = date('H:i', strtotime($row['end']));
    $event_name = mb_convert_kana($row['event_name'], 'KVas');
    $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
    $stmt2 = $dbh->prepare($sql2);
    $stmt2->execute([$row['room_id']]);
    $room = $stmt2->fetch();
    $floor = $room['floor'];

    $events[] = array(
      'event_name' => $event_name,
      'date' => $row['date'],
      'start' => $event_start,
      'end' => $event_end,
      'room_name' => $row['room_name'],
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
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js"></script>
  <script src="js/particles.js"></script>
  <title>会議・宴会予定-<?=$hizuke ?></title>
</head>
<body>
  
  <div id="wrapper" >
    <header>
      <div class="headbox">
        <div class="headbox_left">
        <h1>Today's Schedule</h1>
        <h2><?= $hizuke ?></h2>
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
                <?=$event['start'] ?> - <?=$event['end'] ?>
              </div>
            </div>
          </div>
          <div class="eventbox_line"><img src="images/line001.png" alt=""></div>
        <?php } ?>

      <?php }else{ ?>
        <p>本日の予定はございません。</p>
      <?php } ?>
      </div>

    </main>
    <footer id="footer">
      <div class="footer_logo"><img src="images/NCH_enmusubi_logo.png" alt="CROWNえんむすび"></div>
    </footer>
    
  </div>
  <!--<div id="snow_particlesjs"></div>-->
</body>
</html>