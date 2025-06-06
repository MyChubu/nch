<?php
function getBanquetEvents($date) {
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
  $week = array('日', '月', '火', '水', '木', '金', '土');
  $sql = 'select * from banquet_schedules where date = ?  order by start ASC, branch ASC';
  $stmt = $dbh->prepare($sql);
  $stmt->execute([$date]);
  $count = $stmt->rowCount();
  $events=array();
  if($count >0){
    foreach ($stmt as $row) {
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
      $sql4 = 'select * from banquet_categories where banquet_category_id = ?';
      $stmt4 = $dbh->prepare($sql4);
      $stmt4->execute([$banquet_category_id]);
      $category = $stmt4->fetch();
      $category_name = $category['banquet_category_name'];

      $events[] = array(
        'reservation_id' => $row['reservation_id'],
        'banquet_schedule_id' => $row['banquet_schedule_id'],
        'event_name' => $row['event_name'],
        'date' => $event_date . '(' . $week[date('w', strtotime($row['date']))] . ')',
        'start' => $event_start,
        'end' => $event_end,
        'room_name' => $row['room_name'],
        'floor' => $floor,
        'status' => $row['status'],
        'status_name' => $row['status_name'],
        'purpose_id' => $row['purpose_id'],
        'purpose_name' => $purpose_name,
        'purpose_short' => $purpose_short,
        'category_id' => $banquet_category_id,
        'category_name' => $category_name,
        'enable' => $row['enable'],
        'added' => date('Y/m/d ', strtotime($row['added'])),
        'modified' => date('Y/m/d ', strtotime($row['modified'])),
        'modified_by' => $row['modified_by']
      );
    }
  }
  return $events;
}

