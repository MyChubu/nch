<?php
require_once('../common/conf.php');
session_name('_NCH_ADMIN');
session_start();
$sesid = session_id();
#var_dump($sesid);
$mail = isset($_POST['username']) ? $_POST['username'] : '';
$pass = isset($_POST['password']) ? $_POST['password'] : '';

#var_dump($mail, $pass);

if (empty($mail) || empty($pass)) {
    header('Location: login.php');
    exit;
}
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = "SELECT * FROM users WHERE mail = :mail AND status = 1";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user && password_verify($pass, $user['password'])) {
    $_SESSION['id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['mail'] = $user['mail'];

    header('Location: ./banquet/');
} else {
    header('Location: login.php?error=1');
}
?>