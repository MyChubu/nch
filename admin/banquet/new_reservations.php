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

$ym=$_REQUEST['ym'] ?? date('Y-m');

$sd = $ym . '-01';
$ed = (new DateTime($sd))->modify('last day of +0 month')->format('Y-m-d');

$year_mon = (new DateTime($sd))->format('Y年m月');

$finals= array();
$tentatives= array();
$cancelleds= array();
$zeros= array();

$sql ="SELECT
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  MIN(`status`) AS `status`,
  `sales_category_id`,
  `sales_category_name`,
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
  `memo`
FROM `view_monthly_new_reservation2` 
WHERE `reservation_date` >= :sd 
  AND `d_created` BETWEEN :sdt AND :edt
  AND `reservation_name` NOT LIKE '%名古屋クラウンホテル%'
  AND `reserver` NOT LIKE '%名古屋クラウンホテル%'
  AND `reserver` NOT LIKE '%堀場産業%'
  AND `purpose_id` NOT in (93)
GROUP BY
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  `sales_category_id`,
  `sales_category_name`,
  `agent_id`,
  `agent_name`,
  `agent_short`,
  `pic_id`,
  `pic`,
  `d_created`,
  `d_decided`,
  `d_tentative`,
  `due_date`,
  `cancel_date`
ORDER BY
  `reservation_date`,
  `reservation_id`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':sd', $sd, PDO::PARAM_STR);
