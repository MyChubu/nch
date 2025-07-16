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

$koumoku = array('name', 'mail', 'pic_id', 'status');
foreach ($koumoku as $key) { 
  if (isset($_POST[$key])) {
    $value[$key] = mb_convert_kana($_POST[$key], 'KVas', 'UTF-8'); // 全角カタカナを半角カタカナに変換
    if($key == 'status') {
      $value[$key] = $_POST[$key] ? 1 : 0; // チェックボックスの値を0または1に変換
    }
    if($key == 'mail') {
      $value[$key] = filter_var($value[$key], FILTER_SANITIZE_EMAIL); // メールアドレスのサニタイズ
    }
  }
}
$sql = "SELECT * FROM users WHERE mail = :mail AND status = 1";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':mail', $value['mail'], PDO::PARAM_STR);
$stmt->execute();
$u_count = $stmt->rowCount();
if ($u_count > 0) {
  header('Location: add.php?error=3');
  exit;
}

$sql= "SELECT * FROM users WHERE pic_id = :pic_id";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':pic_id', $value['pic_id'], PDO::PARAM_STR);
$stmt->execute();
$u_count = $stmt->rowCount();
if ($u_count > 0) {
  header('Location: add.php?error=4');
  exit;
}
if (empty($value['name']) || empty($value['mail']) || empty($value['pic_id'])) {
  header('Location: add.php?error=1');
  exit;
}else{
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_!$%&^#|@./+*';
  $length = 8;
  $newPw = '';
  for ($i = 0; $i < $length; $i++) {
    $newPw .= $characters[mt_rand(0, strlen($characters) - 1)];
  }
  $hashedPassword = password_hash($newPw, PASSWORD_DEFAULT);
  $sql = "INSERT INTO users (name, mail, pic_id, password, int_pw, status, added, modified) VALUES (:name, :mail, :pic_id, :hashedPassword, :newPw, :status, now(), now())";
  $stmt = $dbh->prepare($sql);
  $stmt->bindParam(':name', $value['name'], PDO::PARAM_STR);
  $stmt->bindParam(':mail', $value['mail'], PDO::PARAM_STR);
  $stmt->bindParam(':pic_id', $value['pic_id'], PDO::PARAM_STR);
  $stmt->bindParam(':hashedPassword', $hashedPassword, PDO::PARAM_STR);
  $stmt->bindParam(':newPw', $newPw, PDO::PARAM_STR);
  $stmt->bindParam(':status', $value['status'], PDO::PARAM_INT);
  if ($stmt->execute()) {
    // 自動採番されたIDを取得
    $lastInsertId = $dbh->lastInsertId();
    // メール送信処理
    if($value['status'] == 1) {
      mb_language("Japanese");
      mb_internal_encoding("UTF-8");
      $subject = '新規ユーザー登録完了のお知らせ';
      $body = "以下の内容で新規ユーザー登録が完了しました。\n\n";
      $body .= "ユーザー名: {$value['name']}\n";
      $body .= "メールアドレス: {$value['mail']}\n";
      $body .= "パスワード: {$newPw}\n\n";
      $body .= "ログインURL: " . SITE_URL . "/login.php\n";
      $body .= "ログイン後、パスワードの変更をお勧めします。\n";
      $body .= "このメールは自動送信されています。";
      $headers = "From:noreply@nagoyacrown.co.jp\r\n";
      mb_send_mail($value['mail'], $subject, $body, $headers);
    }

    header('Location: edit.php?id=' . $lastInsertId . '&success=1');
    exit;
  } else {
    header('Location: add.php?error=5');
    exit;
  }
}
?>