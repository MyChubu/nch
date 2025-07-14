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

$sql = "SELECT * FROM users order by status DESC, user_id ASC";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ユーザーアカウント管理</title>
</head>
<body>
<h1>ユーザーアカウント管理</h1>
<div>
<table border="1">
  <thead>
    <tr>
      <th>ユーザーID</th>
      <th>ユーザー名</th>
      <th>メールアドレス</th>
      <th>NEHOPS ID</th>
      <th>ステータス</th>
      <th>操作</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($accounts as $account): ?>
      <tr>
        <td><?php echo htmlspecialchars($account['user_id'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($account['name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($account['mail'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($account['pic_id'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo $account['status'] ? '有効' : '無効'; ?></td>
        <td>
          <a href="edit.php?id=<?php echo $account['user_id']; ?>">詳細・編集</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<div>
  <a href="add.php">新規ユーザー追加</a> 
  <a href="../">管理メニューへ戻る</a>
</div>
</body>
</html>