<?php
// NEHOPSのデータ不備を担当者にメールで通知するバッチ処理
// cronで毎日実行想定（平日朝7時）
//休日は処理しない
//・土日（cron側でも設定する）
//・年末年始（12/28〜1/4）

// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once('../common/conf.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);

$today = new DateTimeImmutable('today');

// 曜日取得（0=日曜, 6=土曜）
$w = (int)$today->format('w');
if ($w === 0 || $w === 6) {
  exit; // 土日
}

// 月日取得
$month = (int)$today->format('m');
$day   = (int)$today->format('d');
// 年末年始（12/28〜1/4）
if (
  ($month === 12 && $day >= 28) ||
  ($month === 1  && $day <= 4)
) {
  exit;
}

$ymd = $today->format('Y年m月d日');

//NEHOPSのデータに不備がある場合、担当者にリストをメールする
//担当者リスト取得
$sql = "SELECT `user_id`, `name`, `mail`, `pic_id` FROM `users` WHERE `group` = 1 AND `status` = 1";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

//担当者ごとに処理
foreach($users as $user){
  $events = array();
  $user_id = $user['user_id'];
  $user_name = $user['name'];
  // $user_mail = $user['mail'];
  $user_mail = 'takeichi@nagoyacrown.co.jp'; //テスト用
  $pic_id = $user['pic_id'];
  //データ不備リスト取得
  $sql = "
    SELECT
      reservation_id,
      reservation_date,
      reservation_name,
      MIN(status) AS status,
      sales_category_id,
      sales_category_name,
      reservation_type_code,
      reservation_type,
      reservation_type_name,
      agent_id,
      agent_name,
      agent_short,
      agent_name2,
      MAX(people) AS people,
      SUM(gross)  AS gross,
      SUM(net)    AS net,
      pic_id,
      pic,
      d_created,
      d_decided,
      d_tentative,
      due_date,
      cancel_date,
      MAX(reservation_sales_diff) AS reservation_sales_diff,
      MAX(due_over_flg)           AS due_over_flg
    FROM view_monthly_new_reservation3
    WHERE reservation_date >= CURDATE()
      AND pic_id = :pic_id
    GROUP BY
      reservation_id,
      reservation_date,
      reservation_name,
      sales_category_id,
      sales_category_name,
      reservation_type_code,
      reservation_type,
      reservation_type_name,
      agent_id,
      agent_name,
      agent_short,
      agent_name2,
      pic_id,
      pic,
      d_created,
      d_decided,
      d_tentative,
      due_date,
      cancel_date
    HAVING
      SUM(net) = 0
      OR MAX(people) = 0
      OR MAX(reservation_sales_diff) = 1
      OR MAX(due_over_flg) = 1
    ORDER BY
      reservation_date,
      reservation_id
  ";
 
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':pic_id', $pic_id, PDO::PARAM_STR);
  $stmt->execute();
  $count = $stmt->rowCount();
  if($count > 0){
    $rsvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($rsvs as $rsv){
      $errors = array();
      if($rsv['net'] == 0){
        $errors[] = '明細なし';
      } 
      if($rsv['people'] == 0){
        $errors[] = '人数なし';
      } 
      if($rsv['reservation_sales_diff'] == 1){
        $errors[] = '予約種類不一致';
      }
      if($rsv['due_over_flg'] == 1){
        $errors[] = '仮期限切れ';
      }
      $events[] = array(
        'reservation_id' => $rsv['reservation_id'],
        'reservation_date' => $rsv['reservation_date'],
        'reservation_name' => $rsv['reservation_name'],
        'status' => $rsv['status'],
        'sales_category_id' => $rsv['sales_category_id'],
        'sales_category_name' => $rsv['sales_category_name'],
        'reservation_type_code' => $rsv['reservation_type_code'],
        'reservation_type' => $rsv['reservation_type'],
        'reservation_type_name' => $rsv['reservation_type_name'],
        'agent_id' => $rsv['agent_id'],
        'agent_name' => $rsv['agent_name'],
        'agent_short' => $rsv['agent_short'],
        'agent_name2' => $rsv['agent_name2'],
        'people' => $rsv['people'],
        'gross' => $rsv['gross'],
        'net' => $rsv['net'],
        'pic_id' => $rsv['pic_id'],
        'pic' => $rsv['pic'],
        'due_date' => $rsv['due_date'],
        'errors' => $errors
      );
    }
    
    if(sizeof($events) == 0){
      continue;
    }

    //リスト
    $list = "";
    foreach($events as $event){
      $status = '';
      switch($event['status']){
        case 1:
          $status = '(決)';
          break;
        case 2:
          $status = '(仮)';
          break;
        default:
          $status = '(未)';
      }
      $list .= $event['reservation_date'] . " ";
      $list .= "#" . $event['reservation_id'] . " ";
      $list .= $status . " ";
      $list .= $event['reservation_name'] . " ";
      foreach($event['errors'] as $error){
        $list .= "[" . $error . "] ";
      }
      $list .= "\n";
      //...
    }

    //メール本文作成
    $body ="";
    $body .= "このメールは、WEBシステムより自動で送信しております。\n\n";
    $body .= $user_name . " 様\n\n";
    $body  .="お疲れ様です。\n";
    $body .= "NEHOPSの入力データの不備が" . $count . "件あります。\n";
    $body .= "ご確認いただき、修正をお願いいたします。\n\n";

    $body .= "お知らせする不備は以下のとおりです。\n";
    $body .= "・仮期限切れ = 仮予約の期限が過ぎています。確認および期限延長などの対応をしてください。\n";
    $body .= "・人数なし = 人数が登録されていません。会場に人数を入れてください。\n";
    $body .= "・明細なし = 料金明細がありません。明細を入力してください。\n";
    $body .= "・予約種類不一致 = 「予約種類」と「売上部門」が一致していません。基本情報を修正してください。\n\n";
    $body .= "------------------------------\n";
    $body .= "【データ不備リスト】 " . $ymd . " 現在\n\n";
    $body .= $list;
    $body .= "\n------------------------------\n\n";
    $body .= "すでに修正済みの場合は、このメールは行き違いとなりますので、ご了承ください。";

    //メール送信
    $subject = "【NEHOPS】データ不備のお知らせ";
    $headers = "From:noreply@nagoyacrown.co.jp\r\n";
    mb_send_mail($user_mail, $subject, $body, $headers);
  }
}
?>