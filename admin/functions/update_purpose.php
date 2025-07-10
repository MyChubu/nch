<?php
// ▼ エラー表示（開発時のみ）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ▼ DB接続
require_once('../../common/conf.php');

$dbh = new PDO(DSN, DB_USER, DB_PASS);
#$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ▼ JSONの受け取り
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// ▼ 値の取得とバリデーション
$pur_id = $data['pur_id'] ?? null;
$banquet_purpose_id = $data['banquet_purpose_id'] ?? null;
$banquet_purpose_name = $data['banquet_purpose_name'] ?? '';
$banquet_purpose_short = $data['banquet_purpose_short'] ?? '';
$banquet_category_id = $data['banquet_category_id'] ?? null;
$summary_category = $data['summary_category'] ?? null;

if ($pur_id === null) {
  echo json_encode(['success' => false, 'message' => 'pur_idが不正です']);
  exit;
}

// ▼ DB更新
$sql = "UPDATE banquet_purposes
        SET banquet_purpose_id = :banquet_purpose_id,
            banquet_purpose_name = :banquet_purpose_name,
            banquet_purpose_short = :banquet_purpose_short,
            banquet_category_id = :banquet_category_id,
            summary_category = :summary_category
        WHERE banquet_purpose_id = :pur_id";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':banquet_purpose_id', $banquet_purpose_id, PDO::PARAM_INT);
$stmt->bindValue(':banquet_purpose_name', $banquet_purpose_name);
$stmt->bindValue(':banquet_purpose_short', $banquet_purpose_short);
$stmt->bindValue(':banquet_category_id', $banquet_category_id, PDO::PARAM_INT);
$stmt->bindValue(':summary_category', $summary_category, PDO::PARAM_INT);
$stmt->bindValue(':pur_id', $pur_id, PDO::PARAM_INT);

try {
  $stmt->execute();
    echo json_encode([
    'success' => true,
    'message' => '更新成功',
    'banquet_purpose_id' => $banquet_purpose_id // ← 追加
  ]);
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'message' => 'DBエラー: ' . $e->getMessage()]);
}
