<?php
$pw = $_POST['password'] ?? '';

if  (!empty($pw)) {
    $hashedPassword = password_hash($pw, PASSWORD_DEFAULT);
} else {
    $hashedPassword = '';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PW Hash</title>
</head>
<body>
  <h1>パスワードハッシュ</h1>
  <form action="" method="post">
    <label for="password">パスワード:</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">ハッシュ生成</button>
  </form>
  
  <?php if (!empty($hashedPassword)): ?>
    <h2>生成されたハッシュ:</h2>
    <textarea rows="4" cols="50"><?php echo htmlspecialchars($hashedPassword, ENT_QUOTES, 'UTF-8'); ?></textarea>
  <?php endif; ?>
  
</body>
</html>