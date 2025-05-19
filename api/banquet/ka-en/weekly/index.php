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
$events_ka=array();
$events_en=array();
$events_other=array();


for($i=0; $i<7; $i++){
  $hizuke =date('Y年m月d日 ', strtotime($date));
  $week = array('日', '月', '火', '水', '木', '金', '土');
  $hizuke .= '（' . $week[date('w', strtotime($date))] . '）';

  $dbh = new PDO(DSN, DB_USER, DB_PASS);
  $sql = 'select * from banquet_schedules where date = ?  order by start ASC, branch ASC';
  $stmt = $dbh->prepare($sql);
  $stmt->execute([$date]);
  $count = $stmt->rowCount();
  $events[$date]=array(
    'date'=>$date,
    'hizuke'=>$hizuke,
  );

  if($count >0){
    foreach ($stmt as $row) {
      if($row['status'] != 4 && $row['status'] != 5){
        $event_date = date('Y/m/d ', strtotime($row['date']));
        $event_start = date('H:i', strtotime($row['start']));
        $event_end = date('H:i', strtotime($row['end']));
        $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
        $stmt2 = $dbh->prepare($sql2);
        $stmt2->execute([$row['room_id']]);
        $room = $stmt2->fetch();
        $floor = $room['floor'];
        $sql3 = 'select * from banquet_purposes where banquet_purpose_id = ?';
        $stmt3 = $dbh->prepare($sql3);
        $stmt3->execute([$row['purpose_id']]);
        $purpose = $stmt3->fetch();
        $purpose_name = $purpose['banquet_purpose_name'];
        $purpose_short = $purpose['banquet_purpose_short'];
        $banquet_category_id = $purpose['banquet_category_id'];
        $summary_category = $purpose['summary_category'];
        $sql4 = 'select * from banquet_categories where banquet_category_id = ?';
        $stmt4 = $dbh->prepare($sql4);
        $stmt4->execute([$banquet_category_id]);
        $category = $stmt4->fetch();
        $category_name = $category['banquet_category_name'];
        $sql5 = 'select * from banquet_layouts where layout_id = ?';
        $stmt5 = $dbh->prepare($sql5);
        $stmt5->execute([$row['layout_id']]);
        $layout = $stmt5->fetch();
        $layout_name = $layout['layout_name'];
        $pic = mb_convert_kana($row['pic'], 'KVas');
        $pic = explode(' ', $pic);
        $events[$date][] = array(
          'reservation_id' => $row['reservation_id'],
          'branch' => $row['branch'],
          'banquet_schedule_id' => $row['banquet_schedule_id'],
          'event_name' => $row['event_name'],
          'start' => $event_start,
          'end' => $event_end,
          'people' => $row['people'],
          'room_name' => $row['room_name'],
          'floor' => $floor,
          'status' => $row['status'],
          'status_name' => $row['status_name'],
          'purpose_id' => $row['purpose_id'],
          'purpose_name' => $purpose_name,
          'purpose_short' => $purpose_short,
          'category_id' => $banquet_category_id,
          'category_name' => $category_name,
          'summary_category' => $summary_category,
          'layout_id' => $row['layout_id'],
          'layout_name' => $layout_name,
          'pic' => $pic[0]
        );
        
        if($summary_category == 1 ){
          if($row['purpose_id'] !=0 && $row['purpose_id'] !=88){
            $events_ka[$date][] = array(
              'reservation_id' => $row['reservation_id'],
              'branch' => $row['branch'],
              'banquet_schedule_id' => $row['banquet_schedule_id'],
              'event_name' => $row['event_name'],
              'start' => $event_start,
              'end' => $event_end,
              'people' => $row['people'],
              'room_name' => $row['room_name'],
              'floor' => $floor,
              'status' => $row['status'],
              'status_name' => $row['status_name'],
              'purpose_id' => $row['purpose_id'],
              'purpose_name' => $purpose_name,
              'purpose_short' => $purpose_short,
              'category_id' => $banquet_category_id,
              'category_name' => $category_name,
              'summary_category' => $summary_category,
              'layout_id' => $row['layout_id'],
              'layout_name' => $layout_name,
              'pic' => $pic[0]
            );
          }else{
            $res=array_column($events_other, 'reservation_id');
            if($res>0){
              $key = array_search($row['reservation_id'], $res);
              if($key !== false){
                $events_other[$key]['room_name'] .= '、'.$row['room_name'];
                if($events_other[$key]['people'] < $row['people']){
                  $events_other[$key]['people'] = $row['people'];
                }
              }else{
                $events_other[$date][] = array(
                  'reservation_id' => $row['reservation_id'],
                  'banquet_schedule_id' => $row['banquet_schedule_id'],
                  'event_name' => $row['event_name'],
                  'start' => $event_start,
                  'end' => $event_end,
                  'people' => $row['people'],
                  'room_name' => $row['room_name'],
                  'floor' => $floor,
                  'status' => $row['status'],
                  'status_name' => $row['status_name'],
                  'purpose_id' => $row['purpose_id'],
                  'purpose_name' => $purpose_name,
                  'purpose_short' => $purpose_short,
                  'category_id' => $banquet_category_id,
                  'category_name' => $category_name,
                  'summary_category' => $summary_category,
                  'layout_id' => $row['layout_id'],
                  'layout_name' => $layout_name,
                  'pic' => $pic[0]
                );
              }
            }
          }
        }elseif($summary_category == 2 ){
          $events_en[$date][] = array(
            'reservation_id' => $row['reservation_id'],
            'branch' => $row['branch'],
            'banquet_schedule_id' => $row['banquet_schedule_id'],
            'event_name' => $row['event_name'],
            'start' => $event_start,
            'end' => $event_end,
            'people' => $row['people'],
            'room_name' => $row['room_name'],
            'floor' => $floor,
            'status' => $row['status'],
            'status_name' => $row['status_name'],
            'purpose_id' => $row['purpose_id'],
            'purpose_name' => $purpose_name,
            'purpose_short' => $purpose_short,
            'category_id' => $banquet_category_id,
            'category_name' => $category_name,
            'summary_category' => $summary_category,
            'layout_id' => $row['layout_id'],
            'layout_name' => $layout_name,
            'pic' => $pic[0]
          );
        }
      }
    }
  }

  $date = date('Y-m-d', strtotime($date.' +1 day'));

}
$data=array(
  'status'=>200,
  'message'=>'OK',
  'events'=>$events,
  'events_ka'=>$events_ka,
  'events_en'=>$events_en,
  'events_other'=>$events_other
);
$jsonstr=json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo $jsonstr;
?>