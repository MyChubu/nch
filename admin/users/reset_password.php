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

$id = $_REQUEST['id'] ?? '';

if (empty($id)) {
  header('Location: index.php?error=1');
  exit;
}
$sql = "SELECT * FROM users WHERE user_id = :user_id AND status = 1";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
$stmt->execute();
$account = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$account) {
  header('Location: index.php?error=2');
  exit;
}
$mail = $account['mail'];
$name = $account['name'];

$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_!$%&^#|@./+*';
$length = 8;
$newPw = '';
for ($i = 0; $i < $length; $i++) {
  $newPw .= $characters[mt_rand(0, strlen($characters) - 1)];
}
$hashedPassword = password_hash($newPw, PASSWORD_DEFAULT);

$sql = "UPDATE users SET int_pw = :newPw, password = :hashed, modified= now() WHERE user_id = :user_id";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':newPw', $newPw, PDO::PARAM_STR);
$stmt->bindParam(':hashed', $hashedPassword, PDO::PARAM_STR);
$stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
$stmt->execute();

mb_language("Japanese");
mb_internal_encoding("UTF-8");
$subject = 'パスワードリセットのお知らせ';
$message = "ユーザー名: $name\nメールアドレス: $mail\n新しいパスワード: $newPw\n\nこのパスワードは次回ログイン時に変更が必要です。\n\nログインURL: ". SITE_URL ."/login.php\n\nこのメールは自動送信されています。返信はできませんのでご了承ください。";
$headers = "From:noreply@nagoyacrown.co.jp\r\n";
mb_send_mail($mail, $subject, $message, $headers);
if(!empty($stmt->errorInfo()[2])) {
  $mes= 'パスワードのリセットに失敗しました。';
} else {
  $mes = 'パスワードをリセットしました。新しいパスワードをメールで送信しました。';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <mete http-equiv="refresh" content="3; url=edit.php?id=<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
  <title>パスワードリセット</title>
</head>
<body>
  <p><?= htmlspecialchars($mes, ENT_QUOTES, 'UTF-8'); ?></p>
  <p>3秒後にユーザー編集画面に戻ります。</p>
</body>
</html>