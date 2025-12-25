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

$date = date('Y-m-d');
$now = date('Y-m-d H:i:s');
$hour = date('H');
if($hour >= 22){
  $date = (new DateTime())->modify('+1 day')->format('Y-m-d');
}

$dateObj = new DateTime($date);
$hizuke = $dateObj->format('Y年m月d日');
$week = array('日', '月', '火', '水', '木', '金', '土');
$hizuke .= '（' . $week[(int)$dateObj->format('w')] . '曜日）';

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = <<<SQL
SELECT
  s.banquet_schedule_id,
  s.reservation_id,
  s.branch,
  s.event_name,
  s.date,
  s.start,
  s.end,
  r.name        AS room_name,
  r.name_en     AS room_name_en,
  r.floor,
  r.size,

  MIN(s.start) OVER (PARTITION BY s.reservation_id) AS first_start

FROM banquet_schedules s
LEFT JOIN banquet_rooms r
  ON r.banquet_room_id = s.room_id

WHERE
  s.date = ?
  AND s.end > ?
  AND s.enable = ?
  AND s.additional_sales = ?

ORDER BY
  first_start ASC,          -- 予約単位で時系列
  s.reservation_id ASC,     -- 同時刻の安定化
  s.start ASC,              -- グループ内 start
  (r.size IS NULL) ASC,     -- size NULLは最後（任意）
  r.size DESC,              -- start同一なら広い順
  s.branch ASC              -- 最後に支店で安定化（任意）
SQL;

$stmt = $dbh->prepare($sql);
$stmt->execute([$date, $now, 1, 0]);

$events = [];
foreach ($stmt as $row) {
  $events[] = [
    'id'            => $row['banquet_schedule_id'],
    'reservation_id'=> $row['reservation_id'],
    'branch'        => $row['branch'],
    'event_name'    => mb_convert_kana($row['event_name'], 'KVas'),
    'date'          => $row['date'],
    'start'         => (new DateTime($row['start']))->format('H:i'),
    'end'           => (new DateTime($row['end']))->format('H:i'),
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