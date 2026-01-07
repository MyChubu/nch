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

$dateObj = new DateTime($date);
$hizuke = $dateObj->format('Y年m月d日');
$week = array('日', '月', '火', '水', '木', '金', '土');
$hizuke .= '（' . $week[(int)$dateObj->format('w')] . '曜日）';

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
    ROW_NUMBER() OVER (PARTITION BY e.sche_id ORDER BY e.start ASC, e.end ASC) AS rn
  FROM banquet_ext_sign e
  WHERE e.enable = 1
    AND e.end > ?
),
-- 上のうち、sche_idごとに「採用する1件（rn=1）」だけ残す
ext_pick AS (
  SELECT sche_id, start, end, event_name
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
$stmt->execute([
  $now,   // ext_ranked: e.end > ?
  $date,  // res_first:  date = ?
  $now,   // res_first:  end > ?
  1,      // res_first:  enable = ?
  $date,  // main:       s.date = ?
  $now,   // main:       s.end > ?
  1       // main:       s.enable = ?
]);

$events = [];
foreach ($stmt as $row) {
  $events[] = [
    'id'            => $row['banquet_schedule_id'],
    'reservation_id'=> $row['reservation_id'],
    'branch'        => $row['branch'],
    'event_name'    => mb_convert_kana($row['view_event_name'], 'KVas'),
    'date'          => $row['date'],
    'start'         => (new DateTime($row['view_start']))->format('H:i'),
    'end'           => (new DateTime($row['view_end']))->format('H:i'),
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