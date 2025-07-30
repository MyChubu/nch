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
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
  header('Location: ../'); // 管理者権限がない場合
  exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>新規ユーザー追加</title>
  <link rel="icon" type="image/jpeg" href="../../images/nch_mark.jpg">
  <link href="https://use.fontawesome.com/releases/v6.2.0/css/all.css" rel="stylesheet">
  <link rel="stylesheet" href="./css/style.css">
</head>
<body>
  <div>
    <h1>新規ユーザー追加</h1>
    <div class="user_info">
      <form action="add_user.php" method="post" enctype="application/x-www-form-urlencoded">
        <table>
          <tr>
            <th>ユーザー名</th>
            <td><input type="text" name="name" required></td>
          </tr>
          <tr>
            <th>メールアドレス</th>
            <td><input type="email" name="mail" id="mail" required></td>
          </tr>
          <tr>
            <th>NEHOPS ID</th>
            <td><input type="text" name="pic_id" id="pic_id" required></td>
          </tr>
          <tr>
            <th>ステータス</th>
            <td>
              <label for="status1">有効</label>
              <input type="hidden" name="status" value="0">
              <input type="checkbox" id="status1" name="status" value="1" checked>
            </td>
          </tr>
        </table>
        <div class="submit_btn_area">
          <button type="submit" onclick="return confirm('追加してよろしいですか？');">追加</button>
        </div>
      </form>
    </div>
    <a href="index.php">戻る</a>
  </div>

<script>

</body>
</html>