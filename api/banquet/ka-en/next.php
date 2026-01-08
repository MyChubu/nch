<?php
// ▼ 開発中のエラー出力を有効にする（本番環境では無効化すること）
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

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

$now = new DateTime();
$date = $now->format('Y-m-d');
$w = $now->format('w');
$wd = $week[$w];
$hour = (int)$now->format('H');


if ($hour >= 18) {
  $date = $now->modify('+2 day')->format('Y-m-d');
}else{
  $date = $now->modify('+1 day')->format('Y-m-d');
}
#$date = '2025-03-15';
$dateObj = clone $now;
$hizuke = $dateObj->format('Y年m月d日');
$week = array('日', '月', '火', '水', '木', '金', '土');
$hizuke .= '（' . $week[(int)$dateObj->format('w')] . '）';

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = 'select * from banquet_schedules where date = ? and additional_sales = ?  order by start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$date, 0]);
$count = $stmt->rowCount();
$events=array();
$events_en=array();
$events_ka=array();
$events_other=array();
$amount_en=0;
$amount_ka=0;

if($count >0){
  foreach ($stmt as $row) {
    if($row['status'] != 4 && $row['status'] != 5){
      $reservation_id = $row['reservation_id'];
      $branch = $row['branch'];
      $event_date = (new DateTime($row['date']))->format('Y/m/d');
      $event_start = (new DateTime($row['start']))->format('H:i');
      $event_end = (new DateTime($row['end']))->format('H:i');
      $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
      $stmt2 = $dbh->prepare($sql2);
      $stmt2->execute([$row['room_id']]);
      $room = $stmt2->fetch();
      $floor = $room['floor'];
      $purpose_id = $row['purpose_id'];
      $sql3 = 'select * from banquet_purposes where banquet_purpose_id = ?';
      $stmt3 = $dbh->prepare($sql3);
      $stmt3->execute([$purpose_id]);
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
      if($agent_name == " ") {
        $agent_name = "";
      }
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

      //料金取得
      //宴会料理
      $meal=array();
      $drink1=array();
      $drink2=array();
  


      if($purpose_id == 35){
        $meal[] =array(
          'name' => '朝食バイキング',
          'short_name' => '朝バ',
          'unit_price' => 1100,
          'qty' => $row['people'],
          'amount_gross' => 1100 * $row['people']
        );
      }

      $sql7= 'select * from `view_package_charges` where `reservation_id` = ? AND `branch`= ? ';
      $stmt7 = $dbh->prepare($sql7);
      $stmt7->execute([$reservation_id, $branch]);
      $f_count = $stmt7->rowCount();
      if($f_count > 0){
        foreach ($stmt7 as $row7) {
          $package_name = mb_convert_kana($row7['NameShort'], "KVas");
          $unit_price = intval($row7['UnitP']);
          $qty = intval($row7['Qty']);
          $amount_gross = intval($row7['Gross']);
          $amount_net = $row7['Net'];
          $service_fee = $row7['ServiceFee'];
          $tax = $row7['Tax'];
          $discount_name = '';
          $discount_rate = 0;
          $discount_amount = $row7['Discount'];
          $meal[] =array(
            'name' => $package_name,
            'short_name' => $package_name,
            'unit_price' => $unit_price,
            'qty' => $qty,
            'amount_gross' => $amount_gross,
          );
        }
      }

      //パッケージ以外の料理
      $sql8= 'select * from `view_charges` where `reservation_id` = ? AND `branch`= ?  AND `meal` = 1 AND (`package_id` = "" OR `package_id` IS NULL OR `package_id` = " ")';
      $stmt8 = $dbh->prepare($sql8);
      $stmt8->execute([$reservation_id, $branch]);
      $f_count = $stmt8->rowCount();
      if($f_count > 0){
        foreach ($stmt8 as $row8) {
          $item_name = mb_convert_kana($row8['item_name'], "KVas");
          $short_name = mb_convert_kana($row8['name_short'], "KVas");
          $unit_price = $row8['unit_price'];
          $qty = $row8['qty'];
          $amount_gross = $row8['amount_gross'];
          $amount_net = $row8['amount_net'];
          $meal[] =array(
            'name' => $item_name,
            'short_name' => $short_name,
            'unit_price' => $unit_price,
            'qty' => $qty,
            'amount_gross' => $amount_gross,
          );
        }
      }

      //飲み放題
      $sql8= 'select * from `view_charges` where `reservation_id` = ? AND `branch`= ?  AND `drink1` = 1';
      $stmt8 = $dbh->prepare($sql8);
      $stmt8->execute([$reservation_id, $branch]);
      $f_count = $stmt8->rowCount();
      if($f_count > 0){
        foreach ($stmt8 as $row8) {
          $item_name = mb_convert_kana($row8['item_name'], "KVas");
          $short_name = mb_convert_kana($row8['name_short'], "KVas");
          $unit_price = $row8['unit_price'];
          $qty = $row8['qty'];
          $amount_gross = $row8['amount_gross'];
          $amount_net = $row8['amount_net'];
          $drink1[] =array(
            'name' => $item_name,
            'short_name' => $short_name,
            'unit_price' => $unit_price,
            'qty' => $qty,
            'amount_gross' => $amount_gross,
          );
        }
      }

      //会議ドリンク
      $sql8= 'select * from `view_charges` where `reservation_id` = ? AND `branch`= ? AND `drink2` = 1';
      $stmt8 = $dbh->prepare($sql8);
      $stmt8->execute([$reservation_id, $branch]);
      $f_count = $stmt8->rowCount();
      if($f_count > 0){
        foreach ($stmt8 as $row8) {
          $item_name = mb_convert_kana($row8['item_name'], "KVas");
          $short_name = mb_convert_kana($row8['name_short'], "KVas");
          $unit_price = $row8['unit_price'];
          $qty = $row8['qty'];
          $amount_gross = $row8['amount_gross'];
          $amount_net = $row8['amount_net'];
          $drink2[] =array(
            'name' => $item_name,
            'short_name' => $short_name,
            'unit_price' => $unit_price,
            'qty' => $qty,
            'amount_gross' => $amount_gross,
          );
        }
      }

      //会場料金
      $sql9 = 'select * from `view_charges` where `reservation_id` = ? AND `branch`= ? AND`room_charge` = 1';
      $stmt9 = $dbh->prepare($sql9);
      $stmt9->execute([$reservation_id, $branch]);
      $r_count = $stmt9->rowCount();
      if($r_count > 0){
        foreach ($stmt9 as $row9) {
          $item_name = mb_convert_kana($row9['item_name'], "KVas");
          $unit_price = $row9['unit_price'];
          $qty = $row9['qty'];
          $amount_gross = $row9['amount_gross'];
          $amount_net = $row9['amount_net'];
          $amount_discount = $row9['discount_amount'];
        }
      }

      //料金合計
      $sql10 = "select * from `view_amounts` where `reservation_id` = ? AND `branch`= ?";
      $stmt10 = $dbh->prepare($sql10);
      $stmt10->execute([$reservation_id, $branch]);
      $a_count = $stmt10->rowCount();
      if($a_count > 0){
        foreach ($stmt10 as $row10) {
          $gross = $row10['gross'];
          $net = $row10['net'];
          $discount = $row10['discount'];
          $tax = $row10['tax'];
        }
       
      }

      $event_name = mb_convert_kana($row['event_name'], "KVas");
      $event_name = str_replace('///', ' ', $event_name);

      $events[] = array(
        'reservation_id' => $reservation_id,
        'branch' => $branch,
        'banquet_schedule_id' => $row['banquet_schedule_id'],
        'event_name' => $event_name,
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
        'enable' => $enable,
        'meal' => $meal,
        'drink1' => $drink1,
        'drink2' => $drink2
      );
      
      if($summary_category == 1 ){
        if($row['purpose_id'] !=0 && $row['purpose_id'] !=88 && $row['purpose_id'] !=94){


          $events_ka[] = array(
            'reservation_id' => $reservation_id,
            'branch' => $branch,
            'banquet_schedule_id' => $row['banquet_schedule_id'],
            'event_name' => $event_name,
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
            'enable' => $enable,
            'meal' => $meal,
            'drink1' => $drink1,
            'drink2' => $drink2
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
                'reservation_id' => $reservation_id,
                'branch' => $branch,
                'banquet_schedule_id' => $row['banquet_schedule_id'],
                'event_name' => $event_name,
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
                'enable' => $enable,
                'meal' => $meal,
                'drink1' => $drink1,
                'drink2' => $drink2
                );
            }
          }
        }
      }elseif($summary_category == 2 ){

  
        $events_en[] = array(
          'reservation_id' => $reservation_id,
          'branch' => $branch,
          'banquet_schedule_id' => $row['banquet_schedule_id'],
          'event_name' => $event_name,
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
          'enable' => $enable,
          'meal' => $meal,
          'drink1' => $drink1,
          'drink2' => $drink2
        );
      }
    }
  }
  $sql9 = 'select `summary_category`, sum(`amount_gross`) as `gross` from `view_purpose_charges` where `date` = ?  group by `summary_category` order by `summary_category` ASC';
  $stmt9 = $dbh->prepare($sql9);
  $stmt9->execute([$date]);
  $f_count = $stmt9->rowCount();
  if($f_count > 0){
    foreach ($stmt9 as $row9) {
      $summary_category = $row9['summary_category'];
      $gross = $row9['gross'];
      if($summary_category == 1){
        $amount_ka += intval($gross);
      }elseif($summary_category == 2){
        $amount_en += intval($gross);
      }
    }
  }
}
$data=array(
  'status'=>200,
  'message'=>'OK',
  'date'=>$date,
  'week' => $week[(int)$dateObj->format('w')],
  'hizuke'=>$hizuke,
  'events'=>$events,
  'events_ka'=>$events_ka,
  'events_en'=>$events_en,
  'events_other'=>$events_other,
  'amount_ka'=>$amount_ka,
  'amount_en'=>$amount_en
);
$jsonstr=json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo $jsonstr;
?>