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
if($hour >= 21){
  $date = date('Y-m-d', strtotime('+1 day'));
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
  'week'=>$week[date('w', strtotime($date))],
  'hizuke'=>$hizuke,
  'events'=>$events
);
$jsonstr=json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo $jsonstr;
?>