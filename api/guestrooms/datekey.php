<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
?>
<?php
require_once('../../common/conf.php');
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
$sql = "SELECT `date`,`keycode` AS `key` FROM `guestroom_indi_key` WHERE `date` = :ymd limit 1";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':ymd', $ymd, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($result);
?>