<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
$reservation_id = $_REQUEST['resid'];

$array = getConnectionList($reservation_id);
$events = $array['events'];
$charges = $array['charges'];
$total_amount = $array['total_amount'];
$service_amount = $array['service_amount'];
$tax_amount = $array['tax_amount'];
$discount_amount = $array['discount_amount'];
$subtotal_amount = $array['subtotal_amount'];



?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>デジサイ詳細表示</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <!--<script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>-->
  <!--<script src="js/admin_banquet.js"></script>-->
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <table class="event_table">
    <tr>
      <th><i class="fa-solid fa-hashtag"></i></th>
      <th>予約名</th>
      <th><i class="fa-solid fa-code-branch"></i></th>
      <th>イベント名</th>
      <th><i class="fa-solid fa-calendar-days"></i></th>
      <th><i class="fa-solid fa-arrow-right"></i></th>
      <th><i class="fa-solid fa-arrow-left"></i></th>
      <th><i class="fa-solid fa-location-dot"></i></th>
      <th><i class="fa-solid fa-stairs"></i></th>
      <th><i class="fa-solid fa-signal"></i></th>
      <th><i class="fa-solid fa-user"></i></th>
      <th><i class="fa-solid fa-display"></i></th>
      <th><i class="fa-solid fa-gear"></i></th>
    </tr>
    <?php foreach($events as $event): ?>
      <?php $e_dt = new DateTime($event['event_date']);
            $event_date = $e_dt->format('Y-m-d'); ?>
    <tr>
      <td><?=$event['reservation_id'] ?></td>
      <td><?=$event['resevation_name'] ?></td>
      <td><?=$event['branch'] ?></td>
      <td><?=$event['event_name'] ?></td>
      <td><a href="signage.php?event_date=<?=$event_date ?>"><?=$event['date'] ?></a></td>
      <td><?=$event['start'] ?></td>
      <td><?=$event['end'] ?></td>
      <td><?=$event['room_name'] ?></td>
      <td><?=$event['floor'] ?></td>
      <td><?=statusletter($event['status']) ?></td>
      <td><?=cleanLanternName($event['pic']) ?></td>
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
          <th>料金</th>
          <th>割引</th>
          <th>請求額</th>
          <th>サ料</th>
          <th>税</th>
          <th>税抜</th>
          <th>税・サ抜</th>
        </tr>
        <?php foreach($charges as $charge): ?>
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
          <td><?=number_format($charge['discount']) ?></td>
          <td><?=number_format($charge['gross']) ?></td>
          <td><?=number_format($charge['service_fee']) ?></td>
          <td><?=number_format($charge['tax']) ?></td>
          <td><?=number_format($charge['gross'] - $charge['tax']) ?></td>
          <td><?=number_format($charge['gross'] - $charge['tax'] - $charge['service_fee']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total">
          <td colspan="9">合計</td>
          <td><?=number_format($subtotal_amount) ?></td>
          <td><?=number_format($discount_amount) ?></td>
          <td><?=number_format($total_amount) ?></td>
          <td><?=number_format($service_amount) ?></td>
          <td><?=number_format($tax_amount) ?></td>
          <td><?=number_format($total_amount - $tax_amount) ?></td>
          <td><?=number_format($total_amount - $tax_amount - $service_amount) ?></td>
        </tr>
      </table>

    <?php endif; ?>
  </div>
  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>