<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
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
$admin = $_SESSION['admin'];
require_once('functions/admin_banquet.php');
$week = array('日', '月', '火', '水', '木', '金', '土');
$date = date('Y-m-d');
$w = date('w');
$wd= $week[$w];

$ym=$_REQUEST['ym'] ?? date('Y-m');

$sd = $ym . '-01';
$sdt = $ym . '-01 00:00:00.000000';
$ed = (new DateTime($sd))->modify('last day of +0 month')->format('Y-m-d');
$edt = (new DateTime($ed))->format('Y-m-d 23:59:59.999999');


$finals= array();
$tentatives= array();
$cancelleds= array();

$sql ="SELECT
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  `status`,
  `status_name`,
  `sales_category_id`,
  `sales_category_name`,
  `agent_id`,
  `agent_name`,
  MAX(`people`) AS `people`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`,
  `pic_id`,
  `pic`,
  `nehops_created`,
  `due_date`,
  `cancel_date`
FROM `view_monthly_new_reservation` WHERE `reservation_date` >= :sd AND `nehops_created` BETWEEN :sdt AND :edt
GROUP BY
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  `status`,
  `status_name`,
  `sales_category_id`,
  `sales_category_name`,
  `agent_id`,
  `agent_name`,
  `pic_id`,
  `pic`,
  `nehops_created`,
  `due_date`,
  `cancel_date`
ORDER BY
  `reservation_date`,
  `reservation_id`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':sd', $sd, PDO::PARAM_STR);
$stmt->bindValue(':sdt', $sdt, PDO::PARAM_STR);
$stmt->bindValue(':edt', $edt, PDO::PARAM_STR);
$stmt->execute();
$count = $stmt->rowCount();
if($count > 0){
  $rsvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach($rsvs as $rsv) {
    if($rsv['status'] == 5 && $rsv['cancel_date'] >= $sd){
      $rsv['status'] = 2;
      $rsv['status_name'] = '仮予約';
    }
    if($rsv['status'] == 1){
      $finals[] = $rsv;
    }elseif($rsv['status'] == 2){
      $tentatives[] = $rsv;
    }elseif($rsv['status'] == 5){
      $cancelleds[] = $rsv;
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
    会議・宴会予約リスト
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
      <form  method="get" enqtype="application/x-www-form-urlencoded">
        <input type="month" name="ym" id="ym" value="<?= htmlspecialchars($ym) ?>" required>
        <button type="submit">表示</button>
      </form>
      <!--<div id="download"><a href="output/reservations-excel-export.php?ym=<?= $ym ?>&mon=<?= $mon ?>&sts=<?= $sts ?>" target="_blank"><i class="fa-solid fa-file-excel"></i>Excel</a></div>-->
    </div>
    <div>
      <h1>会議・宴会予約リスト</h1>
      <p>本日は<?= $date ?>（<?= $wd ?>）です。</p>

    </div>
    <div>
      <h2>決定予約</h2>
      <?php if(sizeof($finals) > 0): ?>
        <table class="">
          <thead>
            <tr>
              <th>実施日</th>
              <th>ステータス</th>
              <th>ステータス名</th>
              <th>予約名</th>
              <th>エージェントID</th>
              <th>エージェント名</th>
              <th>人数</th>
              <th>売上</th>
              <th>ネット</th>
              <th>担当ID</th>
              <th>担当名</th>
              <th>予約ID</th>
              <th>仮期限</th>
              <th>キャンセル日</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($finals as $rsv): ?>
            <tr>
              <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
              <td><?= htmlspecialchars($rsv['status']) ?></td>
              <td><?= htmlspecialchars($rsv['status_name']) ?></td>
              <td><?= htmlspecialchars($rsv['reservation_name']) ?></td>
              <td>
                <?php if($rsv['agent_id']): ?>
                  <?= htmlspecialchars($rsv['agent_id']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['agent_id']): ?>
                  <?= htmlspecialchars($rsv['agent_name']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['people']): ?>
                  <?= htmlspecialchars($rsv['people']) ?>
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['gross']): ?>
                  <?= number_format($rsv['gross']) ?>
                <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['net']): ?>
                  <?= number_format($rsv['net']) ?>
                  <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($rsv['pic_id']) ?></td>
              <td><?= htmlspecialchars($rsv['pic']) ?></td>
              <td class="num"><?= htmlspecialchars($rsv['reservation_id']) ?></td>
              <td>
                <?php if($rsv['status'] == 2): ?>
                  <?= htmlspecialchars($rsv['due_date']) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['status'] == 5): ?>
                  <?= htmlspecialchars($rsv['due_date']) ?>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>該当する予約はありません。</p>
      <?php endif; ?>
    </div>
    <div>
      <h2>仮予約</h2>
      <?php if(sizeof($tentatives) > 0): ?>
        <table class="">
          <thead>
            <tr>
              <th>実施日</th>
              <th>ステータス</th>
              <th>ステータス名</th>
              <th>予約名</th>
              <th>エージェントID</th>
              <th>エージェント名</th>
              <th>人数</th>
              <th>売上</th>
              <th>ネット</th>
              <th>担当ID</th>
              <th>担当名</th>
              <th>予約ID</th>
              <th>仮期限</th>
              <th>キャンセル日</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($tentatives as $rsv): ?>
            <tr>
              <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
              <td><?= htmlspecialchars($rsv['status']) ?></td>
              <td><?= htmlspecialchars($rsv['status_name']) ?></td>
              <td><?= htmlspecialchars($rsv['reservation_name']) ?></td>
              <td>
                <?php if($rsv['agent_id']): ?>
                  <?= htmlspecialchars($rsv['agent_id']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['agent_id']): ?>
                  <?= htmlspecialchars($rsv['agent_name']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['people']): ?>
                  <?= htmlspecialchars($rsv['people']) ?>
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['gross']): ?>
                  <?= number_format($rsv['gross']) ?>
                <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['net']): ?>
                  <?= number_format($rsv['net']) ?>
                  <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($rsv['pic_id']) ?></td>
              <td><?= htmlspecialchars($rsv['pic']) ?></td>
              <td class="num"><?= htmlspecialchars($rsv['reservation_id']) ?></td>
              <td>
                <?php if($rsv['status'] == 2): ?>
                  <?= htmlspecialchars($rsv['due_date']) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['status'] == 5): ?>
                  <?= htmlspecialchars($rsv['due_date']) ?>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>該当する予約はありません。</p>
      <?php endif; ?>
    </div>
    <div>
      <h2>キャンセル</h2>
      <?php if(sizeof($cancelleds) > 0): ?>
        <table class="">
          <thead>
            <tr>
              <th>実施日</th>
              <th>ステータス</th>
              <th>ステータス名</th>
              <th>予約名</th>
              <th>エージェントID</th>
              <th>エージェント名</th>
              <th>人数</th>
              <th>売上</th>
              <th>ネット</th>
              <th>担当ID</th>
              <th>担当名</th>
              <th>予約ID</th>
              <th>仮期限</th>
              <th>キャンセル日</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($cancelleds as $rsv): ?>
            <tr>
              <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
              <td><?= htmlspecialchars($rsv['status']) ?></td>
              <td><?= htmlspecialchars($rsv['status_name']) ?></td>
              <td><?= htmlspecialchars($rsv['reservation_name']) ?></td>
              <td>
                <?php if($rsv['agent_id']): ?>
                  <?= htmlspecialchars($rsv['agent_id']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['agent_id']): ?>
                  <?= htmlspecialchars($rsv['agent_name']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['people']): ?>
                  <?= htmlspecialchars($rsv['people']) ?>
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['gross']): ?>
                  <?= number_format($rsv['gross']) ?>
                <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['net']): ?>
                  <?= number_format($rsv['net']) ?>
                  <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($rsv['pic_id']) ?></td>
              <td><?= htmlspecialchars($rsv['pic']) ?></td>
              <td class="num"><?= htmlspecialchars($rsv['reservation_id']) ?></td>
              <td>
                <?php if($rsv['status'] == 2): ?>
                  <?= htmlspecialchars($rsv['due_date']) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['status'] == 5): ?>
                  <?= htmlspecialchars($rsv['due_date']) ?>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>該当する予約はありません。</p>
      <?php endif; ?>
    </div>


  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("footer.php"); ?>

</body>
</html>