<?php
require_once('../common/conf.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
session_name('_NCH_ADMIN');
session_start();
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : '';
$user_name = $_SESSION['name'];

if (empty($user_id) || empty($user_name)) {
  header('Location: login.php?error=2');
  exit;
}else{
  $sql = "SELECT * FROM users WHERE user_id = :user_id AND status = 1";
  $stmt = $dbh->prepare($sql);
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $u_count = $stmt->rowCount();
  if ($u_count < 1) {
    header('Location: login.php?error=3');
    exit;
  }
}
$user_mail = $_SESSION['mail'];
$admin = $_SESSION['admin'];
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$name= $result['name'];
$mail = $result['mail'];
$sys_id = $result['pic_id'];



?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if($msg != ''): ?>
    <meta http-equiv="refresh" content="3; url=./banquet/">
  <?php endif; ?>
  <title>ユーザー情報</title>
   <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .login-container {
      background-color: #fff;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      width: 400px;
    }
    h1 {
      text-align: center;
    }
    label {
      display: block;
      margin-bottom: 5px;
    }
    input[type="email"],
    input[type="password"] {
      width: calc(100% - 10px);
      padding: 5px;
      margin-bottom: 15px;
      font-size: 18px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      width: 100%;
      padding: 10px;
      background-color: #28a745;
      font-size: 18px;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background-color: #218838;
    }
    .error{
      color: red;
      text-align: center;
      margin-top: 10px;
    }
    .msg{
      color: green;
      text-align: center;
      margin-top: 10px;
    }
    .pw-info {
      margin-top: 20px;
      font-size: 12px;
      color: #555;
      width: 400px;
      margin-left:20px;
    }
  </style>
</head>
<body>
  <div>
    <h1><?=$user_name ?>さんの登録情報</h1>
    <div>
      <table>
        <tr>
          <th>USER ID</th>
          <td><?=$user_id ?></td>
        </tr>
        <tr>
          <th>名前</th>
          <td><?=$name ?></td>
        </tr>
        <tr>
          <th>メールアドレス</th>
          <td><?=$mail ?></td>
        </tr>
        <tr>
          <th>システムID</th>
          <td><?=$sys_id ?></td>
        </tr>
        <tr>
          <th>パスワード</th>
          <td><a href="password.php">パスワード変更</a></td>
        </tr>
      </table>
    </div>
  </div>

</body>
</html>