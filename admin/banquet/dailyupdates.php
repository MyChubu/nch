<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
?>
<?php
require_once('../../common/conf.php');
include_once('../functions/accesslog.php');
require_once('functions/admin_banquet.php');
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


$sql='SELECT 
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  `status`,
  `status_name`,
  `sales_dept_id`,
  `sales_dept_name`,
  `sales_dept_short`,
  `sales_category_id`,
  `sales_category_name`,
  MAX(`people`) AS `people`,
  `pic`,
  `pic_id`,
  SUM(`net`) AS `net`,
  `due_date`,
  `cancel_date`,
  `d_created`,
  `d_mod`,
  `d_decided`,
  `d_tentative`,
  `d_edited`
 FROM `view_daily_subtotal3`
  WHERE
    ((`d_created` BETWEEN :date1 AND :date2) OR (`d_mod` BETWEEN :date1 AND :date2))
  
  GROUP BY `reservation_id`
  ORDER BY `d_mod` ASC,`reservation_date` ASC, `reservation_id` ASC';
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':date1', $date1, PDO::PARAM_STR);
$stmt->bindParam(':date2', $date2, PDO::PARAM_STR);
$stmt->execute();
$count = $stmt->rowCount();
if($count > 0){
  $resvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    データ更新リスト<?= $update_msg ?>
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
        <div id="download"><a href="output/newrsv-excel-export.php?ym=<?= $ym ?>&mon=<?= $mon ?>&sts=<?= $sts ?>" target="_blank"><i class="fa-solid fa-file-excel"></i>Excel</a></div>
        <div class="post-button" data-ym="<?=$ym ?>" data-mode="reload"><i class="fa-solid fa-file-excel"></i>Excel</div>
        <label><input type="checkbox" name="cxl" id="cxl">キャンセルも出力</label>
        <form id="postForm" method="POST"></form>
      </div> -->
    </div>
    <div>
      <h1>データ更新リスト<?=$update_msg?></h1>
    </div>
    <div>
      <?php if($count > 0): ?>
        <table>
          <thead>
            <tr>
              <th>状況</th>
              <th>予約ID</th>
              <th>予約日</th>
              <th>予約名</th>
              <th>売上部門</th>
              <th>人数</th>
              <th>担当</th>
              <th>金額NET</th>
              <th>作成日</th>
              <th>仮押日</th>
              <th>キャンセル日</th>
              <th>決定日</th>
              <th>最終更新</th>
              <th>更新日時</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($resvs as $rsv): ?>
              <?php if($rsv['reservation_name'] != '朝食会場'): ?>
              <tr>
                <td><?= statusletter($rsv['status']) ?></td>
                <td><a href="connection_list2.php?resid=<?= htmlspecialchars($rsv['reservation_id']) ?>"><?= htmlspecialchars($rsv['reservation_id']) ?></a></td>
                <td><?= htmlspecialchars($rsv['reservation_date']) ?></td>
                <td><?= htmlspecialchars($rsv['reservation_name']) ?></td>
                <td><?= salescatletter($rsv['sales_category_id']) ?></td>
                <td><?php if($rsv['people']): ?><?= htmlspecialchars($rsv['people']) ?><?php endif; ?></td>
                <td><?= cleanLanternName(htmlspecialchars($rsv['pic'])) ?></td>
                <td><?php if($rsv['net']): ?><?= number_format($rsv['net']) ?><?php endif; ?></td>
                <td><?php if($rsv['d_created']): ?><?= htmlspecialchars($rsv['d_created']) ?><?php endif; ?></td>
                <td><?php if($rsv['d_tentative']): ?><?= htmlspecialchars($rsv['d_tentative']) ?><?php endif; ?></td>
                <td><?php if($rsv['cancel_date']): ?><?= htmlspecialchars($rsv['cancel_date']) ?><?php endif; ?></td>
                <td><?php if($rsv['d_decided']): ?><?= htmlspecialchars($rsv['d_decided']) ?><?php endif; ?></td>
                <td><?php if($rsv['d_mod']): ?><?= htmlspecialchars($rsv['d_mod']) ?><?php endif; ?></td>
                <td><?php if($rsv['d_edited']): ?><?= htmlspecialchars($rsv['d_edited']) ?><?php endif; ?></td>
              </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>


  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("../common/footer.php"); ?>

</body>
</html>