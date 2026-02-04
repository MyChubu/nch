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
$sql="select * from jsons where json_kind = 1 order by json_id desc limit 50";
$res = $dbh->query($sql);
$count = $res->rowCount();
$jsons=array();
if($count > 0){
  foreach ($res as $value) {
    $json_id=$value['json_id'];
    $filename = $value['filename'];
    $json_kind = $value['json_kind'];
    $added = $value['added'];
    $jsons[] = array(
      'json_id' => $json_id,
      'filename' => $filename,
      'json_kind' => $json_kind,
      'added' => $added
    );
  }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
  <main>
    <div class="wrapper">
      <div class="json_upload">
        <div class="json_upload_form">
          <form action="../functions/rooms_jsonupload.php" method="post" enctype="multipart/form-data"  id="json_form">
            <input type="file" name="jsonfile" id="">
            <button type="submit">JSONアップロード</button>
          </form>
        </div>
      </div>
      <div class="json_list">
        <h2>アップロード済みJSONファイル一覧</h2>
        <?php if(sizeof($jsons) > 0): ?>
          <table>
            <thead>
              <tr>
                <th>ファイル名</th>
                <th>アップロード日時</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($jsons as $json): ?>
              <tr>
                <td><?=$json['filename']?></td>
                <td><?=$json['added']?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>アップロードされたJSONファイルはありません。</p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</body>
</html>