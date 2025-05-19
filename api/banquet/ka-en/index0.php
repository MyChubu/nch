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

$hour = date('H');

$date = date('Y-m-d');
$datetime = date('Y-m-d H:i:s');
if($hour >= 18){
  $date = date('Y-m-d', strtotime('+1 day'));
}
#$date = '2024-12-20';
$hizuke =date('Y年m月d日 ', strtotime($date));
$week = array('日', '月', '火', '水', '木', '金', '土');
$hizuke .= '（' . $week[date('w', strtotime($date))] . '）';

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = 'select * from banquet_schedules where date = ?  order by start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$date]);
$count = $stmt->rowCount();
$events=array();
$events_en=array();
$events_ka=array();
$events_other=array();

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
      $agent_id=intval($row['agent_id']);
      $agent_name=mb_convert_kana($row['agent_name'], "KVas");
      $agent_group = '';
      $agent_group_short = '';
      $reserver=mb_convert_kana($row['reserver'], "KVas");
      if($agent_id > 0){
        $sql6 = 'select * from banquet_agents where agent_id = ?';
        $stmt6 = $dbh->prepare($sql6);
        $stmt6->execute([$agent_id]);
        $agent_g = $stmt6->fetch();
        $agent_group = $agent_g['agent_group'];
        $agent_group_short = $agent_g['agent_group_short'];
      }
      $enable = $row['enable'];
      $events[] = array(
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
        'pic' => $pic[0],
        'agent_id' => $agent_id,
        'agent_name' => $agent_name,
        'agent_group' => $agent_group,
        'agent_group_short' => $agent_group_short,
        'reserver' => $reserver,
        'enable' => $enable
      );
      
      if($summary_category == 1 ){
        if($row['purpose_id'] !=0 && $row['purpose_id'] !=88 && $row['purpose_id'] !=94){
          $events_ka[] = array(
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
            'pic' => $pic[0],
            'agent_id' => $agent_id,
            'agent_name' => $agent_name,
            'agent_group' => $agent_group,
            'agent_group_short' => $agent_group_short,
            'reserver' => $reserver,
            'enable' => $enable
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
              $events_other[] = array(
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
                'pic' => $pic[0],
                'agent_id' => $agent_id,
                'agent_name' => $agent_name,
                'agent_group' => $agent_group,
                'agent_group_short' => $agent_group_short,
                'reserver' => $reserver,
                'enable' => $enable
                );
            }
          }
        }
      }elseif($summary_category == 2 ){
        $events_en[] = array(
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
          'pic' => $pic[0],
          'agent_id' => $agent_id,
          'agent_name' => $agent_name,
          'agent_group' => $agent_group,
          'agent_group_short' => $agent_group_short,
          'reserver' => $reserver,
          'enable' => $enable
        );
      }
    }
  }
}
$data=array(
  'status'=>200,
  'message'=>'OK',
  'date'=>$date,
  'datetime'=>$datetime,
  'week'=>$week[date('w', strtotime($date))],
  'hizuke'=>$hizuke,
  'events'=>$events,
  'events_ka'=>$events_ka,
  'events_en'=>$events_en,
  'events_other'=>$events_other
);
$jsonstr=json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo $jsonstr;
?>