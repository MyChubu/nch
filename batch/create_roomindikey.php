<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
require_once('../common/conf.php');

$dbh = new PDO(DSN, DB_USER, DB_PASS);

$tomorrow = new DateTimeImmutable('tomorrow');
$ymd = $tomorrow->format('Y-m-d');
$sql = "SELECT `date` FROM `guestroom_indi_key` WHERE `date` = :ymd";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':ymd', $ymd, PDO::PARAM_STR);
$stmt->execute();
if ($stmt->rowCount() == 0) {
  $randkey = makeRandStr(12);
	$sql = "INSERT INTO `guestroom_indi_key` (`date`, `keycode`, `created_at`) VALUES (:ymd, :randkey, NOW())";
	$stmt = $dbh->prepare($sql);
	$stmt->bindValue(':ymd', $ymd, PDO::PARAM_STR);
	$stmt->bindValue(':randkey', $randkey, PDO::PARAM_STR);
	$stmt->execute();
}


/**
 * ランダム文字列生成 (英数字)
 * $length: 生成する文字数
 */
function makeRandStr($length) {
	$str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
	$r_str = null;
	for ($i = 0; $i < $length; $i++) {
		$r_str .= $str[rand(0, count($str) - 1)];
	}
	return $r_str;
}

?>