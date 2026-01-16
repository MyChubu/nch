<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
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

$sql = <<<SQL
WITH
-- 例外表示（有効）を、sche_idごとに start→end で並べ、今より後に終わるものに順位を付ける
ext_ranked AS (
  SELECT
    e.sche_id,
    e.start,
    e.end,
    e.event_name,
    e.subtitle,
    ROW_NUMBER() OVER (PARTITION BY e.sche_id ORDER BY e.start ASC, e.end ASC) AS rn
  FROM banquet_ext_sign e
  WHERE e.enable = 1
    AND e.end > ?
),
-- 上のうち、sche_idごとに「採用する1件（rn=1）」だけ残す
ext_pick AS (
  SELECT sche_id, start, end, event_name, subtitle
  FROM ext_ranked
  WHERE rn = 1
),
-- reservation_idごとの最小start（予約単位で時系列にするキー）
res_first AS (
  SELECT
    reservation_id,
    MIN(start) AS first_start
  FROM banquet_schedules
  WHERE date = ?
    AND end > ?
    AND enable = ?
  GROUP BY reservation_id
)

SELECT
  s.reservation_id,
  s.banquet_schedule_id,
  s.branch,
  s.date,

  -- ★例外があれば例外、なければ通常
  COALESCE(ep.start, s.start)      AS view_start,
  COALESCE(ep.end,   s.end)        AS view_end,
  COALESCE(ep.event_name, s.event_name) AS view_event_name,
  COALESCE(ep.subtitle, '') AS view_subtitle,

  r.name    AS room_name,
  r.name_en AS room_name_en,
  r.floor,
  r.size,

  rf.first_start

FROM banquet_schedules s
JOIN res_first rf
  ON rf.reservation_id = s.reservation_id
LEFT JOIN ext_pick ep
  ON ep.sche_id = s.banquet_schedule_id
LEFT JOIN banquet_rooms r
  ON r.banquet_room_id = s.room_id

WHERE s.date = ?
  AND s.end > ?
  AND s.enable = ?

ORDER BY
  rf.first_start ASC,        -- 予約単位で時系列（最小start）
  s.reservation_id ASC,      -- 同時刻の安定化
  view_start ASC,            -- 予約内は表示開始が早い順（例外反映後）
  (r.size IS NULL) ASC,      -- size NULLは最後（任意）
  r.size DESC,               -- view_start同じなら面積が大きい順
  s.branch ASC               -- 最後に安定化（任意）
SQL;

$stmt = $dbh->prepare($sql);
$stmt->execute([$now, $date, $now, 1, $date, $now, 1]);

$events = [];
foreach ($stmt as $row) {
  $event_name = mb_convert_kana($row['view_event_name'], 'KVas');
  $subtitle = mb_convert_kana($row['view_subtitle'], 'KVas');
  if( !isset($row['view_subtitle']) ){
    $row['view_subtitle'] = '';
  }
  if( $row['view_subtitle'] !='' ){
    $event_full = $row['view_event_name'] . '<br>' . $row['view_subtitle']; 
  }else{
    $event_full = $row['view_event_name'];
  }
  $events[] = [
    'event_name'   => $event_full,
    'date'         => $row['date'],
    'start'        => (new DateTime($row['view_start']))->format('H:i'),
    'end'          => (new DateTime($row['view_end']))->format('H:i'),
    'room_name'    => $row['room_name'],
    'room_name_en' => $row['room_name_en'],
    'floor'        => $row['floor'],
  ];
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
              <?=str_replace('///', '<br>',$event['event_name']) ?>
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