<?php
require_once('../../../common/conf.php');
// CORSヘッダーを設定
header("Access-Control-Allow-Origin: *"); // すべてのオリジンを許可
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // 許可するHTTPメソッド
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // 許可するリクエストヘッダー
header("Content-Type: application/json; charset=UTF-8"); // JSONのコンテンツタイプを設定

// プリフライトリクエスト(OPTIONS)への対応
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit;
}

//dtのフォーマットはYYYYMMDDHHMM
$dt = $_REQUEST['dt'] ?? '';

if ($dt !== '' && preg_match('/^\d{12}$/', $dt)) {
  $dtObj = DateTimeImmutable::createFromFormat('YmdHi', $dt);
  if ($dtObj === false) {
    http_response_code(400);
    echo json_encode(['status'=>400,'message'=>'dt format error'], JSON_UNESCAPED_UNICODE);
    exit;
  }
} else {
  $dtObj = new DateTimeImmutable();
}

// 22時以降は翌日扱い（dt指定時も同じルールで良いならこれでOK）
$baseDateObj = ((int)$dtObj->format('H') >= 22)
  ? $dtObj->modify('+1 day')
  : $dtObj;

$date = $baseDateObj->format('Y-m-d');
$now  = $dtObj->format('Y-m-d H:i:00'); // 比較用の「今」
$hour = $dtObj->format('H');
$nowTime = $dtObj->format('H:i:00');


$dateObj = new DateTime($date);
$hizuke = $dateObj->format('Y年m月d日');
$week = array('日', '月', '火', '水', '木', '金', '土');
$hizuke .= '（' . $week[(int)$dateObj->format('w')] . '曜日）';

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
  if( !isset($row['view_subtitle']) ){
    $row['view_subtitle'] = '';
  }
  if( $row['view_subtitle'] !='' ){
    $event_full = $row['view_event_name'] . '<br>' . $row['view_subtitle']; 
  }else{
    $event_full = $row['view_event_name'];
  }
  $events[] = [
    'id'            => $row['banquet_schedule_id'],
    'reservation_id'=> $row['reservation_id'],
    'branch'        => $row['branch'],
    'event_name'    => mb_convert_kana($row['view_event_name'], 'KVas'),
    'subtitle'      => mb_convert_kana($row['view_subtitle'], 'KVas'),
    'event_full'    => mb_convert_kana($event_full, 'KVas'),
    'date'          => $row['date'],
    'start'         => (new DateTime($row['view_start']))->format('H:i'),
    'end'           => (new DateTime($row['view_end']))->format('H:i'),
    'room_id'       => $row['room_id'],
    'room_name'     => $row['room_name'],
    'room_name_en'  => $row['room_name_en'],
    'floor'         => $row['floor'],
  ];
}

$data=array(
  'status'=>200,
  'message'=>'OK',
  'date'=>$date,
  'week' => $week[(int)$dateObj->format('w')],
  'hizuke'=>$hizuke,
  'events'=>$events
);
$jsonstr=json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo $jsonstr;
?>