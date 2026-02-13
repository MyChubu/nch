<?php
require_once('../../common/conf.php');
include_once('../functions/accesslog.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
session_name('_NCH_ADMIN');
session_start();
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : '';
$user_name = $_SESSION['name'];
accesslog();

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

$date = htmlspecialchars($_REQUEST['date']);
if($date == ''){
  $date = date('Y-m-d');
}

//明日
$objTommorow = new DateTime('+1 day');//明日
$tomorrow = $objTommorow->format('Y-m-d');


$sql = "SELECT * FROM guestroom_indi_key WHERE date = :date LIMIT 1";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':date', $date, PDO::PARAM_STR);
$stmt->execute();
$count = $stmt->rowCount();
if($count > 0){
  $room_data = $stmt->fetch(PDO::FETCH_ASSOC);
  $keycode = $room_data['keycode'];

  $url='https://sign.nagoyacrown.co.jp/rm/?key='.$keycode;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ROOM SUMMARY QR CODE - <?= htmlspecialchars($date) ?></title>
  <link rel="stylesheet" href="css/roomindiqr.css?<?= date('YmdHis') ?>">
</head>
<body>
  <div class="date-links">
    <a href="roomindiqr.php">TODAY</a>
    <a href="roomindiqr.php?date=<?= $tomorrow ?>">TOMORROW</a>
  </div>
  <div class="current-date"><?= $date ?></div>
  <div>OPEN 09:00 - 16:00</div>
  <?php if($count < 1): ?>
    <div>No QR Code Available</div>
    <div>QRコードは利用できません</div>
  <?php else: ?>
    <div class="qr-code">
      <img src="../functions/create_qrcode.php?data=<?=urlencode($url) ?>&size=20&margin=2" alt="QRコード">
    </div>
    <div class="link-url"><a href="<?= $url ?>"><?= $url ?></a></div>
    <div>This QR code is only valid on<br><?= $date ?>.</div>
    <div>このQRコードは<br><?= $date ?> のみ有効です</div>
  <?php endif; ?>
  
</body>
</html>