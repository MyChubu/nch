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

//デフォルト値
$level = 1; // 通常
$title = ''; // タイトル
$content = ''; // 内容
$start = $today . 'T00:00'; // 開始日時
$end = $today . 'T23:59'; // 終了日時
$status = 1; // 表示ステータス（チェックボックスは1で送信される）

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $koumoku= array('level', 'title', 'content', 'start', 'end', 'status');
  $hissu= array('title', 'content', 'start', 'end'); // 必須項目
  $err_f=0;
  $err_mes=array(); // エラーメッセージを格納する配列
  
  foreach ($koumoku as $key) {
    $$key = isset($_POST[$key]) ? $_POST[$key] : ''; // POSTデータから値を取得
    $$key = htmlspecialchars($$key, ENT_QUOTES, 'UTF-8'); // HTMLエスケープ
    $$key = mb_convert_kana($$key, 'KVas');
  }
  if(isset($status) && $status == 'on') {
    $status = 1; // 変数にも反映
  } else {
    $status = 0; // チェックされていない場合は0
  }

  if($level < 1 || $level > 4) {
    $level = 1; // レベルが不正な場合はデフォルト値に設定
  }

  foreach ($hissu as $key) {
    if (empty($$key)) {
      $err_mes[$key] = '必須項目です。'; // エラーメッセージを追加
    }
  }
  if($start >= $end) {
    $err_mes['date'] = '開始日時は終了日時より前に設定してください。'; // 日付のエラーメッセージ
  }
 
  if (count($err_mes) > 0) {
    $err_f = 1; // エラーがある場合はフラグを立てる
  }

  if ($err_f == 0) {
    //日時をフォーマット
    $start = date('Y-m-d H:i:s', strtotime($start));
    $end = date('Y-m-d H:i:s', strtotime($end));
    // エラーがない場合、データベースに登録
    $stmt = $dbh->prepare("INSERT INTO banquet_infos (level, title, content, start, end, status, added, modified, modified_by) VALUES (:level, :title, :content, :start, :end, :status, now(), now(), 'admin')");
    $stmt->bindParam(':level', $level, PDO::PARAM_INT);
    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt->bindParam(':content', $content, PDO::PARAM_STR);
    $stmt->bindParam(':start', $start, PDO::PARAM_STR);
    $stmt->bindParam(':end', $end, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_INT);
  

    if ($stmt->execute()) {
      header('Location: info.php'); // 登録成功後にリダイレクト
      exit;
    } else {
      $err_mes['db'] = 'データベースへの登録に失敗しました。'; // データベースエラーのメッセージ
    }
  }
  
}


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
  <link rel="stylesheet" href="css/form.css">
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
      <div class="errormes">
        <?php
        // エラーメッセージを表示
        if ($eer_f === 1 && count($err_mes) > 0) {
          foreach ($err_mes as $key => $message) {
            echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
          }
        } elseif (isset($err_mes['db'])) {
          echo '<p>' . htmlspecialchars($err_mes['db'], ENT_QUOTES, 'UTF-8') . '</p>';
        }
        ?>
      </div>
      <div class="form-area">
        <form action="info_entry.php" method="post" enctype="multipart/form-data" id="info-entry-form">
          <div class="form-item">
            <div class="form-left"><label for="level">レベル</label></div>
            <div class="form-right">
              <select name="level" id="level">
                <option value="1"<?=$level==1 ? ' selected':'' ?>>★通常</option>
                <option value="2"<?=$level==2 ? ' selected':'' ?>>★★重要</option>
                <option value="3"<?=$level==3 ? ' selected':'' ?>>★★★超重要</option>
                <option value="4"<?=$level==4 ? ' selected':'' ?>>★★★★非常時</option>
              </select>
            </div>
          </div>
          <div class="form-item">
            <div class="form-left"><label for="start">表示期間:</label></div>
            <div class="form-right">
              <div class="errormes error_datetime"></div>
              <input type="datetime-local" name="start" id="start" value="<?=$start ?>">～<input type="datetime-local" name="end" id="end" value="<?=$end ?>">
              
            </div>
          </div>
          <div class="form-item">
            <div class="form-left"><label for="title">タイトル:</label></div>
            <div class="form-right">
              <div class="errormes"></div>
              <input type="text" name="title" id="title" value="<?=$title ?>" required>
              
            </div>
          </div>
          <div class="form-item">
            <div class="form-left"><label for="content">内容:</label></div>
            <div class="form-right">
              <div class="errormes"></div>
              <textarea name="content" id="content" rows="4" required><?=$content ?></textarea>
            </div>
          </div>
          <div class="form-item">
            <div class="form-left">表示</div>
            <div class="form-right">
              <input type="checkbox" name="status" id="status"<?=$status==1 ? ' checked':'' ?>><label for="status">表示する</lavel>
            </div>
          </div>
          <div class="form-button-area">
            <button type="submit" class="button submit-button">登録</button>
            <button type="reset" name="reset" class="button reset-button">クリア</button>
          </div>
        </form>
      </div>
    </div>

   <!--
    <i class="fa-regular fa-circle-info"></i> 
    <i class="fa-regular fa-triangle-exclamation"></i>
    <i class="fa-regular fa-bell"></i>
    <i class="fa-regular fa-flag"></i>
    <i class="fa-regular fa-crown"></i>
      -->

    


  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("footer.php"); ?>
<script>
function validateDateRange() {
  const startInput = document.getElementById('start');
  const endInput = document.getElementById('end');
  const errorDiv = document.querySelector('.error_datetime');

  const start = new Date(startInput.value);
  const end = new Date(endInput.value);

  if (startInput.value && endInput.value) {
    if (end <= start) {
      errorDiv.textContent = '終了日時は開始日時より後にしてください。';
    } else {
      errorDiv.textContent = '';
    }
  } else {
    // どちらか未入力ならエラーを消しておく（任意で変更可）
    errorDiv.textContent = '';
  }
}

// イベントを追加
document.getElementById('start').addEventListener('change', validateDateRange);
document.getElementById('end').addEventListener('change', validateDateRange);
</script>

</body>
</html>