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
$sql = <<<SQL
SELECT * FROM jsons WHERE json_kind = 1 ORDER BY json_id DESC LIMIT 1
SQL;
$stmt = $dbh->prepare($sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$jsonfile = $result['filename'];
$jsonpath = $_SERVER['DOCUMENT_ROOT'].'/data/json/'.$jsonfile;
if (!file_exists($jsonpath)) {
    http_response_code(404);
    echo json_encode(['error' => 'JSON file not found']);;
    exit;
}
$jsondata = file_get_contents($jsonpath);
$jsonstr = $jsondata;
echo $jsonstr;
?>