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


$new_file_name = "digisign_".date("YmdHis").".csv";
if(defined('DATA_DIR')==false){
  define('DATA_DIR', $_SERVER['DOCUMENT_ROOT'].'/data/');
}
if(defined('CSV_DATA_PATH')==false){
  define('CSV_DATA_PATH', DATA_DIR.'csv/');
}
$temp_file = $_FILES['csvfile']['tmp_name']; # 一時ファイル名
$true_file = $_FILES['csvfile']['name']; # 本来のファイル名
$filename=CSV_DATA_PATH.$new_file_name;

# is_uploaded_fileメソッドで、一時的にアップロードされたファイルが本当にアップロード処理されたかの確認
if (is_uploaded_file($temp_file)) {
  if (move_uploaded_file($temp_file , $filename )) {
    $sql='insert into csvs (filename, csv_kind, status, added, modified) values (?, ?, ?, now(), now())';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$new_file_name, 2, 2]);

    $msg = $new_file_name . "をアップロードしました。";
  } else {
    $msg = "ファイルをアップロードできません。";
  }
} else {
  $msg = "ファイルが選択されていません。";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content=" 5; url=../banquet/csvupload.php">
  <title>CSVデータアップロード完了（<?=$filename ?>）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="../banquet/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <!--<script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>-->
  <!--<script src="js/admin_banquet.js"></script>-->
</head>
<body>
<?php include("../banquet/header.php"); ?>
<main>
<div class="wrapper">

<?= $msg ?>
<div><a href="../banquet/csvupload.php">戻る</a></div>


  
</div>
<?php include("../banquet/aside.php"); ?>
</main>
<?php include("../banquet/footer.php"); ?>

</body>
</html>