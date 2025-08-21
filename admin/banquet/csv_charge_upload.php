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
$sql="select * from csvs where csv_kind = 3 order by csv_id desc limit 50";
$res = $dbh->query($sql);
$count = $res->rowCount();
$csvs=array();
if($count > 0){
  foreach ($res as $value) {
    $csv_id=$value['csv_id'];
    $filename = $value['filename'];
    $csv_kind = $value['csv_kind'];
    $status = $value['status'];
    $status_name = '';
    $status_icon = '';
    if($status == 0){
      $status_name = '処理済';
      $status_icon = '<i class="fa-solid fa-circle-check"></i>';
    }elseif($status == 1){
      $status_name = '処理中';
      $status_icon = '<i class="fa-regular fa-hourglass-half fa-spin"></i>';
    }elseif($status == 80){
      $status_name = 'ファイル削除済';
      $status_icon = '<i class="fa-regular fa-circle-check"></i>';
    }elseif($status == 90){
      $status_name = 'エラー';
      $status_icon = '<i class="fa-regular fa-face-dizzy"></i>';
    }elseif($status == 99){
      $status_name = '除外';
      $status_icon = '<i class="fa-solid fa-xmark"></i>';
    }else{
      $status_name = '未処理';
      $status_icon = '<i class="fa-solid fa-pause"></i>';
    }
    $csvs[] = array(
      'csv_id' => $csv_id,
      'filename' => $filename,
      'csv_kind' => $csv_kind,
      'status' => $status,
      'status_name' => $status_name,
      'status_icon' => $status_icon,
      'added' => $value['added'],
      'modified' => $value['modified']
    );
  }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="60">
  <title>料金CSVアップロード</title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link href="https://use.fontawesome.com/releases/v6.2.0/css/all.css" rel="stylesheet">

</head>
<body>
  <?php include("header.php"); ?>
  <main>
  <div class="wrapper">
    <div class="csv_upload">
      <div class="csv_upload_form">
        <form action="../functions/banq_csv_charge_upload.php" method="post" enctype="multipart/form-data"  id="csv_form">
          <input type="file" name="csvfile" id="">
          <button type="submit">料金CSVアップロード</button>
        </form>
      </div>
      
    </div>
    <div class="csv_list">
      <h2>履歴</h2>
      <?php if(sizeof($csvs) > 0): ?>
        <table class="csv_table">
          <tr>
            <th>ファイル名</th>
            <th>ステータス</th>
            <th>登録日時</th>
            <th>最終更新</th>
          </tr>
        
        <?php foreach($csvs as $csv): ?>
          <tr>
            <td><?=$csv['filename'] ?></td>
            <td><?=$csv['status_icon'] ?></td>
            <td><?=$csv['added'] ?></td>
            <td><?=$csv['modified'] ?></td>
          </tr>
        <?php endforeach; ?>
        </table>
        <div>※最新10件のみ表示</div>
        <div>
        <i class="fa-solid fa-pause"></i>：未処理、
          <i class="fa-solid fa-circle-check"></i>：処理済、
          <i class="fa-regular fa-hourglass-half fa-spin"></i>：処理中、
          <i class="fa-regular fa-circle-check"></i>：ファイル削除済、
          <i class="fa-regular fa-face-dizzy"></i>：エラー、
          <i class="fa-solid fa-xmark"></i>：除外
          
        </div>
      <?php else: ?>
        <div>CSVファイルがありません。</div>
      <?php endif; ?>
      <div><i class="fa-solid fa-book"></i><a href="../../wiki/doku.php?id=csv%E3%81%AE%E3%83%87%E3%83%BC%E3%82%BF%E5%85%A5%E5%8A%9B" target="_blank">オンラインマニュアル（CSVアップロード）</a></div>
    </div>
  </div>
  <?php include("aside.php"); ?>
  </main>
  <?php include("footer.php"); ?>
</body>
</html>