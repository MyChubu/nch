<?php
session_name('_NCH_ADMIN');
session_start();
$_SESSION = array();//セッションの中身をすべて削除
session_destroy();//セッションを破壊
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="5; URL=login.php">
  <title>ログアウト</title>
    <link rel="icon" type="image/jpeg" href="./images/nch_mark.jpg">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      text-align: center;
    }
    p {
      font-size: 1.2em;
    }
    a {
      color: #007bff;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    .logout_logo {
      display: block;
      margin: 20px auto;
      width: 250px; /* Adjust as needed */
      height: auto; /* Maintain aspect ratio */
    }
  </style>
</head>
<body>
  <div>
    <p>ログアウトしました</p>
    <p><a href="login.php">ログインへ</a></p>
    <img src="./images/nch_logo.png" alt="Nagoya Crown Hotel" class="logout_logo">
  </div>
</body>
</html>
