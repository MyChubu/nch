<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
?>
<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
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
$admin = $_SESSION['admin'];
require_once('functions/admin_banquet.php');
$week = array('日', '月', '火', '水', '木', '金', '土');

$today = date('Y-m-d');
$d1 = $_GET['d1'] ? (new DateTime($_GET['d1']))->format('Y-m-d') : null;
$d2 = $_GET['d2'] ? (new DateTime($_GET['d2']))->format('Y-m-d') : null;
if( !$d1 ){
  if( $d2 ){
    $d1 = $d2;
    $date1 = $d1;
    $date2 = $d1;
    $update_msg = '（'.$date1.'）';
    $d2 = '';
  }else{
    $date1 = $today;
    $date2 = $today;
    $update_msg = '（'.$date1.'）';
    $d1 = $today;
  }
}else{
  if( !$d2 ){
    $date1 = $d1;
    $date2 = $d1;
    $update_msg = '（'.$date1.'）';
  }else{
    if( $d1 == $d2 ){
      $date1 = $d1;
      $date2 = $d1;
      $update_msg = '（'.$date1.'）';
      $d2='';
    }else{
      if( $d1 > $d2 ){
        $tmp = $d1;
        $d1 = $d2;
        $d2 = $tmp; 
      } 
      $date1 = $d1;
      $date2 = $d2;
      $update_msg = '（'.$date1.'～'.$date2.'）';
    }
  }
}


$sql='SELECT * FROM `view_softdrink_order`
WHERE `date` BETWEEN :date1 AND :date2
ORDER BY `date` ASC,`item_gene_id` ASC, `reservation_id` ASC';
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':date1', $date1, PDO::PARAM_STR);
$stmt->bindParam(':date2', $date2, PDO::PARAM_STR);
$stmt->execute();
$count = $stmt->rowCount();
if($count > 0){
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $teas=array();
  $wtrs=array();
  $tea_total=0;
  $tea_net_total=0;
  $tea_gross_total=0;
  $wtr_total=0;
  $wtr_net_total=0;
  $wtr_gross_total=0;
  foreach($orders as $order){
    // 各注文の処理
    //種類分け
    $water_types = ['B02-0007'];
    if (in_array($order['item_gene_id'], $water_types)) {
      // 水の処理
      $wtrs[] = $order;
      $wtr_total += $order['qty'];
      $wtr_net_total += $order['net'];
      $wtr_gross_total += $order['gross'];
    } else {
      // お茶の処理
      $teas[] = $order;
      $tea_total += $order['qty'];
      $tea_net_total += $order['net'];
      $tea_gross_total += $order['gross'];
    }

  }


}



