<?php
require_once('../../common/conf.php');
include_once('../functions/accesslog.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
session_name('_NCH_ADMIN');
session_start();
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : '';
$user_name = $_SESSION['name'];
accesslog();

if (empty($user_id) || empty($user_name)) {
  header('Location: ../login.php?error=2');
  exit;
}else{
  $sql = "SELECT * FROM users WHERE user_id = :user_id AND status = 1";
  $stmt = $dbh->prepare($sql);
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $u_count = $stmt->rowCount();
  if ($u_count < 1) {
    header('Location: ../login.php?error=3');
    exit;
  }
}
$user_mail = $_SESSION['mail'];
$admin = $_SESSION['admin'];


require_once('functions/admin_banquet.php');
$reservation_id = $_REQUEST['resid'];

$array = getConnectionList2($reservation_id);
// $detail = getDetail2($reservation_id);
$detail = $array['detail'];
$events = $array['events'];
$charges = $array['charges'];
$total_amount = $array['total_amount'];
$service_amount = $array['service_amount'];
$tax_amount = $array['tax_amount'];
$discount_amount = $array['discount_amount'];
$subtotal_amount = $array['subtotal_amount'];
$total_amount2 = $array['total_amount2'];
$service_amount2 = $array['service_amount2'];
$tax_amount2 = $array['tax_amount2'];
$discount_amount2 = $array['discount_amount2'];
$subtotal_amount2 = $array['subtotal_amount2'];

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>デジサイ詳細表示</title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/form.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
 
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <?php if(sizeof($detail) > 0): ?>
    <div>
      <h2>予約サマリー</h2>
      <table class="event_table">
        <thead>
          <tr>
            <th>項目</th>
            <th>値</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th><i class="fa-solid fa-hashtag"></i></th>
            <td><?=$detail['reservation_id'] ?></td>
          </tr>
          <tr>
            <th>売上種類</th>
            <td>
              <?php if($detail['additional_sales'] == 1): ?>
                追加売上
              <?php else: ?>
                通常売上
              <?php endif; ?>
            </td>
          <tr>
            <th>予約名</th>
            <td><?=$detail['reservation_name'] ?></td>
          </tr>
          <tr>
            <th><i class="fa-solid fa-people-group"></i></th>
            <td><?=$detail['people'] ?></td>
          </tr>
          <tr>
            <th><i class="fa-solid fa-calendar-days"></i></th>
            <td><?=$detail['event_date'] ?></td>
          </tr>
          <tr>
            <th><i class="fa-solid fa-signal"></i></th>
            <td>
              <?=statusletter($detail['status']) ?>
              <? if ($detail['status'] == 2): ?>
                （<?=$detail['due_date'] ?> まで）
              <? endif; ?>
            </td>
          </tr>
          <tr>
            <th><i class="fa-solid fa-user"></i></th>
            <td><?=mb_convert_kana($detail['pic'],'KVas') ?></td>
          </tr>
          <tr>
            <th>販売</th>
            <td>
              <?php if($detail['agent_id'] == 0): ?>
                直販
              <?php else: ?>
                <?=$detail['agent_name'] ?> 
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th>利用種類</th>
            <td><?=salescatletter($detail['sales_category_id']) ?></td>
          </tr>
          <tr>
            <th>システム登録日</th>
            <td><?=$detail['nehops_d_created']!="" ? $detail['nehops_d_created'] : "-" ?></td>
          </tr>
          <tr>
            <th>仮予約登録</th>
            <td><?=$detail['nehops_d_tentative']!="" ? $detail['nehops_d_tentative'] : "-" ?></td>
          </tr>
          <tr>
            <th>キャンセル日</th>
            <td><?=$detail['cancel_date']!="" ? $detail['cancel_date'] : "-" ?></td>
          </tr>
          <tr>
            <th>決定登録</th>
            <td><?=$detail['nehops_d_decided']!="" ? $detail['nehops_d_decided'] : "-" ?></td>
          </tr>
          <tr>
            <th>最終更新日</th>
            <td><?=$detail['nehops_d_mod']!="" ? $detail['nehops_d_mod'] : "-" ?></td>
          </tr>
      </table>
    </div>
    <?php endif; ?>

  <h2>イベント一覧</h2>
  <table class="event_table">
    <tr>
      <th><i class="fa-solid fa-hashtag"></i></th>
      <th><i class="fa-solid fa-code-branch"></i></th>
      <th>イベント名</th>
      <th><i class="fa-solid fa-calendar-days"></i></th>
      <th><i class="fa-solid fa-arrow-right"></i></th>
      <th><i class="fa-solid fa-arrow-left"></i></th>
      <th><i class="fa-solid fa-location-dot"></i></th>
      <th><i class="fa-solid fa-stairs"></i></th>
      <th><i class="fa-solid fa-signal"></i></th>
    
      <th><i class="fa-solid fa-display"></i></th>
      <th><i class="fa-solid fa-gear"></i></th>
    </tr>
    <?php foreach($events as $event): ?>
      <?php $e_dt = new DateTime($event['event_date']);
            $event_date = $e_dt->format('Y-m-d'); ?>
    <tr>
      <td><?=$event['reservation_id'] ?></td>
      <td><?=$event['branch'] ?></td>
      <td><?=str_replace("///", " ", $event['event_name']) ?></td>
      <td><a href="signage.php?event_date=<?=$event_date ?>"><?=$event['date'] ?></a></td>
      <td><?=$event['start'] ?></td>
      <td><?=$event['end'] ?></td>
      <td><?=$event['room_name'] ?></td>
      <td><?=$event['floor'] ?></td>
      <td><?=statusletter($event['status']) ?></td>
      <td>
        <?php if($event['enable'] == 1): ?>
          <i class="fa-solid fa-square-check"></i>
        <?php else: ?>
          &nbsp;
        <?php endif; ?>
      </td>
      <td><a href="detail.php?scheid=<?=$event['banquet_schedule_id'] ?>">詳細</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <div>
    <h2>料金情報</h2>
    <?php if( sizeof($charges) == 0): ?>
      <p>料金情報はありません</p>
    <?php else: ?> 
      <table class="event_table">
        <tr>
          <th><i class="fa-solid fa-hashtag"></i></th>
          <th><i class="fa-solid fa-code-branch"></i></th>
          <th><i class="fa-solid fa-calendar-days"></i></th>
          <th>科目</th>
          <th>科目名</th>
          <th>商品</th>
          <th>商品名</th>
          <th><i class="fa-solid fa-at"></i></th>
          <th>数量</th>
          
          <th>&#9312;&nbsp;金額</th>
          <th>&#9313;&nbsp;売上<br>（&#9312; - &#9317;）</th>
          <th>&#9314;&nbsp;純売上<br>（&#9313; - &#9315; - &#9316;）</th>
          <th>&#9315;&nbsp;サービス料</th>
          <th>&#9316;&nbsp;消費税</th>
          <th>&#9317;&nbsp;割引</th>
        </tr>
        <?php foreach($charges as $charge): ?>
          <?php
            if($detail['status'] != 5 && $charge['status'] == 5){
              continue; // キャンセル分は表示しない
            }
          ?>
        <tr>
          <td><?=$charge['reservation_id'] ?></td>
          <td><?=$charge['branch'] ?></td>
          <td><?=$charge['date'] ?></td>
          <td><?=$charge['item_group_id'] ?></td>
          <td><?=$charge['item_group_name'] ?></td>
          <td><?=$charge['item_id'] ?></td>
          <td><?=$charge['name_short'] ?></td>
          <td><?=number_format($charge['unit_price']) ?></td>
          <td><?=number_format($charge['qty']) ?></td>
          <td><?=number_format($charge['subtotal']) ?></td>
          <td><?=number_format($charge['gross']) ?></td>
          <td><?=number_format($charge['gross'] - $charge['tax'] - $charge['service_fee']) ?></td>
          <td><?=number_format($charge['service_fee']) ?></td>
          <td><?=number_format($charge['tax']) ?></td>
          <td><?=number_format($charge['discount']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total">
          <td colspan="9">合計</td>
          <td><?=$detail['status']!= 5? number_format($subtotal_amount2): number_format($subtotal_amount) ?></td>
          <td><?=$detail['status']!= 5? number_format($total_amount2): number_format($total_amount) ?></td>
          <td><?=$detail['status']!= 5? number_format($total_amount2 - $tax_amount2 - $service_amount2):number_format($total_amount - $tax_amount - $service_amount) ?></td>
          <td><?=$detail['status']!= 5? number_format($service_amount2):number_format($service_amount) ?></td>
          <td><?=$detail['status']!= 5? number_format($tax_amount2):number_format($tax_amount) ?></td>
          <td><?=$detail['status']!= 5? number_format($discount_amount2):number_format($discount_amount) ?></td>
        </tr>
      </table>

    <?php endif; ?>
  </div>
  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("../common/footer.php"); ?>

</body>
</html>