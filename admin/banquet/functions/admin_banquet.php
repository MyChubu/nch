<?php
function getBanquetEvents($date) {
  // データベース接続
  $dbh = new PDO(DSN, DB_USER, DB_PASS);

  // 曜日配列（0=日〜6=土）
  $week = array('日', '月', '火', '水', '木', '金', '土');

  // 指定日付の宴会スケジュールを取得（開始時刻と支店順にソート）
  $sql = 'select * from banquet_schedules where date = ? order by start ASC, branch ASC';
  $stmt = $dbh->prepare($sql);
  $stmt->execute([$date]);
  $count = $stmt->rowCount();

  $events = array();

  if ($count > 0) {
    foreach ($stmt as $row) {
      // 各日時を DateTime オブジェクトとして処理
      $dateObj = new DateTime($row['date']);
      $startObj = new DateTime($row['start']);
      $endObj = new DateTime($row['end']);
      $addedObj = new DateTime($row['added']);
      $modifiedObj = new DateTime($row['modified']);

      // 会場（部屋）の情報を取得
      $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
      $stmt2 = $dbh->prepare($sql2);
      $stmt2->execute([$row['room_id']]);
      $room = $stmt2->fetch();
      $floor = $room['floor']; // 階数

      // 使用目的の情報を取得
      $sql3 = 'select * from banquet_purposes where banquet_purpose_id = ?';
      $stmt3 = $dbh->prepare($sql3);
      $stmt3->execute([$row['purpose_id']]);
      $purpose = $stmt3->fetch();
      $purpose_name = $purpose['banquet_purpose_name'];
      $purpose_short = $purpose['banquet_purpose_short'];
      $banquet_category_id = $purpose['banquet_category_id'];

      // カテゴリー情報を取得
      $sql4 = 'select * from banquet_categories where banquet_category_id = ?';
      $stmt4 = $dbh->prepare($sql4);
      $stmt4->execute([$banquet_category_id]);
      $category = $stmt4->fetch();
      $category_name = $category['banquet_category_name'];

      // イベント情報を配列にまとめる
      $events[] = array(
        'reservation_id' => $row['reservation_id'],                       // 予約ID
        'banquet_schedule_id' => $row['banquet_schedule_id'],           // スケジュールID
        'event_name' => $row['event_name'],                             // イベント名
        'date' => $dateObj->format('Y/m/d') . '(' . $week[(int)$dateObj->format('w')] . ')', // 日付 + 曜日
        'start' => $startObj->format('H:i'),                            // 開始時間
        'end' => $endObj->format('H:i'),                                // 終了時間
        'room_name' => $row['room_name'],                               // 部屋名
        'floor' => $floor,                                              // 階数
        'status' => $row['status'],                                     // ステータスコード
        'status_name' => $row['status_name'],                           // ステータス名
        'purpose_id' => $row['purpose_id'],                             // 使用目的ID
        'purpose_name' => $purpose_name,                                // 使用目的名
        'purpose_short' => $purpose_short,                              // 使用目的略称
        'category_id' => $banquet_category_id,                          // カテゴリーID
        'category_name' => $category_name,                              // カテゴリー名
        'pic' => mb_convert_kana($row['pic'], 'KVas'),                  // 担当者名（全角変換）
        'additional_sales' => $row['additional_sales'],                 // 追加売上フラグ
        'enable' => $row['enable'],                                     // 有効フラグ
        'added' => $addedObj->format('Y/m/d'),                          // 登録日
        'modified' => $modifiedObj->format('Y/m/d'),                    // 最終更新日
        'modified_by' => $row['modified_by']                            // 更新者
      );
    }
  }

  // イベント情報を返す
  return $events;
}

