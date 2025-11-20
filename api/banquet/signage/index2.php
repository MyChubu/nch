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
$sql = 'select * from banquet_schedules where date = ? and end > ? and enable = ? and additional_sales = ? order by start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$date, $now, 1, 0]);
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