<?php
require_once('../common/conf.php');
$date = date('Y-m-d');
$now = date('Y-m-d H:i:s');
if(isset($_REQUEST['event_date']) && $_REQUEST['event_date'] != '') {
  $date = $_REQUEST['event_date'];
  $now = $date . ' 00:00:00';
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
      'id'=>$row['banquet_schedule_id'],
      'reservation_id'=>$row['reservation_id'],
      'branch'=>$row['branch'],
      'event_name' => $event_name,
      'date' => $row['date'],
      'start' => $event_start,
      'end' => $event_end,
      'room_name' => $row['room_name'],
      'floor' => $floor
    );
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
}
?>