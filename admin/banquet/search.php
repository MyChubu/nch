<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

if($_POST['search'] == 1){
  var_dump($_POST);
}

//部屋リスト
$sql = "SELECT `banquet_room_id`, `name`,`floor` FROM `banquet_rooms` WHERE `status` = 1 ORDER BY `order`, `banquet_room_id`";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

//担当者リスト
$sql = "SELECT `pic_id`, `name` FROM `users` WHERE `status` = 1 AND `group` IN(1,5) ORDER BY `user_id`";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$pics = $stmt->fetchAll(PDO::FETCH_ASSOC);

//代理店リスト
$sql = "SELECT `agent_id`, `agent_group` FROM `banquet_agents`  ORDER BY `agent_id`";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    案件検索
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
        <div class="searchbox">
          <form action="search.php" method="post" enctype="multipart/form-data">
          <div>
            予約ID：
            <input type="number" name="reservation_id" id="reservation_id" value="" size="10">
          </div>
          <div>
            実施日：
            <input type="date" name="reservation_date1" id="reservation_date1" value="">
            ～
            <input type="date" name="reservation_date2" id="reservation_date2" value="">
          </div>
          <div>状態：
            <label><input type="checkbox" name="status" value="1">決定</label>
            <label><input type="checkbox" name="status" value="2">仮予約</label>
            <label><input type="checkbox" name="status" value="3">営業押さえ</label>
            <label><input type="checkbox" name="status" value="5">キャンセル</label>
          </div>
          <div>
            予約名：
            <input type="text" name="reservation_name" id="reservation_name" value="" size="20">
          </div>
          <div>
            代理店種類：
            <select name="agent" id="agent">
              <option value="">--</option>
              <option value="0">直販</option>
              <?php foreach($agents as $agent): ?>
                <option value="<?=$agent['agent_id'] ?>"><?=$agent['agent_group'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            代理店名：
            <input type="text" name="agent_name" id="agent_name" value="" size="20">
          </div>
          <div>
            担当者：
            <select name="pic" id="pic">
              <option value="">--</option>
              <?php foreach($pics as $pic): ?>
                <option value="<?=$pic['pic_id'] ?>"><?=$pic['name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            使用会場：
            <?php $flr = ""; ?>
            <?php foreach($rooms as $room): ?>
              <?php if($flr != $room['floor']): ?><br><?php endif; ?>
              <label><input type="checkbox" name="room" id="" value="<?=$room['banquet_room_id'] ?>"><?=$room['name'] ?>　</label>
            <?php $flr = $room['floor']; ?>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="search" value="1">
          <button type="submit">検索</button>
          <button type="reset" onclick="location.href='search.php'">リセット</button>
          </form>
        </div>
      </div>
      <div id="controller_right2">
       
        
      </div>
    </div>
    <div>
      <h1>案件検索</h1>
    </div>
    <div>
      <h2>決定予約</h2>
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
          </tr>
        </thead>
      </table>
    </div>



  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("footer.php"); ?>

</body>
</html>