function getKaEnList($date){
  $dbh = new PDO(DSN, DB_USER, DB_PASS); // DB接続
  $week = array('日', '月', '火', '水', '木', '金', '土'); // 曜日配列

  // 指定日の宴会スケジュールを取得
  $sql = 'select * from banquet_schedules where date = ? order by start ASC, branch ASC';
  $stmt = $dbh->prepare($sql);
  $stmt->execute([$date]);
  $count = $stmt->rowCount();

  // 各イベント用配列
  $events = array();
  $events_en = array();     // 宴会カテゴリ（演）
  $events_ka = array();     // 宴会カテゴリ（会）
  $events_other = array();  // その他カテゴリ
  $amount_en = 0;           // 金額（演）
  $amount_ka = 0;           // 金額（会）

  if ($count > 0) {
    foreach ($stmt as $row) {
      $reservation_id = $row['reservation_id'];
      $branch = $row['branch'];

      // 日付・時間をDateTimeで処理
      $dateObj = new DateTime($row['date']);
      $startObj = new DateTime($row['start']);
      $endObj = new DateTime($row['end']);
      $addedObj = new DateTime($row['added']);
      $modifiedObj = new DateTime($row['modified']);

      // 部屋情報の取得
      $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
      $stmt2 = $dbh->prepare($sql2);
      $stmt2->execute([$row['room_id']]);
      $room = $stmt2->fetch();
      $floor = $room['floor'];

      // 使用目的の取得
      $purpose_id = $row['purpose_id'];
      $sql3 = 'select * from banquet_purposes where banquet_purpose_id = ?';
      $stmt3 = $dbh->prepare($sql3);
      $stmt3->execute([$purpose_id]);
      $purpose = $stmt3->fetch();
      $purpose_name = $purpose['banquet_purpose_name'];
      $purpose_short = $purpose['banquet_purpose_short'];
      $banquet_category_id = $purpose['banquet_category_id'];
      $summary_category = $purpose['summary_category']; // 1:会, 2:演

      // カテゴリーの取得
      $sql4 = 'select * from banquet_categories where banquet_category_id = ?';
      $stmt4 = $dbh->prepare($sql4);
      $stmt4->execute([$banquet_category_id]);
      $category = $stmt4->fetch();
      $category_name = $category['banquet_category_name'];

      // 代理店情報の初期値
      $agent_id = $row['agent_id'];
      $agent_name = trim($row['agent_name']);
      $agent_group = $row['agent_group'];
      $agent_group_short = $row['agent_group_short'];

      // 代理店IDがある場合、DBから代理店情報を取得
      if ($agent_id > 0) {
        $sql6 = 'select * from banquet_agents where agent_id = ?';
        $stmt6 = $dbh->prepare($sql6);
        $stmt6->execute([$agent_id]);
        $agent = $stmt6->fetch();
        $agent_group = $agent['agent_group'];
        $agent_group_short = $agent['agent_group_short'];
      }

      // 料理・飲料情報の初期化
      $meal = array();
      $drink1 = array();
      $drink2 = array();

      // 朝食バイキング（目的ID=35）の処理
      if ($purpose_id == 35) {
        $meal[] = array(
          'name' => '朝食バイキング',
          'short_name' => '朝バ',
          'unit_price' => 1100,
          'qty' => $row['people'],
          'amount_gross' => 1100 * $row['people']
        );
      }

      // パッケージ料理の取得
      $sql7 = 'select * from `view_package_charges` where `reservation_id` = ? AND `branch`= ?';
      $stmt7 = $dbh->prepare($sql7);
      $stmt7->execute([$reservation_id, $branch]);
      foreach ($stmt7 as $row7) {
        $meal[] = array(
          'name' => mb_convert_kana($row7['NameShort'], "KVas"),
          'short_name' => mb_convert_kana($row7['NameShort'], "KVas"),
          'unit_price' => intval($row7['UnitP']),
          'qty' => intval($row7['Qty']),
          'amount_gross' => intval($row7['Gross'])
        );
      }

      // 単品料理の取得
      $sql8 = 'select * from `view_charges` where `reservation_id` = ? AND `branch`= ? AND `meal` = 1 AND (`package_id` = "" OR `package_id` IS NULL OR `package_id` = " ") ORDER BY `detail_number` ASC';
      $stmt8 = $dbh->prepare($sql8);
      $stmt8->execute([$reservation_id, $branch]);
      foreach ($stmt8 as $row8) {
        $meal[] = array(
          'name' => mb_convert_kana($row8['item_name'], "KVas"),
          'short_name' => mb_convert_kana($row8['name_short'], "KVas"),
          'unit_price' => $row8['unit_price'],
          'qty' => $row8['qty'],
          'amount_gross' => $row8['amount_gross']
        );
      }

      //飲み放題
      $sql9= 'select * from `view_charges` where `reservation_id` = ? AND `branch`= ?  AND `drink1` = 1';
      $stmt9 = $dbh->prepare($sql9);
      $stmt9->execute([$reservation_id, $branch]);
      $f_count = $stmt9->rowCount();
      if($f_count > 0){
        foreach ($stmt9 as $row9) {
          $item_name = mb_convert_kana($row9['item_name'], "KVas");
          $short_name = mb_convert_kana($row9['name_short'], "KVas");
          $unit_price = $row9['unit_price'];
          $qty = $row9['qty'];
          $amount_gross = $row9['amount_gross'];
          $amount_net = $row9['amount_net'];
          $drink1[] =array(
            'name' => $item_name,
            'short_name' => $short_name,
            'unit_price' => $unit_price,
            'qty' => $qty,
            'amount_gross' => $amount_gross,
          );
        }
      }

      // イベントデータ（共通部分）を配列に格納
      $event_common = array(
        'reservation_id' => $reservation_id,
        'branch' => $branch,
        'banquet_schedule_id' => $row['banquet_schedule_id'],
        'event_name' => $row['event_name'],
        'date' => $dateObj->format('Y/m/d') . '(' . $week[(int)$dateObj->format('w')] . ')',
        'start' => $startObj->format('H:i'),
        'end' => $endObj->format('H:i'),
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
        'additional_sales' => $row['additional_sales'],
        'agent_id' => $agent_id,
        'agent_name' => $agent_name,
        'agent_group' => $agent_group,
        'agent_group_short' => $agent_group_short,
        'reserver' => $row['reserver'],
        'enable' => $row['enable'],
        'added' => $addedObj->format('Y/m/d'),
        'modified' => $modifiedObj->format('Y/m/d'),
        'modified_by' => $row['modified_by'],
        'meal' => $meal,
        'drink1' => $drink1,
        'drink2' => $drink2
      );

      // カテゴリごとに分類
      $events[] = $event_common;
      if ($summary_category == 1) {
        if (!in_array($purpose_id, [0, 88, 94])) {
          $events_ka[] = $event_common;
        } else {
          $events_other[] = $event_common;
        }
      } elseif ($summary_category == 2) {
        $events_en[] = $event_common;
      }
    }

    // 日別売上集計（summary_category ごと）
    $sql9 = 'select `summary_category`, sum(`amount_gross`) as `gross` from `view_purpose_charges` where `date` = ? group by `summary_category` order by `summary_category` ASC';
    $stmt9 = $dbh->prepare($sql9);
    $stmt9->execute([$date]);
    foreach ($stmt9 as $row9) {
      if ($row9['summary_category'] == 1) {
        $amount_ka += intval($row9['gross']);
      } elseif ($row9['summary_category'] == 2) {
        $amount_en += intval($row9['gross']);
      }
    }
  }

  // 最終的にまとめて返す
  return array(
    'events' => $events,
    'events_en' => $events_en,
    'events_ka' => $events_ka,
    'events_other' => $events_other,
    'amount_en' => $amount_en,
    'amount_ka' => $amount_ka
  );
}

