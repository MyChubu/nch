<?php
$pw="";
$pw = $_POST['password'] ?? '';
$hashedPassword = $_POST['hashedPassword'] ?? '';
$verificationResult = '';
if (!empty($pw) && !empty($hashedPassword)) {
    if (password_verify($pw, $hashedPassword)) {
        $verificationResult = 'パスワードが一致しました。';
    } else {
        $verificationResult = 'パスワードが一致しません。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PW Hash</title>
    <link rel="icon" type="image/jpeg" href="./images/nch_mark.jpg">
</head>
<body>
  <h1>パスワードハッシュ</h1>
  <form action="" method="post">
    <label for="password">パスワード:</label>
    <input type="text" id="password" name="password" value="<?= htmlspecialchars($pw, ENT_QUOTES, 'UTF-8') ?>" required>
    <label for="hashedPassword">ハッシュ:</label>
    <input type="text" id="hashedPassword" name="hashedPassword" value="" required>
    <button type="submit">照合</button>
  </form>
  <?php if(!empty($verificationResult)): ?>
    <h2>照合結果:</h2>
    <p><?php echo htmlspecialchars($verificationResult, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><?= htmlspecialchars($hashedPassword, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

</body>
</html>