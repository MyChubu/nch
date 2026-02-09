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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Pragma" content="no-cache">
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>マスター編集</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/edit_master.css">
  <link rel="stylesheet" href="css/form.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <div id="controller">
    <div id="controller_left">

    </div>
    <div id="controller_right">

    </div>
  </div>
  <div>
    <h1>マスター編集</h1>
    <div>NEHOPSのマスターデータに合わせてください。</div>
    <div class="master_list_wrapper">
      
      <div class="master_list_item"><a href="edit_agents.php"><i class="fa-solid fa-web-awesome"></i>&nbsp;エージェント</a></div>
      <div class="master_list_item"><a href="edit_categories.php"><i class="fa-solid fa-web-awesome"></i>&nbsp;カテゴリー</a></div>
      <div class="master_list_item"><a href="edit_items.php"><i class="fa-solid fa-web-awesome"></i>&nbsp;アイテム</a></div>
      <div class="master_list_item"><a href="edit_packages.php"><i class="fa-solid fa-web-awesome"></i>&nbsp;パッケージ</a></div>
      <div class="master_list_item"><a href="edit_purposes.php"><i class="fa-solid fa-web-awesome"></i>&nbsp;使用目的</a></div>
      <div class="master_list_item"><a href="edit_rooms.php"><i class="fa-solid fa-web-awesome"></i>&nbsp;宴会場</a></div>
      <div class="master_list_item"><a href="edit_sales_dept.php"><i class="fa-solid fa-web-awesome"></i>&nbsp;売上部門</a></div>
     
    </div>
  </div>


</div>
<?php include("aside.php"); ?>
</main>
<?php include("../common/footer.php"); ?>

</body>
</html>