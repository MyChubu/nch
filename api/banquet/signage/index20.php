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
$sql = 'select * from banquet_schedules where date = ? and end > ? and enable = ? and additional_sales = ? order by start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$date, $now, 1, 0]);
$count = $stmt->rowCount();
$events=array();
if($count > 0){
  foreach ($stmt as $row) {
    $event_start = (new DateTime($row['start']))->format('H:i');
    $event_end = (new DateTime($row['end']))->format('H:i');
    $event_name = mb_convert_kana($row['event_name'], 'KVas');
    $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
    $stmt2 = $dbh->prepare($sql2);
    $stmt2->execute([$row['room_id']]);
    $room = $stmt2->fetch();
    $room_name = $room['name'];
    $room_name_en = $room['name_en'];
    $floor = $room['floor'];
    $events[] = array(
      'id'=>$row['banquet_schedule_id'],
      'reservation_id'=>$row['reservation_id'],
      'branch'=>$row['branch'],
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