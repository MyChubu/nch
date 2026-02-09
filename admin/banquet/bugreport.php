<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
?>
<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
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
require_once('functions/admin_banquet.php');
$week = array('日', '月', '火', '水', '木', '金', '土');



?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>
    バグ報告
  </title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/form.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/reservations.css?<?=date('YmdHis') ?>">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="css/form.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/table_sort.css?<?=date('YmdHis') ?>">
  <script src="js/table_sort.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
  <div class="wrapper">
    <div>
      <h1>バグ報告・要望フォーム</h1>
      <div>
        WEBシステムのバグ報告や機能追加の要望がありましたら、以下のフォームよりご連絡ください。<br>
        内容を確認の上、担当者より折り返しご連絡いたします。
      </div>
    </div>
    <div>
      <form action="output/bugreport_send.php" method="post" enctype="multipart/form-data">
        <table class="form_table">
          <tr>
            <th><label for="title">件名<span class="required">必須</span></label></th>
            <td><input type="text" name="title" id="title" placeholder="件名を入力してください" required></td>
          </tr>
          <tr>
            <th><label for="category">カテゴリ<span class="required">必須</span></label></th>
            <td>
              <select name="category" id="category" required>
                <option value="">選択してください</option>
                <option value="bug">バグ報告</option>
                <option value="request">機能要望</option>
                <option value="other">その他</option>
              </select>
            </td>
          </tr>
          <tr>
            <th><label for="details">詳細内容<span class="required">必須</span></label></th>
            <td><textarea name="details" id="details" rows="10" placeholder="詳細内容を入力してください" required></textarea></td>
          </tr>
          <tr>
            <th><label for="screenshot">スクリーンショット（任意）</label></th>
            <td>
              <div>不具合の画面が分かる場合は、スクリーンショットを添付してください。</div>
              <input type="file" name="screenshot" id="screenshot" accept="image/*">
            </td>
          </tr>
          <tr>
            <th><label for="user">報告者名<span class="required">必須</span></label></th>
            <td><input type="text" name="user" id="user" value="<?=$user_name ?>" required></td>
          </tr>
          <tr>
            <th><label for="email">メールアドレス<span class="required">必須</span></label></th>
            <td><input type="email" name="email" id="email" value="<?=$user_mail ?>" required></td>
          </tr>
        </table>
        <div class="form_buttons">  
          <input type="button" value="送信" onclick="if(confirm('内容を送信してもよろしいですか？')){this.form.submit();}">
          <input type="button" value="リセット" onclick="if(confirm('内容をリセットしてもよろしいですか？')){this.form.reset();}"> 
        </div>
      </form>
    </div>



  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("../common/footer.php"); ?>
</body>
</html>