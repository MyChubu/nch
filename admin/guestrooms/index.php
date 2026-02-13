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

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>客室管理</title>
  <link rel="icon" type="image/jpeg" href="https://<?= $_SERVER['HTTP_HOST'] ?>/admin/images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="css/style.css?<?=date('YmdHis')?>">
</head>
<body>
  <?php include("header.php"); ?>
  <main>
    <div class="wrapper">
      <div>
        <div><a href="./roomindiqr.php" target="_blank">客室インジ QRコード</a></div>
        <div><a href="./roomsummary.php">客室インジケータ表示</a></div>
        <div><a href="./jsonupload.php">json アップロード</a></div>
      </div>
    </div>
    <?php include("aside.php"); ?>
  </main>
  <?php include("../common/footer.php"); ?>

 
</body>
</html>