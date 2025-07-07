<?php
require_once('../common/conf.php');

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン</title>
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
  </style>
</head>
<body>
  <div class="login-container">
    <form action="auth.php" method="post" id="loginForm" enqtype="application/x-www-form-urlencoded">
      <h1>ログイン</h1>
      <label for="username">ユーザー名:</label>
      <input type="email" id="username" name="username" required autofocus autocomplete="username" placeholder="メールアドレス">
      <br>
      <label for="password">パスワード:</label>
      <input type="password" id="password" name="password" required>
      <br>
      <?php if (isset($_GET['error'])): ?>
        <div class="error">ユーザー名またはパスワードが正しくありません。</div>
      <?php endif; ?>
      <button type="submit">ログイン</button>
    </form>
  </div>
  
</body>
</html>