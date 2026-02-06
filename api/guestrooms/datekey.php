<?php
require_once('../../../common/conf.php');
// CORSヘッダーを設定
header("Access-Control-Allow-Origin: *"); // すべてのオリジンを許可
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // 許可するHTTPメソッド
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // 許可するリクエストヘッダー
header("Content-Type: application/json; charset=UTF-8"); // JSONのコンテンツタイプを設定

// プリフライトリクエスト(OPTIONS)への対応
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit;
}

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$ymd = htmlspecialchars($_GET['date']);
$sql = "SELECT * FROM `guestroom_indi_key` WHERE `date` = :ymd limit 1";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':ymd', $ymd, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$keycode=$result['keycode'];

?>