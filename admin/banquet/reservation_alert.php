<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);
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
$date = date('Y-m-d');
$w = date('w');
$wd= $week[$w];


$sql ="SELECT
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  MIN(`status`) AS `status`,
  `sales_category_id`,
  `sales_category_name`,
  `reservation_type_code`,
  `reservation_type`,
  `reservation_type_name`,
  `agent_id`,
  `agent_name`,
  `agent_short`,
  `agent_name2`,
  MAX(`people`) AS `people`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`,
  `pic_id`,
  `pic`,
  `d_created`,
  `d_decided`,
  `d_tentative`,
  `due_date`,
  `cancel_date`,
  MAX(`reservation_sales_diff`) AS `reservation_sales_diff`,
  MAX(`due_over_flg`) AS `due_over_flg`

FROM `view_monthly_new_reservation3`
WHERE `reservation_date` >= CURDATE()
GROUP BY
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  `sales_category_id`,
  `sales_category_name`,
  `reservation_type_code`,
  `reservation_type`,
  `reservation_type_name`,
  `agent_id`,
  `agent_name`,
  `agent_short`,
  `agent_name2`,
  `pic_id`,
  `pic`,
  `d_created`,
  `d_decided`,
  `d_tentative`,
  `due_date`,
  `cancel_date`,
  `reservation_sales_diff`,
  `due_over_flg`
HAVING
     SUM(`net`) = 0
  OR MAX(`people`) = 0
  OR MAX(`reservation_sales_diff`) = 1
  OR MAX(`due_over_flg`) = 1
ORDER BY
  `pic_id`,
  `reservation_date`,
  `reservation_id`";
$stmt = $dbh->prepare($sql);

$stmt->execute();
$count = $stmt->rowCount();

$events = array();

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
    データ不備リスト（<?= $date ?> 現在）
  </title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/form.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/reservations.css?<?=date('YmdHis') ?>">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="css/new_reservations.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/table_sort.css?<?=date('YmdHis') ?>">
  <script src="js/table_sort.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
  <div class="wrapper">
    <div id="controller">

    </div>
    <div>
      <h1>データ不備リスト（<?= $date ?> 現在）</h1>
    </div>
    <div>
      <table id="rsv_table" class="sortable">
        <thead>
          <tr>
            <th class="cell_w80">予約ID</th>
            <th class="cell_w100">予約日</th>
            <th class="cell_w200">予約名</th>
            <th class="cell_w30">状況</th>
            <th>仮予約期限</th>
            <th class="cell_w150">販売区分</th>
            <th class="cell_w150">予約種類</th>
            <th class="cell_w30">人数</th>
            <th class="cell_w100">売上金額</th>
            <th class="cell_w300">担当者</th>
            <th class="cell_w200">代理店</th>
            <th>代理店名</th>
            <th class="cell_w200">異常内容</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($events as $event): ?>
            <tr>
              <td class="cell_w80"><a href="connection_list.php?resid=<?= $event['reservation_id'] ?>"><?= htmlspecialchars($event['reservation_id']) ?></td>
              <td class="cell_w100"><?= htmlspecialchars($event['reservation_date']) ?></td>
              <td class="cell_w200"><?= htmlspecialchars(cleanLanternName($event['reservation_name'])) ?></td>
              <td class="cell_w30"><?= statusletter($event['status']) ?></td>
              <td class="cell_w100"><?= htmlspecialchars($event['due_date']) ?></td>
              <td class="cell_w150"><?= salescatletter($event['sales_category_id']) ?></td>
              <td class="cell_w150"><?= salescatletter($event['reservation_type']) ?></td>
              <td class="cell_w30"><?= htmlspecialchars($event['people']) ?></td>
              <td class="cell_w100"><?= number_format($event['net']) ?></td>
              <td class="cell_w300"><?= htmlspecialchars(cleanLanternName($event['pic'])) ?></td>
              <td class="cell_w200"><?= htmlspecialchars($event['agent_short']) ?></td>
              <td><?= htmlspecialchars($event['agent_name2']) ?></td>
              <td class="cell_w300">
                <?php foreach($event['errors'] as $error): ?>
                  <?= htmlspecialchars($error) ?><br>
                <?php endforeach; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>







  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("footer.php"); ?>

</body>
</html>