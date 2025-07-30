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

$id = $_REQUEST['id'] ?? '';
if (empty($id)) {
  header('Location: index.php?error=1');
  exit;
}
$sql = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
$stmt->execute();
$account = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$account) {
  header('Location: index.php?error=2');
  exit;
}
$status = $account['status'];
$message = '';
$error= isset($_GET['error']) ? $_GET['error'] : '';
if ($error == 3) {
  $message = '<p class="text_error">メールアドレスが既に登録されています。</p>';
} elseif ($error == 4) {
  $message = '<p class="text_error">NEHOPS IDが既に登録されています。</p>';
}
$success = isset($_GET['success']) ? $_GET['success'] : '';
if ($success == 1) {
  $message = '<p class="text_success">ユーザー情報を更新しました。</p>';
} elseif ($success == 2) {
  $message = '<p class="text_success">ユーザーを追加しました。</p>';
} elseif ($success == 3) {
  $message = '<p class="text_success">パスワードをリセットしました。新しいパスワードはメールで送信されました。</p>';
}
  
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>ユーザー編集（<?=$account['name'] ?>）</title>
  <link rel="icon" type="image/jpeg" href="../../images/nch_mark.jpg">
  <link href="https://use.fontawesome.com/releases/v6.2.0/css/all.css" rel="stylesheet">
  <link rel="stylesheet" href="./css/style.css">
</head>
<body>
  <div>
    <h2><?= htmlspecialchars($account['name'], ENT_QUOTES, 'UTF-8'); ?>さんの登録情報</h2>
    <?php if ($message): ?>
      <div class="message_area">
        <?= $message; ?>
      </div>
    <?php endif; ?>
    <div class="user_info">
      <form action="update.php" method="post" enqtypeype="application/x-www-form-urlencoded">
        <input type="hidden" name="id" value="<?= htmlspecialchars($account['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
        <table>
          <tr>
            <th>ユーザーID</th>
            <td>
              <?= htmlspecialchars($account['user_id'], ENT_QUOTES, 'UTF-8'); ?></td>
          </tr>
          <tr>
            <th>ユーザー名</th>
            <td>
              <input type="text" name="name" value="<?= htmlspecialchars($account['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </td>
          </tr>
          <tr>
            <th>メールアドレス</th>
            <td>
              <input type="email" name="mail" value="<?= htmlspecialchars($account['mail'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </td>
          </tr>
          <tr>
            <th>NEHOPS ID</th>
            <td>
              <input type="text" name="pic_id" value="<?= htmlspecialchars($account['pic_id'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </td>
          </tr>
          <tr>
            <th>ステータス</th>
            <td>
              <label for="status1">有効</label>
              <input type="hidden" name="status" value="0">
              <input type="checkbox" id="status1" name="status" value="1" <?= $status==1 ? 'checked' : '' ?> <?= $user_id == $id ? 'disabled' : '' ?>>  
            </td>
          </tr>
          <tr>
            <th>管理者</th>
            <td>
              <label for="admin1">はい</label>
              <input type="hidden" name="admin" value="0">
              <input type="checkbox" id="admin1" name="admin" value="1" <?= $account['admin'] == 1 ? 'checked' : '' ?> <?= $user_id == $id ? 'disabled' : '' ?>>
            </td>
          </tr>
          <tr>
            <th>登録日時</th>
            <td><?php echo htmlspecialchars($account['added'], ENT_QUOTES, 'UTF-8'); ?></td>
          </tr>
          <tr>
            <th>更新日時</th>
            <td><?php echo htmlspecialchars($account['modified'], ENT_QUOTES, 'UTF-8'); ?></td>
          </tr>
        </table>
        <div class="submit_btn_area">
          <button type="submit" onclick="return confirm('本当に更新しますか？');">更新</button>
        </div>
        
      </form>
      <p>※ステータスを無効にすると、ログインできなくなります。</p>
    </div>
  </div>
  <div>
    <h2>パスワードリセット</h2>
    <div class="user_info">
      <p>
        パスワードは表示できません。<br>
        ユーザー自身でパスワードを変更する必要があります。
      </p>
      <p>
        パスワードをリセットすると、仮パスワードがユーザーのメールアドレスに送信されます。<br>
        ユーザーは次回ログイン時に、仮パスワードを使用してログインし、パスワードを変更する必要があります。
      </p>
      <form action="reset_password.php" method="post" enqtypeype="application/x-www-form-urlencoded">
        <input type="hidden" name="id" value="<?= htmlspecialchars($account['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
        <p>パスワードをリセットします。新しいパスワードは、次回ログイン時に変更が必要です。</p>
        <div class="submit_btn_area">
          <button type="submit" onclick="return confirm('本当にリセットしますか？');" <?=$status == 1 ? "":"disabled" ?>>パスワードリセット</button>
        </div>
      </form>
    </div>
    <a href="index.php">戻る</a>
  </div>
  
</body>
</html>