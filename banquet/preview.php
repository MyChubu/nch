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
$nowTime = date('H:i:s', strtotime($date . ' ' . $time));
$week = array('日', '月', '火', '水', '木', '金', '土');
$hizuke .= '(' . $week[date('w', strtotime($date))] . '曜日)';

$minus_1h = (new DateTime($now))->modify('-1 hour')->format('H:i');
$minus_halfh = (new DateTime($now))->modify('-30 minutes')->format('H:i');
$minus_quarterh = (new DateTime($now))->modify('-15 minutes')->format('H:i');
$plus_quarterh = (new DateTime($now))->modify('+15 minutes')->format('H:i');
$plus_halfh = (new DateTime($now))->modify('+30 minutes')->format('H:i');
$plus_1h = (new DateTime($now))->modify('+1 hour')->format('H:i');

$dbh = new PDO(DSN, DB_USER, DB_PASS, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$sql = <<<SQL
WITH
-- 例外 start/end を「必ず DATETIME」に正規化する
-- 例: '15:00:00' なら TIMESTAMP(当日, '15:00:00')
-- 例: '2026-02-10 15:00:00' ならそのまま
ext_norm AS (
  SELECT
    e.sche_id,
    CASE
      WHEN CAST(e.start AS CHAR) REGEXP '^[0-9]{2}:[0-9]{2}(:[0-9]{2})?$'
        THEN TIMESTAMP(?, e.start)
      ELSE e.start
    END AS start_dt,
    CASE
      WHEN CAST(e.end AS CHAR) REGEXP '^[0-9]{2}:[0-9]{2}(:[0-9]{2})?$'
        THEN TIMESTAMP(?, e.end)
      ELSE e.end
    END AS end_dt,
    e.event_name,
    e.subtitle
  FROM banquet_ext_sign e
  WHERE e.enable = 1
),

-- 今「有効な」例外だけを pick（start <= now < end）
ext_pick_ranked AS (
  SELECT
    n.sche_id,
    n.start_dt,
    n.end_dt,
    n.event_name,
    n.subtitle,
    ROW_NUMBER() OVER (PARTITION BY n.sche_id ORDER BY n.start_dt ASC, n.end_dt ASC) AS rn
  FROM ext_norm n
  WHERE n.end_dt > ?
),
ext_pick AS (
  SELECT sche_id, start_dt, end_dt, event_name, subtitle
  FROM ext_pick_ranked
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

  COALESCE(ep.start_dt, s.start)            AS view_start,
  COALESCE(ep.end_dt,   s.end)              AS view_end,
  COALESCE(ep.event_name, s.event_name)     AS view_event_name,
  COALESCE(ep.subtitle, '')                 AS view_subtitle,

  s.room_id,
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

  -- ★例外が「終了した」履歴があるなら、その後は通常終了が先でも出さない
  AND NOT EXISTS (
    SELECT 1
    FROM ext_norm n2
    WHERE n2.sche_id = s.banquet_schedule_id
      AND n2.end_dt <= ?
  )

ORDER BY
  rf.first_start ASC,
  s.reservation_id ASC,
  view_start ASC,
  (r.size IS NULL) ASC,
  r.size DESC,
  s.branch ASC
SQL;

try {
  $stmt = $dbh->prepare($sql);
  $stmt->execute([
    $date, // ext_norm: TIMESTAMP(?, e.start)
    $date, // ext_norm: TIMESTAMP(?, e.end)

    $now,  // ext_pick_ranked: n.end_dt > ?

    $date, // res_first: date = ?
    $now,  // res_first: end > ?
    1,     // res_first: enable = ?

    $date, // main: s.date = ?
    $now,  // main: s.end > ?
    1,     // main: s.enable = ?

    $now,  // NOT EXISTS: n2.end_dt <= ?
  ]);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode([
    'status' => 500,
    'message' => 'SQL error',
    'detail' => $e->getMessage(),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

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