$stmt->bindValue(':sdt', $sd, PDO::PARAM_STR);
$stmt->bindValue(':edt', $ed, PDO::PARAM_STR);
$stmt->execute();
$count = $stmt->rowCount();
if($count > 0){
  $rsvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach($rsvs as $rsv) {
    $rsv['orig_status'] = $rsv['status'];
    if($rsv['status'] ==1 && $rsv['d_decided'] > $ed){
      $rsv['status'] = 2;
    }
    if($rsv['status'] ==5 && $rsv['cancel_date'] > $ed){
      $rsv['status'] = 2;
    }
    if($rsv['status'] ==2 && $rsv['d_decided']){
      if($rsv['d_decided'] <= $ed){
        $rsv['status'] = 1;
      }
      
    }
    if($rsv['people'] === null){
      $rsv['people'] = 0;
    }
    if($rsv['net'] === null){
      $rsv['net'] = 0;
    }
    $rsv['zerocheck'] = 0;
    if($rsv['orig_status']!=5 && ($rsv['net'] == 0 || $rsv['people'] == 0)){
      $rsv['zerocheck'] = 1;
    }

    if($rsv['zerocheck'] == 1 && $rsv['status'] != 5){
      $zeros[] = $rsv;
    }

    if($rsv['status'] == 1){
      $finals[] = $rsv;
    }elseif($rsv['status'] == 2){
      $tentatives[] = $rsv;
    }elseif($rsv['status'] == 5 && $rsv['reservation_name'] != '倉庫'){
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
    新規獲得リスト（<?= $year_mon ?>）
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
      <div id="controller_left">
        <form  method="get" enqtype="application/x-www-form-urlencoded">
          <input type="month" name="ym" id="ym" value="<?= htmlspecialchars($ym) ?>" required>
          <button type="submit">表示</button>
        </form>
        </div>
      <div id="controller_right2">
        <div id="download"><a href="output/newrsv-excel-export.php?ym=<?= $ym ?>&mon=<?= $mon ?>&sts=<?= $sts ?>" target="_blank"><i class="fa-solid fa-file-excel"></i>Excel</a></div>
      </div>
    </div>
    <div>
      <h1>新規獲得リスト（<?= $year_mon ?>）</h1>
      <p><?= $year_mon ?>に新規獲得した予約のリストです。</p>
      <p>「最終」欄は本日（<?= $date ?>）の予約状況を示しています。</p>
    </div>

    <div>
      <h2>ZEROチェック</h2>
      <p>新規獲得のうち、人数または金額が0のもの</p>
      <?php if(sizeof($zeros) > 0): ?>
        <table class="">
          <thead>
            <tr>
              <th>実施日</th>
              <th>状態</th>
              <th>種類</th>
              <th>予約名</th>
              <th>販売</th>
              <th>人数</th>
              <th>金額</th>
              <th>担当名</th>
              <th>予約ID</th>
              <th>代理店名</th>
              <th>仮期限</th>
              <th>予約登録</th>
              <th>仮予約日</th>
              <th>キャンセル日</th>
              <th>決定日</th>
              <th>memo</th>
              <th>最終</th>
              <th>ZERO</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($zeros as $rsv): ?>
            <tr>
              <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
              <td><?= statusletter($rsv['status']) ?></td>
              <td><?= salescatletter($rsv['sales_category_id']) ?></td>
              <td><?= cleanLanternName(htmlspecialchars($rsv['reservation_name'])) ?></td>
              <td><?php if($rsv['agent_id']): ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name'])) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['people']): ?>
                  <?= htmlspecialchars($rsv['people']) ?>
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
              <td><?= htmlspecialchars(cleanLanternName($rsv['pic'])) ?></td>
              <td class="num"><a href="connection_list2.php?resid=<?= htmlspecialchars($rsv['reservation_id']) ?>"><?= htmlspecialchars($rsv['reservation_id']) ?></a></td>
              <td>
                <?php if($rsv['agent_id']): ?>
                  <?php if($rsv['agent_id'] == 2999): ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name2'],10)) ?>
                  <?php else: ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name'])) ?>
                  <?php endif; ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['due_date']): ?>
                  <?= htmlspecialchars($rsv['due_date']) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_created']): ?>
                  <?= htmlspecialchars($rsv['d_created']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_tentative']): ?>
                  <?= htmlspecialchars($rsv['d_tentative']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['cancel_date']): ?>
                  <?= htmlspecialchars($rsv['cancel_date']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>   
              </td>
              <td>
                <?php if($rsv['d_decided']): ?>
                  <?= htmlspecialchars($rsv['d_decided']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($rsv['memo']) ?></td>
              <td>
                <?= statusletter($rsv['orig_status']) ?>
              </td>
              <td>
                <?= $rsv['zerocheck']==1 ? '<span class="zero"><i class="fa-solid fa-triangle-exclamation"></i></span>' : '&nbsp;' ?>
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
      <p><?= $year_mon ?>に獲得し同月中は「仮予約」だったもの</p>
      <?php $people_total = 0; ?>
      <?php $net_total = 0; ?>
      <?php if(sizeof($tentatives) > 0): ?>
        <table class="">
          <thead>
            <tr>
              <th>実施日</th>
              <th>状態</th>
              <th>種類</th>
              <th>予約名</th>
              <th>販売</th>
              <th>人数</th>
              <th>金額</th>
              <th>担当名</th>
              <th>予約ID</th>
              <th>代理店名</th>
              <th>仮期限</th>
              <th>予約登録</th>
              <th>仮予約日</th>
              <th>キャンセル日</th>
              <th>決定日</th>
              <th>memo</th>
              <th>最終</th>
              <th>ZERO</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($tentatives as $rsv): ?>
            <tr>
              <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
              <td><?= statusletter($rsv['status']) ?></td>
              <td><?= salescatletter($rsv['sales_category_id']) ?></td>
              <td><?= cleanLanternName(htmlspecialchars($rsv['reservation_name'])) ?></td>
              <td><?php if($rsv['agent_id']): ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name'])) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['people']): ?>
                  <?= htmlspecialchars($rsv['people']) ?>
                  <?php $people_total += $rsv['people']; ?>
                <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['net']): ?>
                  <?= number_format($rsv['net']) ?>
                  <?php $net_total += $rsv['net']; ?>
                  <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars(cleanLanternName($rsv['pic'])) ?></td>
              <td class="num"><a href="connection_list2.php?resid=<?= htmlspecialchars($rsv['reservation_id']) ?>"><?= htmlspecialchars($rsv['reservation_id']) ?></a></td>
              <td>
                <?php if($rsv['agent_id']): ?>
                  <?php if($rsv['agent_id'] == 2999): ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name2'],10)) ?>
                  <?php else: ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name'])) ?>
                  <?php endif; ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['due_date']): ?>
                  <?= htmlspecialchars($rsv['due_date']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_created']): ?>
                  <?= htmlspecialchars($rsv['d_created']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_tentative']): ?>
                  <?= htmlspecialchars($rsv['d_tentative']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['cancel_date']): ?>
                  <?= htmlspecialchars($rsv['cancel_date']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_decided']): ?>
                  <?= htmlspecialchars($rsv['d_decided']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($rsv['memo']) ?></td>
              <td>
                <?= statusletter($rsv['orig_status']) ?>
              </td>
              <td>
                <?= $rsv['zerocheck']==1 ? '<span class="zero"><i class="fa-solid fa-triangle-exclamation"></i></span>' : '&nbsp;' ?> 
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th><?=number_format(sizeof($tentatives)) ?>件</th>
              <th>&nbsp;</th>
              <th><?= number_format($people_total) ?>人</th>
              <th><?= number_format($net_total) ?></th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <p>該当する予約はありません。</p>
      <?php endif; ?>
    </div>

    <div>
      <h2>決定予約</h2>
      <p><?= $year_mon ?>に獲得し同月中に「決定」したもの</p>
      <?php $people_total = 0; ?>
      <?php $net_total = 0; ?>
      <?php if(sizeof($finals) > 0): ?>
        <table class="">
          <thead>
            <tr>
              <th>実施日</th>
              <th>状態</th>
              <th>種類</th>
              <th>予約名</th>
              <th>販売</th>
              <th>人数</th>
              <th>金額</th>
              <th>担当名</th>
              <th>予約ID</th>
              <th>代理店名</th>
              <th>仮期限</th>
              <th>予約登録</th>
              <th>仮予約日</th>
              <th>キャンセル日</th>
              <th>決定日</th>
              <th>memo</th>
              <th>最終</th>
              <th>ZERO</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($finals as $rsv): ?>
            <tr>
              <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
              <td><?= statusletter($rsv['status']) ?></td>
              <td><?= salescatletter($rsv['sales_category_id']) ?></td>
              <td><?= cleanLanternName(htmlspecialchars($rsv['reservation_name'])) ?></td>
              <td><?php if($rsv['agent_id']): ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name'])) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['people']): ?>
                  <?= htmlspecialchars($rsv['people']) ?>
                  <?php $people_total += $rsv['people']; ?>
                <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['net']): ?>
                  <?= number_format($rsv['net']) ?>
                  <?php $net_total += $rsv['net']; ?>
                  <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars(cleanLanternName($rsv['pic'])) ?></td>
              <td class="num"><a href="connection_list2.php?resid=<?= htmlspecialchars($rsv['reservation_id']) ?>"><?= htmlspecialchars($rsv['reservation_id']) ?></a></td>
              <td>
                <?php if($rsv['agent_id']): ?>
                  <?php if($rsv['agent_id'] == 2999): ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name2'],10)) ?>
                  <?php else: ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name'])) ?>
                  <?php endif; ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['due_date']): ?>
                  <?= htmlspecialchars($rsv['due_date']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_created']): ?>
                  <?= htmlspecialchars($rsv['d_created']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_tentative']): ?>
                  <?= htmlspecialchars($rsv['d_tentative']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['cancel_date']): ?>
                  <?= htmlspecialchars($rsv['cancel_date']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_decided']): ?>
                  <?= htmlspecialchars($rsv['d_decided']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($rsv['memo']) ?></td>
              <td>
                <?= statusletter($rsv['orig_status']) ?>
              </td>
              <td>
                <?= $rsv['zerocheck']==1 ? '<span class="zero"><i class="fa-solid fa-triangle-exclamation"></i></span>' : '&nbsp;' ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th><?=number_format(sizeof($finals)) ?>件</th>
              <th>&nbsp;</th>
              <th><?= number_format($people_total) ?>人</th>
              <th><?= number_format($net_total) ?></th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <p>該当する予約はありません。</p>
      <?php endif; ?>
    </div>

    <div>
      <h2>キャンセル</h2>
      <p><?= $year_mon ?>に獲得し同月中に「キャンセル」になったもの</p>
      <?php $people_total = 0; ?>
      <?php $net_total = 0; ?>
      <?php if(sizeof($cancelleds) > 0): ?>
        <table class="">
          <thead>
            <tr>
              <th>実施日</th>
              <th>状態</th>
              <th>種類</th>
              <th>予約名</th>
              <th>販売</th>
              <th>人数</th>
              <th>金額</th>
              <th>担当名</th>
              <th>予約ID</th>
              <th>代理店名</th>
              <th>仮期限</th>
              <th>予約登録</th>
              <th>仮予約日</th>
              <th>キャンセル日</th>
              <th>決定日</th>
              <th>memo</th>
              <th>最終</th>
              <th>ZERO</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($cancelleds as $rsv): ?>
            <tr>
              <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
              <td><?= statusletter($rsv['status']) ?></td>
              <td><?= salescatletter($rsv['sales_category_id']) ?></td>
              <td><?= cleanLanternName(htmlspecialchars($rsv['reservation_name'])) ?></td>
              <td><?php if($rsv['agent_id']): ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name'])) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['people']): ?>
                  <?= htmlspecialchars($rsv['people']) ?>
                  <?php $people_total += $rsv['people']; ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td class="num">
                <?php if($rsv['net']): ?>
                  <?= number_format($rsv['net']) ?>
                  <?php $net_total += $rsv['net']; ?>
                  <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars(cleanLanternName($rsv['pic'])) ?></td>
              <td class="num"><a href="connection_list2.php?resid=<?= htmlspecialchars($rsv['reservation_id']) ?>"><?= htmlspecialchars($rsv['reservation_id']) ?></a></td>
              <td>
                <?php if($rsv['agent_id']): ?>
                  <?php if($rsv['agent_id'] == 2999): ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name2'],10)) ?>
                  <?php else: ?>
                  <?= htmlspecialchars(cleanLanternName2($rsv['agent_name'])) ?>
                  <?php endif; ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['due_date']): ?>
                  <?= htmlspecialchars($rsv['due_date']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_created']): ?>
                  <?= htmlspecialchars($rsv['d_created']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_tentative']): ?>
                  <?= htmlspecialchars($rsv['d_tentative']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['cancel_date']): ?>
                  <?= htmlspecialchars($rsv['cancel_date']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td>
                <?php if($rsv['d_decided']): ?>
                  <?= htmlspecialchars($rsv['d_decided']) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($rsv['memo']) ?></td>
              <td>
                <?= statusletter($rsv['orig_status']) ?>
              </td>
              <td>
                <?= $rsv['zerocheck']==1 ? '<span class="zero"><i class="fa-solid fa-triangle-exclamation"></i></span>' : '&nbsp;' ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th><?=number_format(sizeof($cancelleds)) ?>件</th>
              <th>&nbsp;</th>
              <th><?= number_format($people_total) ?>人</th>
              <th><?= number_format($net_total) ?></th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
              <th>&nbsp;</th>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <p>該当する予約はありません。</p>
      <?php endif; ?>
    </div>


  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("../common/footer.php"); ?>

</body>
</html>