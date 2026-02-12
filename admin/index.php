<?php
require_once('../common/conf.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
session_name('_NCH_ADMIN');
session_start();
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : '';
$user_name = $_SESSION['name'];

if (empty($user_id) || empty($user_name)) {
  header('Location: ./login.php?error=2');
  exit;
}else{
  $sql = "SELECT * FROM users WHERE user_id = :user_id AND status = 1";
  $stmt = $dbh->prepare($sql);
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $u_count = $stmt->rowCount();
  if ($u_count < 1) {
    header('Location: ./login.php?error=3');
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
  <title>Admin Dashboard</title>
  <link rel="icon" type="image/jpeg" href="./images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">

  <style>
    body{
      font-family: Arial, sans-serif;
      text-align: center;
    }
    div{
      box-sizing: border-box;
    }
    .login_logo{
      width: 100px;
      margin: 0 auto 10px auto;
      display: block;
    }
    .menubox{
      display: flex;
      justify-content: center;
      gap: 20px;
      align-items: center;
      margin-bottom: 20px;
    }
    .menubox div{
      width: 15%;
      align-items: center;

    }
    .menubox a{
      display:flex;
      box-sizing: border-box;
      width: 100%;
      height:60px;
      justify-content: center;
      padding: 15px;
      background-color: #0066ff;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-size: 16px;
      align-items: center;
      font-size:20px;
    }
    .menubox a.banq{
      background-color: #0066ff;
    }
    .menubox a.gstrm{
      background-color: #009933;
    }
    .menubox a.user{
      background-color: #666666;
    }
    .menubox a.admin{
      background-color: #222222;
    }
    .greeting{
      font-size: 24px;
      margin-bottom: 10px;
      font-weight: bold;
    }
    .messagebox{
      background-color: #eee;
      border: 1px solid #ddd;
      padding: 10px;
      margin-bottom: 20px;
      font-size: 16px;
    }
    i{
      margin-right: 10px;
    }
    @media screen and (max-width: 600px) {
      .menubox{
        flex-direction: column;
        gap: 10px;
      }
      .menubox div{
        width: 80%;
      }
    }
  </style>
</head>
<body>
  <div><img src="./images/nch_mark.jpg" alt="NCHマーク" class="login_logo"></div>
  <div class="greeting"><?=$user_name ?>さん、こんにちは！</div>
  <div class="messagebox">ログイン後の画面が変わりました。</div>
  <h1>MENU</h1>
  <div class="menubox">
    <div><a href="./banquet/" class="banq"><i class="fa-solid fa-champagne-glasses"></i> 会議・宴会</a></div>
    <div><a href="./banquet/signage.php" class="banq"><i class="fa-solid fa-display"></i> デジサイ</a></div>
  </div>
  <div class="menubox">
    <div><a href="./guestrooms/" class="gstrm"><i class="fa-solid fa-bed"></i> 客室</a></div>
    <div><a href="./guestrooms/roomindiqr.php" class="gstrm" target="_blank"><i class="fa-solid fa-qrcode"></i> ルームインジ</a></div>
  </div>
  <div class="menubox">
    <div><a href="./user.php" class="user"><i class="fa-regular fa-circle-user"></i> ユーザー情報</a></div>
    <div><a href="./password.php" class="user"><i class="fa-solid fa-lock"></i> パスワード変更</a></div>
    <div><a href="./logout.php" class="user"><i class="fa-solid fa-right-from-bracket"></i> ログアウト</a></div>
  </div>
  <?php if($admin == 1): ?>
  <div class="menubox">
    <div><a href="./users/" class="admin"><i class="fa-solid fa-user-plus"></i> ユーザー管理</a></div>
  </div>
  <?php endif; ?>
  
</body>
</html>