?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>
    ペットボトル茶・水<?= $update_msg ?>
  </title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/form.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/reservations.css?<?=date('YmdHis') ?>">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  
  <link rel="stylesheet" href="css/table_sort.css?<?=date('YmdHis') ?>">
  <script src="js/table_sort.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
  <div class="wrapper">
    <div id="controller">
      <div id="controller_left">
        <form  method="get" enqtype="application/x-www-form-urlencoded">
          <input type="date" name="d1" id="d1" value="<?= htmlspecialchars($d1) ?>" required> ～ <input type="date" name="d2" id="d2" value="<?= htmlspecialchars($d2) ?>" >
          <button type="submit">表示</button>
        </form>
      </div>
      <!-- <div id="controller_right2">
        <div id="download"><a href="output/softdrink-excel-export.php?d1=<?= $d1 ?>&d2=<?= $d2 ?>" target="_blank"><i class="fa-solid fa-file-excel"></i>Excel</a></div>
      </div> -->
    </div>
    <div>
      <h1>ペットボトルお茶・水<?=$update_msg?></h1>
    </div>
    <div>
      <?php if($count > 0): ?>

        <?php if(sizeof($teas) > 0): ?>
          <h2>お茶（ペットボトル）</h2>
          <div>期間中合計：<?=number_format($tea_total,0) ?> 本</div>
          <table>
            <thead>
              <tr>
                <th>日付</th>
                <th>状態</th>
                <th>売上部門</th>
                <th>予約ID</th>
                <th>予約名</th>
                <th>会場</th>
                <th>種類</th>
                <th>単価</th>
                <th>数量</th>
                <th>金額GROSS</th>
                <th>金額NET</th>
                <th>担当</th>
                <th>作成日</th>
                <th>決定日</th>
                <th>最終更新</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($teas as $order): ?>
                <tr>
                  <td><?=htmlspecialchars($order['date']) ?></td>
                  <td><?=statusletter($order['status']) ?></td>
                  <td><?=salescatletter($order['banquet_category_id']) ?></td>
                  <td><a href="connection_list.php?resid=<?=htmlspecialchars($order['reservation_id']) ?>"><?=htmlspecialchars($order['reservation_id']) ?></a></td>
                  <td><?=htmlspecialchars($order['reservation_name']) ?></td>
                  <td><?=htmlspecialchars($order['room_name']) ?></td>
                  <td><?=htmlspecialchars($order['item_name_short']) ?></td>
                  <td><?=htmlspecialchars(number_format($order['unit_price'])) ?></td>
                  <td><?=htmlspecialchars($order['qty']) ?></td>
                  <td><?=htmlspecialchars(number_format($order['gross'])) ?></td>
                  <td><?=htmlspecialchars(number_format($order['net'])) ?></td>
                  <td><?=cleanLanternName($order['pic']) ?></td>
                  <td><?=htmlspecialchars($order['d_created']) ?></td>
                  <td><?=htmlspecialchars($order['d_decided']) ?></td>
                  <td><?=htmlspecialchars($order['d_mod']) ?></td>

                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfooter>
              <tr>
                <td colspan="4">合計</td>
                <td><?=sizeof($teas) ?> 件</td>
                <td colspan="3"></td>
                <td><?=number_format($tea_total,0) ?> 本</td>
                <td><?=number_format($tea_gross_total,0) ?> 円</td>
                <td><?=number_format($tea_net_total,0) ?> 円</td>
                <td colspan="4"></td>
              </tr>
            </tfooter>
          </table>
        <?php endif; ?>
        <?php if(sizeof($wtrs) > 0): ?>
          <h2>水（ペットボトル）</h2>
          <div>期間中合計：<?=number_format($wtr_total,0) ?> 本</div>
          <table>
            <thead>
              <tr>
                <th>日付</th>
                <th>状態</th>
                <th>売上部門</th>
                <th>予約ID</th>
                <th>予約名</th>
                <th>会場</th>
                <th>種類</th>
                <th>単価</th>
                <th>数量</th>
                <th>金額GROSS</th>
                <th>金額NET</th>
                <th>担当</th>
                <th>作成日</th>
                <th>決定日</th>
                <th>最終更新</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($wtrs as $order): ?>
                <tr>
                  <td><?=htmlspecialchars($order['date']) ?></td>
                  <td><?=statusletter($order['status']) ?></td>
                  <td><?=salescatletter($order['banquet_category_id']) ?></td>
                  <td><a href="connection_list.php?resid=<?=htmlspecialchars($order['reservation_id']) ?>"><?=htmlspecialchars($order['reservation_id']) ?></a></td>
                  <td><?=htmlspecialchars($order['reservation_name']) ?></td>
                  <td><?=htmlspecialchars($order['room_name']) ?></td>
                  <td><?=htmlspecialchars($order['item_name_short']) ?></td>
                  <td><?=htmlspecialchars(number_format($order['unit_price'])) ?></td>
                  <td><?=htmlspecialchars($order['qty']) ?></td>
                  <td><?=htmlspecialchars(number_format($order['gross'])) ?></td>
                  <td><?=htmlspecialchars(number_format($order['net'])) ?></td>
                  <td><?=cleanLanternName($order['pic']) ?></td>
                  <td><?=htmlspecialchars($order['d_created']) ?></td>
                  <td><?=htmlspecialchars($order['d_decided']) ?></td>
                  <td><?=htmlspecialchars($order['d_mod']) ?></td>

                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfooter>
              <tr>
                <td colspan="4">合計</td>
                <td><?=sizeof($wtrs) ?> 件</td>
                <td colspan="3"></td>
                <td><?=number_format($wtr_total,0) ?> 本</td>
                <td><?=number_format($wtr_gross_total,0) ?> 円</td>
                <td><?=number_format($wtr_net_total,0) ?> 円</td>
                <td colspan="4"></td>
              </tr>
            </tfooter>
          </table>
        <?php endif; ?>
      <?php else: ?>
        <p>該当する注文はありません。</p>
      <?php endif; ?>
    </div>


  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("footer.php"); ?>

</body>
</html>