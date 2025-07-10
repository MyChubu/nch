<?php
require_once('../../common/conf.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
session_name('_NCH_ADMIN');
session_start();
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : '';
$user_name = $_SESSION['name'];

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

require_once('functions/admin_banquet.php');
$week = array('日', '月', '火', '水', '木', '金', '土');
$scheid = $_REQUEST['scheid'];

$array = getDetail($scheid);
$detail = $array['detail'];
$charge = $array['charges'];
$reservation_id = $detail['reservation_id'];
$branch = $detail['branch'];
$subtotal_amount = $array['subtotal_amount'];
$discount_amount = $array['discount_amount'];
$total_amount = $array['total_amount'];
$service_amount = $array['service_amount'];
$tax_amount = $array['tax_amount'];

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>デジサイ詳細表示</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/form.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <table class="event_table">
    <tr>
      <th>項目</th>
      <th>内容</th>
    </tr>
    <?php foreach($detail as $key => $value): ?>
    <tr>
      <td><?= $key ?></td>
      <td>
        <?php if($key == 'NEHOPS予約ID'): ?>
          <a href="./connection_list.php?resid=<?= $value ?>"><?= $value ?></a>
        <?php elseif($key == '利用日'): ?>
          <?php
            $ed = new DateTime($value);
            $edate = $ed->format('Y-m-d');
            $ew = $ed->format('w');
            $ewd = $week[$ew];

                 ?>
                    <a href="./signage.php?event_date=<?= $ed->format('Y-m-d') ?>"><?= $ed->format('Y-m-d') ?>(<?=$ewd ?>)</a>
        <?php else: ?> 
          <?= $value ?>
        <?php endif; ?>
      </td> 
    </tr>
    <?php endforeach; ?>

  </table>
  <div>
    <h2>料金情報</h2>
    <?php if( sizeof($charge) == 0): ?>
      <p>料金情報はありません</p>
    <?php else: ?>
      <table class="event_table">
        <tr>
          <th>予約ID</th>
          <th><i class="fa-solid fa-code-branch"></i></th>
          <th>科目</th>
          <th>科目名</th>
          <th>商品</th>
          <th>商品名</th>
          <th><i class="fa-solid fa-at"></i></th>
          <th>数量</th>
          <th>小計</th>
          <th>割引</th>
          <th>サ料</th>
          <th>税</th>
          <th>請求額</th>
        </tr>
        <?php foreach($charge as $row): ?>
        <tr>
          <?php foreach($row as $key => $value): ?>
            <td><?= $value ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </table>
      <div class="total">
        <p>合計金額：<?=number_format($subtotal_amount) ?>円</p>
        <p>割引額：<?=number_format($discount_amount) ?>円</p>
        <p>請求額：<?=number_format($total_amount) ?>円</p>
        <p>（サービス料：<?=number_format($service_amount) ?>円）</p>
        <p>（消費税：<?=number_format($tax_amount) ?>円）</p>
        <p>（税抜：<?=number_format($total_amount - $tax_amount) ?>円）</p>
        <p>（税・サ抜：<?=number_format($total_amount - $tax_amount - $service_amount) ?>円）</p>

      </div>
    <?php endif; ?>
    
  </div>
  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>