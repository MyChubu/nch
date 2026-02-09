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



if(defined('DATA_DIR')==false){
  define('DATA_DIR', $_SERVER['DOCUMENT_ROOT'].'/data/');
}
if(defined('JSON_DATA_PATH')==false){
  define('JSON_DATA_PATH', DATA_DIR.'json/');
}
$temp_file = $_FILES['jsonfile']['tmp_name']; # 一時ファイル名
$true_file = $_FILES['jsonfile']['name']; # 本来のファイル名
$new_file_name = "rooms_".date("YmdHis").".json";
$filename=JSON_DATA_PATH.$new_file_name;

# is_uploaded_fileメソッドで、一時的にアップロードされたファイルが本当にアップロード処理されたかの確認
if (is_uploaded_file($temp_file)) {
  if (move_uploaded_file($temp_file , $filename )) {
    $sql='insert into jsons (filename, json_kind, status, added) values (?, ?, 1, now())';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$new_file_name, 1]);

    $msg = $new_file_name . "をアップロードしました。";
  
    # 古いファイルを削除する場合はここで削除する
    $sql = "SELECT filename FROM jsons WHERE json_kind = 1 AND filename <> :filename ORDER BY added DESC";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':filename', $new_file_name, PDO::PARAM_STR);
    $stmt->execute();
    $old_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($old_files as $old_file){
      $old_filename = JSON_DATA_PATH . $old_file['filename'];
      if(file_exists($old_filename)){
        unlink($old_filename);
        $sql = "update jsons set status = 0 where filename = :filename";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':filename', $old_file['filename'], PDO::PARAM_STR);
        $stmt->execute();
      }
    }
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
  <meta http-equiv="refresh" content=" 10; url=../guestrooms/jsonupload.php">
  <title>JSONデータアップロード完了（<?=$filename ?>）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">

</head>
<body>

<main>
<div class="wrapper">

<?= $msg ?>
<div><a href="../guestrooms/jsonupload.php">戻る</a></div>
</div>

</main>


</body>
</html>