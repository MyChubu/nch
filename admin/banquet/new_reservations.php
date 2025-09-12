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
FROM `view_monthly_new_reservation2` WHERE `reservation_date` >= :sd AND `d_created` BETWEEN :sdt AND :edt
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
    $rsv['orig_status_name'] = rsvOneLetter($rsv['orig_status']);
    if($rsv['status'] ==1 && $rsv['d_decided'] > $ed){
      $rsv['status'] = 2;
      $rsv['status_name'] = '仮';
    }
    if($rsv['status'] ==5 && $rsv['cancel_date'] > $ed){
      $rsv['status'] = 2;
      $rsv['status_name'] = '仮';
    }
    if($rsv['status'] ==2 && $rsv['d_decided']){
      if($rsv['d_decided'] <= $ed){
        $rsv['status'] = 1;
        $rsv['status_name'] = '決';
      }
      
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

function rsvOneLetter($s){
  if($s ==1){
    return '決';
  }elseif($s ==2){
    return '仮';
  }elseif($s ==3){
    return '営';
  }elseif($s ==4){
    return '待';
  }elseif($s ==5){
    return 'C';
  }else{
    return '他';
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
      <div id="download"><a href="output/newrsv-excel-export.php?ym=<?= $ym ?>&mon=<?= $mon ?>&sts=<?= $sts ?>" target="_blank"><i class="fa-solid fa-file-excel"></i>Excel</a></div>
    </div>
    <div>
      <h1>新規獲得リスト（<?= $year_mon ?>）</h1>
      <p><?= $year_mon ?>に新規獲得した予約のリストです。</p>
      <p>「最終」欄は本日（<?= $date ?>）の予約状況を示しています。</p>
    </div>
    <div>
      <h2>決定予約</h2>
      <p><?= $year_mon ?>に獲得し同月中に「決定」したもの</p>
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
<<<<<<< Updated upstream
              <th>メモ</th>
=======
              <th>memo</th>
>>>>>>> Stashed changes
              <th>最終</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($finals as $rsv): ?>
            <tr>
              <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
              <td><?= rsvOneLetter($rsv['status']) ?></td>
              <td><?= htmlspecialchars($rsv['sales_category_name']) ?></td>
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
                  &nbsp;
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
              <td class="num"><a href="connection_list.php?resid=<?= htmlspecialchars($rsv['reservation_id']) ?>"><?= htmlspecialchars($rsv['reservation_id']) ?></a></td>
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
                <?php if($rsv['memo']): ?>
                  <?= nl2br(htmlspecialchars($rsv['memo'])) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              <td>
                <?= htmlspecialchars($rsv['orig_status_name']) ?>
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
<<<<<<< Updated upstream
              <th>メモ</th>
=======
              <th>memo</th>
>>>>>>> Stashed changes
              <th>最終</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($tentatives as $rsv): ?>
            <tr>
              <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
              <td><?= rsvOneLetter($rsv['status']) ?></td>
              <td><?= htmlspecialchars($rsv['sales_category_name']) ?></td>
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
                  &nbsp;
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
              <td class="num"><a href="connection_list.php?resid=<?= htmlspecialchars($rsv['reservation_id']) ?>"><?= htmlspecialchars($rsv['reservation_id']) ?></a></td>
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
                <?php if($rsv['memo']): ?>
                  <?= nl2br(htmlspecialchars($rsv['memo'])) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              <td>
                <?= htmlspecialchars($rsv['orig_status_name']) ?>
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
      <p><?= $year_mon ?>に獲得し同月中に「キャンセル」になったもの</p>
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
<<<<<<< Updated upstream
              <th>メモ</th>
=======
              <th>memo</th>
>>>>>>> Stashed changes
              <th>最終</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($cancelleds as $rsv): ?>
            <tr>
              <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
              <td><?= rsvOneLetter($rsv['status']) ?></td>
              <td><?= htmlspecialchars($rsv['sales_category_name']) ?></td>
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
                  &nbsp;
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
              <td class="num"><a href="connection_list.php?resid=<?= htmlspecialchars($rsv['reservation_id']) ?>"><?= htmlspecialchars($rsv['reservation_id']) ?></a></td>
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
                <?php if($rsv['memo']): ?>
                  <?= nl2br(htmlspecialchars($rsv['memo'])) ?>
                <?php else: ?>
                  &nbsp;
                <?php endif; ?>
              <td>
                <?= htmlspecialchars($rsv['orig_status_name']) ?>
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