function getConnectionList($reservation_id){
  $week = array('日', '月', '火', '水', '木', '金', '土'); // 曜日配列

  // データベース接続
  $dbh = new PDO(DSN, DB_USER, DB_PASS);

  // 宴会スケジュールを取得
  $sql = 'select * from banquet_schedules where reservation_id = ? order by start ASC, branch ASC';
  $stmt = $dbh->prepare($sql);
  $stmt->execute([$reservation_id]);
  $count = $stmt->rowCount();

  // 初期化
  $events = array();
  $charges = array();
  $total_amount = 0;
  $service_amount = 0;
  $tax_amount = 0;
  $discount_amount = 0;
  $subtotal_amount = 0;

  if($count > 0){
    foreach ($stmt as $row) {
      $sche_id = $row['banquet_schedule_id'];
      $branch = $row['branch'];
      $pic = mb_convert_kana($row['pic'], 'KVas'); // 担当者名を全角変換

      // 日付・時間をDateTimeで処理
      $dateObj = new DateTime($row['date']);
      $startObj = new DateTime($row['start']);
      $endObj = new DateTime($row['end']);
      $event_date = $dateObj->format('Y/m/d');
      $event_start = $startObj->format('H:i');
      $event_end = $endObj->format('H:i');

      // 会場（部屋）情報の取得
      $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
      $stmt2 = $dbh->prepare($sql2);
      $stmt2->execute([$row['room_id']]);
      $room = $stmt2->fetch();
      $floor = $room['floor'];

      // イベント情報を配列に追加
      $events[] = array(
        'banquet_schedule_id' => $sche_id,
        'reservation_id' => $reservation_id,
        'branch' => $branch,
        'resevation_name' => mb_convert_kana($row['reservation_name'], 'KVas'),
        'event_name' => mb_convert_kana($row['event_name'], 'KVas'),
        'event_date' => $event_date,
        'date' => $event_date . '(' . $week[(int)$dateObj->format('w')] . ')',
        'start' => $event_start,
        'end' => $event_end,
        'room_name' => $row['room_name'],
        'floor' => $floor,
        'status' => $row['status'],
        'status_name' => mb_convert_kana($row['status_name'], 'KVas'),
        'enable' => $row['enable'],
        'pic' => $pic,
      );

      // ====================
      // パッケージ料金の取得
      // ====================
      $sql4 = 'select * from view_package_charges where reservation_id = ? and branch = ?';
      $stmt4 = $dbh->prepare($sql4);
      $stmt4->execute([$reservation_id, $branch]);

      foreach ($stmt4 as $row4) {
        $unit_price = intval($row4['UnitP']);
        $qty = intval($row4['Qty']);
        $subtotal = $unit_price * $qty;
        $gross = intval($row4['Gross']);
        $service_fee = intval($row4['ServiceFee']);
        $tax = intval($row4['Tax']);
        $discount = intval($row4['Discount']);

        // 金額集計
        $total_amount += $gross;
        $service_amount += $service_fee;
        $tax_amount += $tax;
        $discount_amount += $discount;
        $subtotal_amount += $subtotal;

        // パッケージ料金を追加
        $charges[] = array(
          'reservation_id' => $reservation_id,
          'branch' => $branch,
          'date' => $event_date,
          'item_group_id' => $row4['package_category'],
          'item_group_name' => mb_convert_kana($row4['PackageName2'], 'KVas'),
          'item_id' => $row4['package_id'],
          'item_name' => mb_convert_kana($row4['NameShort'], 'KVas'),
          'name_short' => mb_convert_kana($row4['NameShort'], 'KVas'),
          'unit_price' => $unit_price,
          'qty' => $qty,
          'subtotal' => $subtotal,
          'discount' => $discount,
          'service_fee' => $service_fee,
          'tax' => $tax,
          'gross' => $gross
        );
      }

      // ========================
      // 単品（非パッケージ）料金
      // ========================
      $sql3 = "select * from view_charges
        where reservation_id = ? and branch = ?
        and item_group_id NOT LIKE 'X%' 
        and package_category NOT LIKE 'F%'";
      $stmt3 = $dbh->prepare($sql3);
      $stmt3->execute([$reservation_id, $branch]);

      foreach ($stmt3 as $row2) {
        $unit_price = intval($row2['unit_price']);
        $qty = intval($row2['qty']);
        $subtotal = $unit_price * $qty;
        $gross = intval($row2['amount_gross']);
        $service_fee = intval($row2['service_fee']);
        $tax = intval($row2['tax']);
        $discount = intval($row2['discount_amount']);

        // 金額集計
        $total_amount += $gross;
        $service_amount += $service_fee;
        $tax_amount += $tax;
        $discount_amount += $discount;
        $subtotal_amount += $subtotal;

        // 単品料金を追加
        $charges[] = array(
          'reservation_id' => $reservation_id,
          'branch' => $branch,
          'date' => $event_date,
          'item_group_id' => $row2['item_group_id'],
          'item_group_name' => mb_convert_kana($row2['item_group_name'], 'KVas'),
          'item_id' => $row2['item_id'],
          'item_name' => mb_convert_kana($row2['item_name'], 'KVas'),
          'name_short' => mb_convert_kana($row2['name_short'], 'KVas'),
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

  // 結果配列を返却
  return array(
    'events' => $events,
    'charges' => $charges,
    'total_amount' => $total_amount,
    'service_amount' => $service_amount,
    'tax_amount' => $tax_amount,
    'discount_amount' => $discount_amount,
    'subtotal_amount' => $subtotal_amount
  );
}

function getDetail($scheid) {
  // 曜日配列（0〜6: 日〜土）
  $week = array('日', '月', '火', '水', '木', '金', '土');

  // DB接続
  $dbh = new PDO(DSN, DB_USER, DB_PASS);

  // 宴会スケジュールの取得
  $sql = 'select * from banquet_schedules where banquet_schedule_id = ?';
  $stmt = $dbh->prepare($sql);
  $stmt->execute([$scheid]);
  $data = $stmt->fetch();

  // 各種基本情報の取得
  $reservation_id = $data['reservation_id'];
  $reservation_name = mb_convert_kana($data['reservation_name'], 'KVas');
  $branch = $data['branch'];
  $event_name = mb_convert_kana($data['event_name'], 'KVas');

  // 日付・時間を DateTime で処理
  $dateObj = new DateTime($data['date']);
  $startObj = new DateTime($data['start']);
  $endObj = new DateTime($data['end']);

  $event_date = $dateObj->format('Y/m/d') . '(' . $week[(int)$dateObj->format('w')] . ')';
  $start = $startObj->format('H:i');
  $end = $endObj->format('H:i');

  $room_id = $data['room_id'];
  $room_name = $data['room_name'];

  // 会場情報取得
  $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
  $stmt2 = $dbh->prepare($sql2);
  $stmt2->execute([$room_id]);
  $room = $stmt2->fetch();
  $floor = $room['floor'];

  // 担当者・その他情報
  $pic = mb_convert_kana($data['pic'], 'KVas');
  $enable = $data['enable'];
  $people = $data['people'];
  $status = mb_convert_kana($data['status_name'], 'KVas');

  // 使用目的の取得
  $purpose_id = $data['purpose_id'];
  $sql3 = 'select * from banquet_purposes where banquet_purpose_id = ?';
  $stmt3 = $dbh->prepare($sql3);
  $stmt3->execute([$purpose_id]);
  $purpose = $stmt3->fetch();
  $purpose_name = $purpose['banquet_purpose_name'];
  $banquet_category_id = $purpose['banquet_category_id'];

  // レイアウト情報
  $layout_id = $data['layout_id'];
  $sql5 = 'select * from banquet_layouts where layout_id = ?';
  $stmt5 = $dbh->prepare($sql5);
  $stmt5->execute([$layout_id]);
  $layout = $stmt5->fetch();
  $layout_name = $layout['layout_name'];
  $layout_name2 = $data['layout_name'];

  // カテゴリ情報
  $sql4 = 'select * from banquet_categories where banquet_category_id = ?';
  $stmt4 = $dbh->prepare($sql4);
  $stmt4->execute([$banquet_category_id]);
  $category = $stmt4->fetch();
  $category_name = $category['banquet_category_name'];

  // エージェント情報
  $agent_id = $data['agent_id'];
  $agent_name = $data['agent_name'];
  $agent_group = '';
  $agent_group_short = '';
  if ($agent_id > 0) {
    $sql6 = 'select * from banquet_agents where agent_id = ?';
    $stmt6 = $dbh->prepare($sql6);
    $stmt6->execute([$agent_id]);
    $agent = $stmt6->fetch();
    $agent_group = $agent['agent_group'];
    $agent_group_short = $agent['agent_group_short'];
  }

  $reserver = mb_convert_kana($data['reserver'], 'KVas');

  // 詳細データ作成
  $detail = array(
    '管理ID' => $scheid,
    'NEHOPS予約ID' => $reservation_id,
    '予約名' => $reservation_name,
    '明細枝番' => $branch,
    '行灯名称' => $event_name,
    '利用日' => $dateObj->format('Y-m-d'),
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

  // ====================
  // 料金情報の取得処理
  // ====================
  $charge = array();
  $total_amount = 0;
  $service_amount = 0;
  $tax_amount = 0;
  $discount_amount = 0;
  $subtotal_amount = 0;

  // パッケージ料金
  $sql6 = 'select * from view_package_charges where reservation_id = ? and branch = ?';
  $stmt6 = $dbh->prepare($sql6);
  $stmt6->execute([$reservation_id, $branch]);
  foreach ($stmt6 as $row6) {
    $unit_price = intval($row6['UnitP']);
    $qty = intval($row6['Qty']);
    $subtotal = $unit_price * $qty;
    $service_fee = intval($row6['ServiceFee']);
    $tax = intval($row6['Tax']);
    $discount = intval($row6['Discount']);
    $gross = intval($row6['Gross']);

    $charge[] = array(
      'reservation_id' => $reservation_id,
      'branch' => $branch,
      'item_group_id' => $row6['package_id'],
      'item_group_name' => mb_convert_kana($row6['PackageName2'], "KVas"),
      'item_id' => $row6['package_id'],
      'item_name' => mb_convert_kana($row6['NameShort'], "KVas"),
      'unit_price' => number_format($unit_price),
      'qty' => number_format($qty),
      'subtotal' => number_format($subtotal),
      'discount' => number_format($discount),
      'service_fee' => number_format($service_fee),
      'tex' => number_format($tax),
      'gross' => number_format($gross)
    );

    // 合計金額の加算
    $total_amount += $gross;
    $service_amount += $service_fee;
    $tax_amount += $tax;
    $discount_amount += $discount;
    $subtotal_amount += $subtotal;
  }

  // 単品料金（パック以外）
  $sql7 = 'select * from view_charges where reservation_id = ? and branch = ? and item_group_id NOT LIKE \'X%\' and package_category NOT LIKE \'F%\'';
  $stmt7 = $dbh->prepare($sql7);
  $stmt7->execute([$reservation_id, $branch]);
  $charges = $stmt7->fetchAll(PDO::FETCH_ASSOC);

  foreach ($charges as $row) {
    $unit_price = intval($row['unit_price']);
    $qty = intval($row['qty']);
    $subtotal = $unit_price * $qty;
    $service_fee = intval($row['service_fee']);
    $tax = intval($row['tax']);
    $discount = intval($row['discount_amount']);
    $gross = intval($row['amount_gross']);

    $charge[] = array(
      'reservation_id' => $reservation_id,
      'branch' => $branch,
      'item_group_id' => $row['item_group_id'],
      'item_group_name' => $row['item_group_name'],
      'item_id' => $row['item_id'],
      'item_name' => $row['item_name'],
      'unit_price' => number_format($unit_price),
      'qty' => number_format($qty),
      'subtotal' => number_format($subtotal),
      'discount' => number_format($discount),
      'service_fee' => number_format($service_fee),
      'tex' => number_format($tax),
      'gross' => number_format($gross)
    );

    // 合計金額の加算
    $total_amount += $gross;
    $service_amount += $service_fee;
    $tax_amount += $tax;
    $discount_amount += $discount;
    $subtotal_amount += $subtotal;
  }

  // 返却配列の組み立て
  return array(
    'detail' => $detail,
    'charges' => $charge,
    'total_amount' => $total_amount,
    'service_amount' => $service_amount,
    'tax_amount' => $tax_amount,
    'discount_amount' => $discount_amount,
    'subtotal_amount' => $subtotal_amount
  );
}

function getMonthlySales($ym) {
  $dbh = new PDO(DSN, DB_USER, DB_PASS);

  // 指定年月の月末日を取得（例：2024-06 → 30）
  $ymObj = new DateTime($ym);
  $last_day = $ymObj->format('t');

  // 月初日を取得（例：2024-06 → 2024-06-01）
  $first_day = $ymObj->format('Y-m-01');
  // 月末日を取得（例：2024-06 → 2024-06-30）
  $end_day = $ymObj->format('Y-m-' . $last_day);
  

  // 表示用年月（例：2024年 06月）
  $year_month = $ymObj->format('Y年 m月');

  // 日別売上サブトータルを取得（目的ID=3を除外）
  $sql = "SELECT * FROM `view_daily_subtotal` WHERE `date` BETWEEN :fd AND :ed AND `purpose_id` NOT IN (3) ORDER BY `date` ASC";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':fd', $first_day, PDO::PARAM_STR);
  $stmt->bindValue(':ed', $end_day, PDO::PARAM_STR);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 各カテゴリごとの売上初期化
  $sales = array();
  $total_enkai = 0;
  $total_kaigi = 0;
  $total_shokuji = 0;
  $total_others = 0;
  $total = 0;

  if (count($rows) > 0) {
    foreach ($rows as $row) {
      $room_id = $row['room_id'];
      $date = $row['date'];
      $reservation_name = cleanLanternName($row['reservation_name']); // 表示名クリーニング
      $people = $row['people'];
      $banquet_category_id = $row['banquet_category_id'];
      $start = $row['start'];
      $end = $row['end'];
      $gross = $row['gross'];
      $ex_ts = $row['ex-ts']; // 売上集計対象金額

      // 日別売上一覧へ追加
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

      // カテゴリ別合計の集計
      $total += $ex_ts;
      switch ($banquet_category_id) {
        case 1:
          $total_kaigi += $ex_ts; break;
        case 2:
          $total_enkai += $ex_ts; break;
        case 3:
          $total_shokuji += $ex_ts; break;
        case 9:
          $total_others += $ex_ts; break;
      }
    }
  }

  // カレンダー表示対象の会場を取得
  $rooms = array();
  $sql = "SELECT * FROM `banquet_rooms` WHERE `cal` = 1 ORDER BY `order` DESC, `banquet_room_id` ASC";
  $room_rows = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  if (count($room_rows) === 0) {
    echo "会場が登録されていません。<br>";
    exit;
  }

  foreach ($room_rows as $row) {
    $rooms[] = array(
      'room_id' => $row['banquet_room_id'],
      'room_name' => $row['name'],
      'floor' => $row['floor']
    );
  }

  // 結果をまとめて返却
  return array(
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
}

function getDefectList($ym){
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
  $ymObj = new DateTime($ym);
  $first_day = $ymObj->format('Y-m-01');


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

function cleanLanternName($name, $max_length = 10) {
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

  $name = preg_replace("/[【※]/u", " ", $name); // 各文字単独で置換

  // 最初に出てくるスペース（半角・全角）で前半だけに分ける
  $parts = preg_split("/[ 　]/u", $name, 2);  // 2つに分割（前後）
  $name = $parts[0];  // 前半部分だけ使用

  // 最初の10文字に切り詰め
  $name = mb_substr($name, 0, $max_length);

  return $name;
}

function cleanLanternName2($name, $max_length = 10) {
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

  $name = preg_replace("/[【※]/u", " ", $name); // 各文字単独で置換

  // 最初に出てくるスペース（半角・全角）で前半だけに分ける
 # $parts = preg_split("/[ 　]/u", $name, 2);  // 2つに分割（前後）
 # $name = $parts[0];  // 前半部分だけ使用

  // 最初の10文字に切り詰め
  $name = mb_substr($name, 0, $max_length);

  return $name;
}

function salescatletter($sales_category_id) {
  $sales_category = array(
    1 => "<span class='sales-cat sales-cat-ka'>会</span>",
    2 => "<span class='sales-cat sales-cat-en'>宴</span>",
    3 => "<span class='sales-cat sales-cat-sho'>食</span>",
    4 => "<span class='sales-cat sales-cat-ka'>会</span><span class='sales-cat sales-cat-en'>宴</span>",
    5 => "<span class='sales-cat sales-cat-ka'>会</span><span class='sales-cat sales-cat-sho'>食</span>",
    6 => "<span class='sales-cat sales-cat-ka'>会</span><span class='sales-cat sales-cat-en'>宴</span><span class='sales-cat sales-cat-sho'>食</span>"
  );
  
  return isset($sales_category[$sales_category_id]) ? $sales_category[$sales_category_id] : "不明";
}

function statusletter($status) {
  $status_letters = array(
    1 => "<span class='status-letter status-letter-1'>決</span>",
    2 => "<span class='status-letter status-letter-2'>仮</span>",
    3 => "<span class='status-letter status-letter-3'>営</span>",
    4 => "<span class='status-letter status-letter-4'>待</span>",
    5 => "<span class='status-letter status-letter-5'>×</span>",
  );

  return isset($status_letters[$status]) ? $status_letters[$status] : "不明";
}
?>