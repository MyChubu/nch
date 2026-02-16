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



// POSTされたデータを取得
$json = file_get_contents('php://input');

// JSONをデコードしてPHPの連想配列に変換
$data = json_decode($json, true);

// データが正しく受け取れたか確認
if (json_last_error() === JSON_ERROR_NONE) {
  // 必要なデータを取得
  $scheid = intval($data['scheid']) ?? null;
  $event_name = $data['event_name'] ?? null;
  $enabled = intval($data['enabled']) ?? null;

  // データ検索
  $sql= 'select * from banquet_schedules where banquet_schedule_id = ?';
  $stmt = $dbh->prepare($sql);
  $stmt->execute([$scheid]);
  $count = $stmt->rowCount();
  if($count > 0){
    try{
      $dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $dbh -> beginTransaction();
      $sql = 'update banquet_schedules set event_name = ?, enable = ?, modified = ?, modified_by = ? where banquet_schedule_id = ?';
      $stmt = $dbh->prepare($sql);
      $stmt->execute([$event_name, $enabled, date('Y-m-d H:i:s'), 'admin', $scheid]);
      $dbh -> commit();

      // レスポンスを返す
      header('Content-Type: application/json');
      echo json_encode([
        'status' => 'success',
        'message' => 'Data received successfully',
        'received_data' => $data,
      ]);

    }catch(PDOException $e){
      $dbh -> rollBack();
      $message = $e->getMessage();

      // レスポンスを返す
      header('Content-Type: application/json');
      echo json_encode([
        'status' => 'error',
        'message' => $message,
      ]);

    }
    
  }
  // ログや処理の確認（例: データベースに保存）
  error_log("ID: $scheid, Event Name: $event_name, Enabled: " . ($enabled ? 'true' : 'false'));

  
} else {
  // JSONデコードエラーの場合のレスポンス
  header('Content-Type: application/json', true, 400);
  echo json_encode([
    'status' => 'error',
    'message' => 'Invalid JSON format',
  ]);
}
?>
