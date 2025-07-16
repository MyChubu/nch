<?php
require_once('../common/conf.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
session_name('_NCH_ADMIN');
session_start();
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : '';
$user_name = $_SESSION['name'];

if (empty($user_id) || empty($user_name)) {
  header('Location: login.php?error=2');
  exit;
}else{
  $sql = "SELECT * FROM users WHERE user_id = :user_id AND status = 1";
  $stmt = $dbh->prepare($sql);
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $u_count = $stmt->rowCount();
  if ($u_count < 1) {
    header('Location: login.php?error=3');
    exit;
  }
}
$user_mail = $_SESSION['mail'];
$admin = $_SESSION['admin'];
$error = '';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $currentPw = $_POST['currentPw'] ?? '';
  $newPw = $_POST['newPw'] ?? '';
  $newPwConf = $_POST['newPwConf'] ?? '';

  if (empty($currentPw) || empty($newPw) || empty($newPwConf)) {
    $error = "すべてのフィールドを入力してください。";
  } elseif ($newPw !== $newPwConf) {
    $error = "新しいパスワードと確認用パスワードが一致しません。";
  } else {
    $sql = "SELECT password FROM users WHERE user_id = :user_id";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($currentPw, $user['password'])) {
      $validationResult = validatePassword($newPw);
      if ($validationResult !== true) {
        $error = $validationResult; // パスワードのバリデーションエラー
      } elseif ($newPw === $currentPw) {
        $error = "新しいパスワードは現在のパスワードと異なる必要があります。";
      } else {
        // パスワード更新処理
        $hashedNewPw = password_hash($newPw, PASSWORD_DEFAULT);
        $updateSql = "UPDATE users SET password = :password WHERE user_id = :user_id";
        $updateStmt = $dbh->prepare($updateSql);
        $updateStmt->bindParam(':password', $hashedNewPw, PDO::PARAM_STR);
        $updateStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        if ($updateStmt->execute()) {
          $msg= "パスワードが正常に更新されました。";
        } else {
          $error = "パスワードの更新に失敗しました。";
        }
      }
    } else {
      $error = "現在のパスワードが正しくありません。";
    }
  }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if($msg != ''): ?>
    <meta http-equiv="refresh" content="3; url=./banquet/">
  <?php endif; ?>
  <title>パスワード変更</title>
    <link rel="icon" type="image/jpeg" href="./images/nch_mark.jpg">
   <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .login-container {
      background-color: #fff;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      width: 400px;
    }
    h1 {
      text-align: center;
    }
    label {
      display: block;
      margin-bottom: 5px;
    }
    input[type="email"],
    input[type="password"] {
      width: calc(100% - 10px);
      padding: 5px;
      margin-bottom: 15px;
      font-size: 18px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      width: 100%;
      padding: 10px;
      background-color: #28a745;
      font-size: 18px;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background-color: #218838;
    }
    .error{
      color: red;
      text-align: center;
      margin-top: 10px;
    }
    .msg{
      color: green;
      text-align: center;
      margin-top: 10px;
    }
    .pw-info {
      margin-top: 20px;
      font-size: 12px;
      color: #555;
      width: 400px;
      margin-left:20px;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <form action="" method="post" id="editPassword" enqtype="application/x-www-form-urlencoded">
      <h1>パスワード変更</h1>
      <label for="currentPw">現在のパスワード</label>
      <input type="password" id="currentPw" name="currentPw" required autofocus placeholder="現在のパスワード">
      <br>
      <label for="newPw">新しいパスワード:</label>
      <input type="password" id="newPw" name="newPw" required>
      <br>
      <label for="newPwConf">新しいパスワード(確認):</label>
      <input type="password" id="newPwConf" name="newPwConf" required>
      <button type="submit">パスワード変更</button>
    </form>
  </div>
  <div class="pw-info">
    <p>
    パスワードは8〜30文字で、<br>
    英大文字・英小文字・指定された記号（-_!$%&^#|@./+*）を1文字以上含めてください。<br>
    スペース（半角・全角）や全角文字、半角カナは使用できません。<br>
    使用できない文字が含まれている場合は、エラーメッセージが表示されます。<br>
    パスワードは現在のパスワードと異なる必要があります。<br>
    </p>
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php elseif ($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  </div>
</body>
</html>
<?php
function validatePassword($password) {
  $length = strlen($password);
  if ($length < 8 || $length > 30) {
    return 'パスワードは8〜30文字で入力してください。';
  }

  // 英大文字・英小文字・記号の各1文字以上
  if (!preg_match('/[A-Z]/', $password)) {
    return 'パスワードには英大文字（A〜Z）を1文字以上含めてください。';
  }
  if (!preg_match('/[a-z]/', $password)) {
    return 'パスワードには英小文字（a〜z）を1文字以上含めてください。';
  }
  if (!preg_match('/[-_!$%&^#|@.\/+*]/', $password)) {
    return 'パスワードには指定された記号（-_!$%&^#|@./+*）を1文字以上含めてください。';
  }

  // スペース（半角・全角）禁止
  if (preg_match('/[\s　]/u', $password)) {
    return 'パスワードにスペース（半角・全角）は使用できません。';
  }

  // 全角文字禁止
  if (preg_match('/[^\x00-\x7F]/u', $password)) {
    return 'パスワードに全角文字は使用できません。';
  }

  // 半角カナ禁止（FF61〜FF9FのUnicode範囲）
  if (preg_match('/[\x{FF61}-\x{FF9F}]/u', $password)) {
    return 'パスワードに半角カナは使用できません。';
  }

  // 記号は許可されたものだけ使用
  if (preg_match('/[^a-zA-Z0-9\-_!$%&^#|@.\/+*]/', $password)) {
    return '使用できない文字が含まれています。許可された記号（-_!$%&^#|@./+*）のみ使用してください。';
  }

  return true;
}

?>