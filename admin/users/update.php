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
$sql = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
$stmt->execute();
$account = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$account) {
  header('Location: index.php?error=2');
  exit;
}
$koumoku = array('name', 'mail', 'pic_id', 'status', 'admin');
foreach ($koumoku as $key) {
  if (isset($_POST[$key])) {
    $value[$key] = $_POST[$key];
    if($key == 'status') {
      $value[$key] = $_POST[$key] ? 1 : 0; // チェックボックスの値を0または1に変換
    }
    if($key == 'admin') {
      $value[$key] = $_POST[$key] ? 1 : 0; // チェックボックスの値を0または1に変換
    }
    if($key == 'mail') {
      $value[$key] = filter_var($value[$key], FILTER_SANITIZE_EMAIL); // メールアドレスのサニタイズ
      //メールの重複チェック
      $sql = "SELECT * FROM users WHERE mail = :mail AND user_id != :user_id AND status = 1";
      $stmt = $dbh->prepare($sql);
      $stmt->bindParam(':mail', $value[$key], PDO::PARAM_STR);
      $stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
      $stmt->execute();
      if ($stmt->rowCount() > 0) {
        header('Location: edit.php?id=' . $id . '&error=3');
        exit;
      }
    }
  }
}
if (empty($value['name']) || empty($value['mail']) || empty($value['pic_id'])) {
  header('Location: edit.php?id=' . $id . '&error=1');
  exit;
}else{
  $sql = "UPDATE users SET name = :name, mail = :mail, pic_id = :pic_id, status = :status, admin = :admin, modified = now() WHERE user_id = :user_id";
  $stmt = $dbh->prepare($sql);
  $stmt->bindParam(':name', $value['name'], PDO::PARAM_STR);
  $stmt->bindParam(':mail', $value['mail'], PDO::PARAM_STR);
  $stmt->bindParam(':pic_id', $value['pic_id'], PDO::PARAM_STR);
  $stmt->bindParam(':status', $value['status'], PDO::PARAM_INT);
  $stmt->bindParam(':admin', $value['admin'], PDO::PARAM_INT);
  $stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
  if ($stmt->execute()) {
    header('Location: edit.php?id='. $id .'&success=1');
    exit;
  } else {
    header('Location: edit.php?id=' . $id . '&error=2');
    exit;
  }
}