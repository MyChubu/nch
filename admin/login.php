<?php
require_once('../common/conf.php');

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン</title>
</head>
<body>
  <form action="auth.php" method="post" id="loginForm" enqtype="application/x-www-form-urlencoded">
    <h1>ログイン</h1>
    <label for="username">ユーザー名:</label>
    <input type="email" id="username" name="username" required autofocus autocomplete="username" placeholder="メールアドレス">
    <br>
    <label for="password">パスワード:</label>
    <input type="password" id="password" name="password" required>
    <br>
    <button type="submit">ログイン</button>
  
</body>
</html>