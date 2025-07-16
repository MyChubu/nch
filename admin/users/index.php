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
  <link rel="icon" type="image/jpeg" href="../../images/nch_mark.jpg">
  <title>ユーザーアカウント管理</title>
  <link href="https://use.fontawesome.com/releases/v6.2.0/css/all.css" rel="stylesheet">
  <style>
    body { 
      font-family: Arial, sans-serif;
      background-color: #f4f4f4; 
    }
    h1 { color: #333; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: #fff;
    }
    th, td {
      padding: 8px 12px;
      border: 1px solid #ddd;
    
    }
    th {
      position: sticky;
      top: 0;
      background-color: #f2f2f2;
      text-align: center;
    }
    td {
      text-align: left;
    }
    td.text_center {
      text-align: center;
    }
    td.flex{
      display: flex;
      justify-content: center;
      align-items: center;
    }
    a {
      color: #007bff;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    td a{
      display: block;
      padding: 4px 8px;
      background-color: #dadada;
      border-radius: 4px;
      color: #333;
      text-decoration: none;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    td a:hover {
      background-color: #c0c0c0;
      text-decoration: none;
      color: #000;
    }
    .text_green {
      color: green;
      font-size:1.2em;
    }
    .text_gray {
      color: lightgray;
    }
  </style>
</head>
<body>
<h1>ユーザーアカウント管理</h1>
<div>
<table class="">
  <thead>
    <tr>
      <th>ユーザーID</th>
      <th>ユーザー名</th>
      <th>メールアドレス</th>
      <th>NEHOPS ID</th>
      <th>管理者</th>
      <th>ステータス</th>
      <th>操作</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($accounts as $account): ?>
      <tr>
        <td class="text_center"><?php echo htmlspecialchars($account['user_id'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($account['name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($account['mail'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text_center"><?php echo htmlspecialchars($account['pic_id'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text_center"><?php echo $account['admin'] ? '<span class="text_green"><i class="fa-solid fa-square-check"></i></span>' : ''; ?></td>
        <td class="text_center"><?php echo $account['status'] ? '<span class="text_green"><i class="fa-solid fa-square-check"></i></span>' : '<span class="text_gray"><i class="fa-solid fa-square-xmark"></i></span>'; ?></td>
        <td class="flex">
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