function getKaEnList($date){
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
  $week = array('日', '月', '火', '水', '木', '金', '土');
  $sql = 'select * from banquet_schedules where date = ?  order by start ASC, branch ASC';
  $stmt = $dbh->prepare($sql);
  $stmt->execute([$date]);
  $count = $stmt->rowCount();
  $events=array();
  $events_en=array();
  $events_ka=array();
  $events_other=array();
  $amount_en = 0;
  $amount_ka = 0;
  if($count >0){
    foreach ($stmt as $row) {
      $reservation_id = $row['reservation_id'];
      $branch = $row['branch'];
      $event_date = date('Y/m/d ', strtotime($row['date']));
      $event_start = date('H:i', strtotime($row['start']));
      $event_end = date('H:i', strtotime($row['end']));
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
      $agent_id = $row['agent_id'];
      $agent_name = $row['agent_name'];
      if($agent_name == " ") {
        $agent_name = "";
      }
      $agent_group = $row['agent_group'];
      $agent_group_short = $row['agent_group_short'];
      $reserver = $row['reserver'];
      if($agent_id > 0){
        $sql6 = 'select * from banquet_agents where agent_id = ?';
        $stmt6 = $dbh->prepare($sql6);
        $stmt6->execute([$agent_id]);
        $agent = $stmt6->fetch();
        $agent_group = $agent['agent_group'];
        $agent_group_short = $agent['agent_group_short'];
      }

      //料金取得
      //宴会料理
      $meal=array();
      $drink1=array();
      $drink2=array();
      $ammounts=array();
      $rc=array();

      if($purpose_id == 35){
        $meal[] =array(
          'name' => '朝食バイキング',
          'short_name' => '朝バ',
          'unit_price' => 1100,
          'qty' => $row['people'],
          'amount_gross' => 1100 * $row['people']
        );
      }
      $sql7= 'select * from `view_package_charges` where `reservation_id` = ? AND `branch`= ?';
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
      $sql8= 'select * from `view_charges` where `reservation_id` = ? AND `branch`= ?  AND `meal` = 1 AND (`package_id` = "" OR `package_id` IS NULL OR `package_id` = " ") ORDER BY `detail_number` ASC';
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

      $events[] = array(
        'reservation_id' => $reservation_id,
        'branch' => $branch,
        'banquet_schedule_id' => $row['banquet_schedule_id'],
        'event_name' => $row['event_name'],
        'date' => $event_date . '(' . $week[date('w', strtotime($row['date']))] . ')',
        'start' => $event_start,
        'end' => $event_end,
        'people' => $row['people'],
        'room_name' => $row['room_name'],
        'floor' => $floor,
        'status' => $row['status'],
        'status_name' => $row['status_name'],
        'purpose_id' => $purpose_id,
        'purpose_name' => $purpose_name,
        'purpose_short' => $purpose_short,
        'category_id' => $banquet_category_id,
        'category_name' => $category_name,
        'summary_category' => $summary_category,
        'pic' => mb_convert_kana($row['pic'], 'KVas'),
        'agent_id' => $agent_id,
        'agent_name' => $agent_name,
        'agent_group' => $agent_group,
        'agent_group_short' => $agent_group_short,
        'reserver' => $reserver,
        'enable' => $row['enable'],
        'added' => date('Y/m/d ', strtotime($row['added'])),
        'modified' => date('Y/m/d ', strtotime($row['modified'])),
        'modified_by' => $row['modified_by'],
        'meal' => $meal,
        'drink1' => $drink1,
        'drink2' => $drink2,
      );
      if($summary_category == 1){
        if($purpose_id !=0 && $purpose_id !=88 && $purpose_id !=94){
          $events_ka[] = array(
            'reservation_id' => $reservation_id,
            'branch' => $branch,
            'banquet_schedule_id' => $row['banquet_schedule_id'],
            'event_name' => $row['event_name'],
            'date' => $event_date . '(' . $week[date('w', strtotime($row['date']))] . ')',
            'start' => $event_start,
            'end' => $event_end,
            'people' => $row['people'],
            'room_name' => $row['room_name'],
            'floor' => $floor,
            'status' => $row['status'],
            'status_name' => $row['status_name'],
            'purpose_id' => $purpose_id,
            'purpose_name' => $purpose_name,
            'purpose_short' => $purpose_short,
            'category_id' => $banquet_category_id,
            'category_name' => $category_name,
            'summary_category' => $summary_category,
            'pic' => mb_convert_kana($row['pic'], 'KVas'),
            'agent_id' => $agent_id,
            'agent_name' => $agent_name,
            'agent_group' => $agent_group,
            'agent_group_short' => $agent_group_short,
            'reserver' => $reserver,
            'enable' => $row['enable'],
            'added' => date('Y/m/d ', strtotime($row['added'])),
            'modified' => date('Y/m/d ', strtotime($row['modified'])),
            'modified_by' => $row['modified_by'],
            'meal' => $meal,
            'drink1' => $drink1,
            'drink2' => $drink2,
          );
        }else{
          $events_other[] = array(
            'reservation_id' => $reservation_id,
            'branch' => $branch,
            'banquet_schedule_id' => $row['banquet_schedule_id'],
            'event_name' => $row['event_name'],
            'date' => $event_date . '(' . $week[date('w', strtotime($row['date']))] . ')',
            'start' => $event_start,
            'end' => $event_end,
            'people' => $row['people'],
            'room_name' => $row['room_name'],
            'floor' => $floor,
            'status' => $row['status'],
            'status_name' => $row['status_name'],
            'purpose_id' => $purpose_id,
            'purpose_name' => $purpose_name,
            'purpose_short' => $purpose_short,
            'category_id' => $banquet_category_id,
            'category_name' => $category_name,
            'summary_category' => $summary_category,
            'pic' => mb_convert_kana($row['pic'], 'KVas'),
            'agent_id' => $agent_id,
            'agent_name' => $agent_name,
            'agent_group' => $agent_group,
            'agent_group_short' => $agent_group_short,
            'reserver' => $reserver,
            'enable' => $row['enable'],
            'added' => date('Y/m/d ', strtotime($row['added'])),
            'modified' => date('Y/m/d ', strtotime($row['modified'])),
            'modified_by' => $row['modified_by'],
            'meal' => $meal,
            'drink1' => $drink1,
            'drink2' => $drink2,
          );

        }
      }elseif($summary_category == 2 ){
        $events_en[] = array(
          'reservation_id' => $reservation_id,
          'branch' => $branch,
          'banquet_schedule_id' => $row['banquet_schedule_id'],
          'event_name' => $row['event_name'],
          'date' => $event_date . '(' . $week[date('w', strtotime($row['date']))] . ')',
          'start' => $event_start,
          'end' => $event_end,
          'people' => $row['people'],
          'room_name' => $row['room_name'],
          'floor' => $floor,
          'status' => $row['status'],
          'status_name' => $row['status_name'],
          'purpose_id' => $purpose_id,
          'purpose_name' => $purpose_name,
          'purpose_short' => $purpose_short,
          'category_id' => $banquet_category_id,
          'category_name' => $category_name,
          'summary_category' => $summary_category,
          'pic' => mb_convert_kana($row['pic'], 'KVas'),
          'agent_id' => $agent_id,
          'agent_name' => $agent_name,
          'agent_group' => $agent_group,
          'agent_group_short' => $agent_group_short,
          'reserver' => $reserver,
          'enable' => $row['enable'],
          'added' => date('Y/m/d ', strtotime($row['added'])),
          'modified' => date('Y/m/d ', strtotime($row['modified'])),
          'modified_by' => $row['modified_by'],
          'meal' => $meal,
          'drink1' => $drink1,
          'drink2' => $drink2,
        );
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
  $array=array(
    'events' => $events,
    'events_en' => $events_en,
    'events_ka' => $events_ka,
    'events_other' => $events_other,
    'amount_en' => $amount_en,
    'amount_ka' => $amount_ka
  );
  return $array;
}

function getConnectionList($reservation_id){
  $week = array('日', '月', '火', '水', '木', '金', '土');
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
  $sql = 'select * from banquet_schedules where reservation_id = ? order by start ASC, branch ASC';
  $stmt = $dbh->prepare($sql);
  $stmt->execute([$reservation_id]);
  $count = $stmt->rowCount();
  $events=array();
  $charges = array();
  $total_amount = 0;
  $service_amount = 0;
  $tax_amount = 0;
  $discount_amount = 0;
  $subtotal_amount = 0;
  if($count >0){
    foreach ($stmt as $row) {
      $sche_id= $row['banquet_schedule_id'];
      $branch = $row['branch'];
      $pic = mb_convert_kana($row['pic'], 'KVas');
      $event_date = date('Y/m/d ', strtotime($row['date']));
      $event_start = date('H:i', strtotime($row['start']));
      $event_end = date('H:i', strtotime($row['end']));
      $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
      $stmt2 = $dbh->prepare($sql2);
      $stmt2->execute([$row['room_id']]);
      $room = $stmt2->fetch();
      $floor = $room['floor'];
      $events[] = array(
        'banquet_schedule_id' => $sche_id,
        'reservation_id' => $reservation_id,
        'branch' => $branch,
        'resevation_name' => mb_convert_kana($row['reservation_name'], 'KVas'),
        'event_name' => mb_convert_kana($row['event_name'],'KVas'),
        'event_date' => $event_date,
        'date' => $event_date . '(' . $week[date('w', strtotime($row['date']))] . ')',
        'start' => $event_start,
        'end' => $event_end,
        'room_name' => $row['room_name'],
        'floor' => $floor,
        'status_name' => mb_convert_kana($row['status_name'], 'KVas'),
        'enable' => $row['enable'],
        'pic' => $pic,
      );

      // 料金取得
      //パック料金
      
      $sql4 = 'select * from view_package_charges where reservation_id = ? and branch = ?';
      $stmt4 = $dbh->prepare($sql4);
      $stmt4->execute([$reservation_id, $branch]);
      $p_count = $stmt4->rowCount();

      if($p_count > 0){
        foreach ($stmt4 as $row4) {
          $package_category = $row4['package_category'];
          $package_name_short = mb_convert_kana($row4['NameShort'], "KVas");
          $package_name = mb_convert_kana($row4['PackageName2'], "KVas");
          $package_id = $row4['package_id'];
          $unit_price = intval($row4['UnitP']);
          $qty = intval($row4['Qty']);
          $subtotal = intval($unit_price) * intval($qty);
          $service_fee = $row4['ServiceFee'];
          $tax = $row4['Tax'];
          $discount = $row4['Discount'];
          $gross = $row4['Gross'];
          $total_amount += intval($gross);
          $service_amount += intval($service_fee);
          $tax_amount += intval($tax);
          $discount_amount += intval($discount);
          $subtotal_amount += $subtotal;
          $charges[] = array(
            'reservation_id' => $reservation_id,
            'branch' => $branch,
            'date' => $event_date,
            'item_group_id' => $package_category,
            'item_group_name' => $package_name,
            'item_id' => $package_id,
            'item_name' => $package_name_short,
            'name_short' => $package_name_short,
            'unit_price' => $unit_price,
            'qty' => $qty,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'service_fee' => $service_fee,
            'tax' => $tax,
            'gross' => $gross
          );
        }
      }

      //パック料金以外
      $sql3 = "select * from view_charges
        where reservation_id = ?
        and branch = ?
        and item_group_id NOT LIKE 'X%' 
        and package_category NOT LIKE 'F%'";
      $stmt3 = $dbh->prepare($sql3);
      $stmt3->execute([$reservation_id, $branch]);
      $count2 = $stmt3->rowCount();
      if($count2 > 0){
        foreach ($stmt3 as $row2) {
          $item_group_id = $row2['item_group_id'];
          $item_group_name = $row2['item_group_name'];
          $item_id = $row2['item_id'];
          $item_name = $row2['item_name'];
          $name_short = $row2['name_short'];
          $unit_price = $row2['unit_price'];
          $qty= $row2['qty'];
          $subtotal = intval($unit_price) * intval($qty);
          $service_fee = $row2['service_fee'];
          $tax = $row2['tax'];
          $discount = $row2['discount_amount'];
          $gross = $row2['amount_gross'];
          $total_amount += intval($gross);
          $service_amount += intval($service_fee);
          $tax_amount += intval($tax);
          $discount_amount += intval($discount);
          $subtotal_amount += $subtotal;
          $charges[] = array(
            'reservation_id' => $reservation_id,
            'branch' => $branch,
            'date' => $event_date,
            'item_group_id' => $item_group_id,
            'item_group_name' => mb_convert_kana($item_group_name, 'KVas'),
            'item_id' => $item_id,
            'item_name' => mb_convert_kana($item_name, 'KVas'),
            'name_short' => mb_convert_kana($name_short, 'KVas'),
            'unit_price' => $unit_price,
            'qty' => $qty,
            'subtotal' => $subtotal,
            'service_fee' => $service_fee,
            'tax' => $tax,
            'discount' => $discount,
            'gross' => $gross,
          );
        }
      }
    }
  }
  $array=array(
    'events' => $events,
    'charges' => $charges,
    'total_amount' => $total_amount,
    'service_amount' => $service_amount,
    'tax_amount' => $tax_amount,
    'discount_amount' => $discount_amount,
    'subtotal_amount' => $subtotal_amount
  );
  return $array;
}

function getDetail($scheid){
  $week = array('日', '月', '火', '水', '木', '金', '土');
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
  $sql = 'select * from banquet_schedules where banquet_schedule_id = ?';
  $stmt = $dbh->prepare($sql);
  $stmt->execute([$scheid]);
  $data = $stmt->fetch();
  $reservation_id = $data['reservation_id'];
  $reservation_name = mb_convert_kana($data['reservation_name'], 'KVas');
  $branch = $data['branch'];
  $event_name = mb_convert_kana($data['event_name'], 'KVas');
  $date=$data['date'];

  $event_date = date('Y/m/d ', strtotime($data['date'])) . '(' . $week[date('w', strtotime($data['date']))] . ')';
  $start = date('H:i', strtotime($data['start']));
  $end = date('H:i', strtotime($data['end']));
  $room_id = $data['room_id'];
  $room_name = $data['room_name'];
  $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
  $stmt2 = $dbh->prepare($sql2);
  $stmt2->execute([$room_id]);
  $room = $stmt2->fetch();
  $floor = $room['floor'];
  $pic = mb_convert_kana($data['pic'], 'KVas');
  $enable = $data['enable'];
  $people = $data['people'];
  $status = mb_convert_kana($data['status_name'], 'KVas');
  $purpose_id = $data['purpose_id'];
  $sql3 = 'select * from banquet_purposes where banquet_purpose_id = ?';
  $stmt3 = $dbh->prepare($sql3);
  $stmt3->execute([$purpose_id]);
  $purpose = $stmt3->fetch();
  $purpose_name = $purpose['banquet_purpose_name'];
  $layout_id = $data['layout_id'];
  $sql5 = 'select * from banquet_layouts where layout_id = ?';
  $stmt5 = $dbh->prepare($sql5);
  $stmt5->execute([$layout_id]);
  $layout = $stmt5->fetch();
  $layout_name = $layout['layout_name'];
  $layout_name2 = $data['layout_name'];
  $banquet_category_id = $purpose['banquet_category_id'];
  $sql4 = 'select * from banquet_categories where banquet_category_id = ?';
  $stmt4 = $dbh->prepare($sql4);
  $stmt4->execute([$banquet_category_id]);
  $category = $stmt4->fetch();
  $category_name = $category['banquet_category_name'];
  $agent_id = $data['agent_id'];
  $agent_name = $data['agent_name'];
  $agent_group = '';
  $agent_group_short = '';
  if($agent_id > 0){
    $sql6 = 'select * from banquet_agents where agent_id = ?';
    $stmt6 = $dbh->prepare($sql6);
    $stmt6->execute([$agent_id]);
    $agent = $stmt6->fetch();
    $agent_group = $agent['agent_group'];
    $agent_group_short = $agent['agent_group_short'];
  }
  $reserver = mb_convert_kana($data['reserver'], 'KVas');

  $detail = array(
    '管理ID' => $scheid,
    'NEHOPS予約ID' => $reservation_id,
    '予約名' => $reservation_name,
    '明細枝番' => $branch,
    '行灯名称' => $event_name,
    '利用日' => $date,
    '開始時間' => $start,
    '終了時間' => $end,
    '人数' => $people,
    '会場名' => $room_name,
    '会場ID' => $room_id,
    'フロア' => $floor,
    '営業担当' => $pic,
    'エージェントID' => $agent_id,
    'エージェント名' => $agent_name,
    'エージェントG' => $agent_group,
    'エージェントG(S)' => $agent_group_short,
    '申込会社' => $reserver,
    'ステータス' => $status,
    '使用目的' => $purpose_name,
    '目的ID' => $purpose_id,
    'カテゴリ' => $category_name,
    'レイアウト' => $layout_name,
    'レイアウトID' => $layout_id,
    'デジサイ表示' => $enable
  );

  // 料金情報
  $charge = array();
  $total_amount = 0;
  $service_amount = 0;
  $tax_amount = 0;
  $discount_amount = 0;
  $subtotal_amount = 0;
  // パック料金
  $sql6 = 'select * from view_package_charges where reservation_id = ? and branch = ?';
  $stmt6 = $dbh->prepare($sql6);
  $stmt6->execute([$reservation_id, $branch]);
  $f_count = $stmt6->rowCount();
  if($f_count > 0){
    foreach ($stmt6 as $row6) {
      $package_category = $row6['package_category'];
      $package_name_short = mb_convert_kana($row6['NameShort'], "KVas");
      $package_name = mb_convert_kana($row6['PackageName2'], "KVas");
      $package_id = $row6['package_id'];
      $unit_price = intval($row6['UnitP']);
      $qty = intval($row6['Qty']);
      $subtotal = intval($unit_price) * intval($qty);
      $service_fee = $row6['ServiceFee'];
      $tax = $row6['Tax'];
      $discount = $row6['Discount'];
      $gross = $row6['Gross'];
      $charge[] = array(
        'reservation_id' => $reservation_id,
        'branch' => $branch,
        'item_group_id' => $package_id,
        'item_group_name' => $package_name,
        'item_id' => $package_id,
        'item_name' => $package_name_short,
        'unit_price' => number_format($unit_price),
        'qty' => number_format($qty),
        'subtotal' => number_format($subtotal),
        'discount' => number_format($discount),
        'service_fee' => number_format($service_fee),
        'tex' => number_format($tax),
        'gross' => number_format($gross)
      );
      $total_amount += intval($gross);
      $service_amount += intval($service_fee);
      $tax_amount += intval($tax);
      $discount_amount += intval($discount);
      $subtotal_amount += $subtotal;
    }
  }
  //パッケージ以外の料理
  $sql7 = 'select * from view_charges
    where `reservation_id` = ?
    and `branch` = ?
    and item_group_id NOT LIKE \'X%\'
    and package_category NOT LIKE \'F%\'';
  $stmt7 = $dbh->prepare($sql7);
  $stmt7->execute([$reservation_id, $branch]);
  $charges = $stmt7->fetchAll(PDO::FETCH_ASSOC);
  
  foreach($charges as $row){
    $item_group_id = $row['item_group_id'];
    $item_group_name = $row['item_group_name'];
    $item_id = $row['item_id'];
    $item_name = $row['item_name'];
    $name_short = $row['name_short'];
    $unit_price = $row['unit_price'];
    $qty= $row['qty'];
    $subtotal = intval($unit_price) * intval($qty);
    $service_fee = $row['service_fee'];
    $tax = $row['tax'];
    $discount = $row['discount_amount'];
    $gross = $row['amount_gross'];
    
    $charge[] = array(
      'reservation_id' => $reservation_id,
      'branch' => $branch,
      'item_group_id' => $item_group_id,
      'item_group_name' => $item_group_name,
      'item_id' => $item_id,
      'item_name' => $item_name,
      'unit_price' => number_format($unit_price),
      'qty' => number_format($qty),
      'subtotal' => number_format($subtotal),
      'discount' => number_format($discount),
      'service_fee' => number_format($service_fee),
      'tex' => number_format($tax),
      'gross' => number_format($gross)
    );
    $total_amount += intval($gross);
    $service_amount += intval($service_fee);
    $tax_amount += intval($tax);
    $discount_amount += intval($discount);
    $subtotal_amount += $subtotal;
  }
  $array=array(
    'detail' => $detail,
    'charges' => $charge,
    'total_amount' => $total_amount,
    'service_amount' => $service_amount,
    'tax_amount' => $tax_amount,
    'discount_amount' => $discount_amount,
    'subtotal_amount' => $subtotal_amount
  );
  return $array;
}

function getMonthlySales($ym){
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
  #$week = array( "日", "月", "火", "水", "木", "金", "土" );
  $last_day = date('t', strtotime($ym));
  $year_month = date('Y年 m月', strtotime($ym));

  $sql = "SELECT * FROM `view_daily_subtotal` where `ym` = :ym and `purpose_id` <> 3";
  $stmt = $dbh->prepare($sql); 
  $stmt->bindValue(':ym', $ym, PDO::PARAM_STR);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $s_count = count($rows);

  $sales = array();
  $total_enkai = 0;
  $total_kaigi = 0;
  $total_shokuji = 0;
  $total_others = 0;
  $total = 0;
  if($s_count > 0) {
    foreach($rows as $row) {
      $room_id = $row['room_id'];
      $date = $row['date'];
      $reservation_name = cleanLanternName($row['reservation_name']);
      $people = $row['people'];
      $banquet_category_id = $row['banquet_category_id'];
      $start= $row['start'];
      $end = $row['end'];
      $gross = $row['gross'];
      $ex_ts = $row['ex-ts'];
      $sales[] = array(
        'room_id' => $room_id,
        'date' => $date,
        'reservation_name' => $reservation_name,
        'banquet_category_id' => $banquet_category_id,
        'people' => $people,
        'start' => $start,
        'end' => $end,
        'gross' => $gross,
        'ex_ts' => $ex_ts
      );
      $total += $ex_ts;
      if($banquet_category_id == 1) {
        $total_kaigi += $ex_ts;
      } elseif($banquet_category_id == 2) {
        $total_enkai += $ex_ts;
      } elseif($banquet_category_id == 3) {
        $total_shokuji += $ex_ts;
      } elseif($banquet_category_id == 9) {
        $total_others += $ex_ts;
      }
    }
  } 
  $rooms = array();
  $sql = "SELECT * FROM `banquet_rooms` WHERE `cal` = 1 ORDER BY `order` DESC, `banquet_room_id` ASC";
  $stmt = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  $count = count($stmt);
  if($count == 0) {
    echo "会場が登録されていません。<br>";
    exit;
  }
  foreach($stmt as $row) {
    $room_id = $row['banquet_room_id'];
    $room_name = $row['name'];
    $floor = $row['floor'];

    $rooms[]= array(
      'room_id' => $room_id,
      'room_name' => $room_name,
      'floor' => $floor,
    );
  }
  $array =array(
    'ym' => $ym,
    'year_month' => $year_month,
    'last_day' => $last_day,
    'sales' => $sales,
    'rooms' => $rooms,
    'total_enkai' => $total_enkai,
    'total_kaigi' => $total_kaigi,
    'total_shokuji' => $total_shokuji,
    'total_others' => $total_others,
    'total' => $total
  );
  return $array;
}

function getDefectList($ym){
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
  $first_day = date('Y-m-01', strtotime($ym));


  $defects = array();
  
  //営業担当者が記入されていない
  $error_name = "営業担当者未記入";
  $sql = "SELECT * FROM `banquet_schedules`
          WHERE `date` >= :first_day
          AND `status` IN (1,2,3)
          AND `pic` = ''  ORDER BY `date`,`reservation_id`,`branch`";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $count = $stmt->rowCount();
  if($count > 0) {
    foreach($results as $row) {
      $sche_id = $row['banquet_schedule_id'];
      $status = $row['status'];
      $status_name = $row['status_name'];
      $res_date = $row['date'];
      $reservation_id = $row['reservation_id'];
      $reservation_name = $row['reservation_name'];
      $reservation_name = cleanLanternName($reservation_name);
      $branch = $row['branch'];
      $purpose_id = $row['purpose_id'];
      $purpose_name = $row['purpose_name'];
      $room_id = $row['room_id'];
      $room_name = $row['room_name'];
      $pic= $row['pic'];

      $defects[] = array(
        'error_name' => $error_name,
        'sche_id' => $sche_id,
        'status' => $status,
        'status_name' => $status_name,
        'res_date' => $res_date,
        'reservation_id' => $reservation_id,
        'reservation_name' => $reservation_name,
        'branch' => $branch,
        'purpose_id' => $purpose_id,
        'purpose_name' => $purpose_name,
        'room_id' => $room_id,
        'room_name' => $room_name,
        'pic' => $pic
      );
    }
  }

  //決定予約・仮予約で目的がおかしいもの
  $error_name="決定・仮で目的が不正";
  $sql = "SELECT * FROM `banquet_schedules`
          WHERE `date` >= :first_day
          AND `status` IN (1,2)
          AND `purpose_id` IN (0,3,88,93,94)  ORDER BY `date`,`reservation_id`,`branch`";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $count = $stmt->rowCount();
  if($count > 0) {
    foreach($results as $row) {
      $sche_id = $row['banquet_schedule_id'];
      $status = $row['status'];
      $status_name = $row['status_name'];
      $res_date = $row['date'];
      $reservation_id = $row['reservation_id'];
      $reservation_name = $row['reservation_name'];
      $reservation_name = cleanLanternName($reservation_name);
      $branch = $row['branch'];
      $purpose_id = $row['purpose_id'];
      $purpose_name = $row['purpose_name'];
      $room_id = $row['room_id'];
      $room_name = $row['room_name'];
      $pic= $row['pic'];

      $defects[] = array(
        'error_name' => $error_name,
        'sche_id' => $sche_id,
        'status' => $status,
        'status_name' => $status_name,
        'res_date' => $res_date,
        'reservation_id' => $reservation_id,
        'reservation_name' => $reservation_name,
        'branch' => $branch,
        'purpose_id' => $purpose_id,
        'purpose_name' => $purpose_name,
        'room_id' => $room_id,
        'room_name' => $room_name,
        'pic' => $pic
      );
    }
  } 
  //営業押さえで目的がおかしいもの
  $error_name="営業押さえで目的が不正";
  $sql = "SELECT * FROM `banquet_schedules`
          WHERE `date` >= :first_day
          AND `status` IN (3)
          AND `purpose_id` NOT IN (0,3,88,93,94) ORDER BY `date`,`reservation_id`,`branch`";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $count = $stmt->rowCount();
  if($count > 0) {
    foreach($results as $row) {
      $sche_id = $row['banquet_schedule_id'];
      $status = $row['status'];
      $status_name = $row['status_name'];
      $res_date = $row['date'];
      $reservation_id = $row['reservation_id'];
      $reservation_name = $row['reservation_name'];
      $reservation_name = cleanLanternName($reservation_name);
      $branch = $row['branch'];
      $purpose_id = $row['purpose_id'];
      $purpose_name = $row['purpose_name'];
      $room_id = $row['room_id'];
      $room_name = $row['room_name'];
      $pic= $row['pic'];

      $defects[] = array(
        'error_name' => $error_name,
        'sche_id' => $sche_id,
        'status' => $status,
        'status_name' => $status_name,
        'res_date' => $res_date,
        'reservation_id' => $reservation_id,
        'reservation_name' => $reservation_name,
        'branch' => $branch,
        'purpose_id' => $purpose_id,
        'purpose_name' => $purpose_name,
        'room_id' => $room_id,
        'room_name' => $room_name,
        'pic' => $pic
      );
    }
  }

  //決定予約・仮予約で金額データがないもの
  $error_name="決定・仮で明細なし";
  $sql = "SELECT *  FROM `view_daily_subtotal` WHERE `status` IN (1,2) AND `date` >= :first_day AND `gross` IS NULL ORDER BY `date`,`reservation_id`,`branch`";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $count = $stmt->rowCount();
  if($count > 0) {
    foreach($results as $row) {
      $sche_id = $row['sche_id'];
      $status = $row['status'];
      $status_name = $row['status_name'];
      $res_date = $row['date'];
      $reservation_id = $row['reservation_id'];
      $reservation_name = $row['reservation_name'];
      $reservation_name = cleanLanternName($reservation_name);
      $branch = $row['branch'];
      $purpose_id = $row['purpose_id'];
      $purpose_name = $row['purpose_short'];
      $room_id = $row['room_id'];
      $room_name = $row['room_name'];
      $pic= $row['pic'];

      $defects[] = array(
        'error_name' => $error_name,
        'sche_id' => $sche_id,
        'status' => $status,
        'status_name' => $status_name,
        'res_date' => $res_date,
        'reservation_id' => $reservation_id,
        'reservation_name' => $reservation_name,
        'branch' => $branch,
        'purpose_id' => $purpose_id,
        'purpose_name' => $purpose_name,
        'room_id' => $room_id,
        'room_name' => $room_name,
        'pic' => $pic
      );
    }
  }
  return $defects;
}

function cleanLanternName($name) {
  // 不要な法人名接尾辞のリスト
  $replaceWords = [
      "株式会社", "㈱", "（株）", "(株)",
      "一般社団法人", "(一社)", "（一社）",
      "公益社団法人", "(公社)", "（公社）",
      "有限会社", "㈲", "（有）", "(有)",
      "一般財団法人", "(一財)", "（一財）",
      "公益財団法人", "(公財)", "（公財）",
      "学校法人", "(学)", "（学）",
      "医療法人", "(医)", "（医）",
      "財団法人", "(財)", "（財）",
      "合同会社", "(同)", "（同）",
      "(下見)","（下見）","【下見】", "下見", "下見 ", "下見　"
  ];

  // 不要語句の削除
  foreach ($replaceWords as $word) {
      $name = str_replace($word, "", $name);
  }
  $name = str_replace("労働組合連合会", "労連", $name);
  $name = str_replace("労働組合", "労組", $name);

  // 「第〇回」の削除（半角数字／全角数字／漢数字に対応）
  $name = preg_replace("/第[0-9０-９一二三四五六七八九十百千万億兆]+回/u", "", $name);

  // 西暦年度（例: 2025年度）の削除
  $name = preg_replace("/[0-9０-９]{4}年度/u", "", $name);

  // 和暦年度（例: 令和7年度、平成31年度）の削除
  $name = preg_replace("/(令和|平成|昭和)[0-9０-９一二三四五六七八九十百千万]+年度/u", "", $name);

  // 先頭の半角・全角スペースを削除
  $name = preg_replace("/^[ 　]+/u", "", $name);

  // 最初に出てくるスペース（半角・全角）で前半だけに分ける
  $parts = preg_split("/[ 　]/u", $name, 2);  // 2つに分割（前後）
  $name = $parts[0];  // 前半部分だけ使用

  // 最初の10文字に切り詰め
  $name = mb_substr($name, 0, 10);

  return $name;
}
?>