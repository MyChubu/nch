<?php
require_once('../../../../common/conf.php');

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
if(isset($_REQUEST['date'])){
  $date = $_REQUEST['date'];
}

$events=array();
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = 'select * from banquet_rooms where status = ?  order by order ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([1]);
$rooms = $stmt->fetch();
foreach ($rooms as $room) {
  $room_id = $room['banquet_room_id'];
  $room_name = $room['banquet_room_name'];
  $events[$room_id]=array(
    'room_id'=>$room_id,
    'room_name'=>$room_name,
  );
}
$data=array(

  'events'=>$events,
);

$jsonstr=json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo $jsonstr;
?>