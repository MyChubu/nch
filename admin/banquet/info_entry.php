<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$week = array('日', '月', '火', '水', '木', '金', '土');
$today=date('Y-m-d');
$now = date('Y-m-d H:i:s');
$w = date('w');
$wd= $week[$w];
$time= date('H:i');


?>
<!DOCTYPE html>

<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="300">
  <meta name="robots" content="noindex, nofollow">
  <title>お知らせ一覧</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="js/admin_banquet.js"></script>

</head>
<body>
<?php include("header.php"); ?>
<main>
  <div class="wrapper">
    <div id="controller">
    </div>
    <div>
      <h2>お知らせ登録</h2>
      <div>
        <form action="info_entry.php" method="post">
          <label for="title">タイトル:</label>
          <input type="text" name="title" id="title" required>
          <label for="content">内容:</label>
          <textarea name="content" id="content" rows="4" required></textarea>
          <label for="start">開始日時:</label>
          <input type="datetime-local" name="start" id="start" value="<?= $now ?>">
          <label for="end">終了日時:</label>
          <input type="datetime-local" name="end" id="end" value="<?= $today ?>T23:59">
          <button type="submit">登録</button>
        </form>
      </div>
    </div>

   

    


  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("footer.php"); ?>

</body>
</html>