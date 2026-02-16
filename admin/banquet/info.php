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
require_once('functions/admin_banquet.php');
$week = array('日', '月', '火', '水', '木', '金', '土');
$today=date('Y-m-d');
$now = date('Y-m-d H:i:s');
$w = date('w');
$wd= $week[$w];
$time= date('H:i');

//有効なお知らせ
$info_live = array();
$sql = "SELECT * FROM `banquet_infos` 
  WHERE
    `start` <= :nowtime 
    AND `end` >= :nowtime 
    AND `status` = 1
  ORDER BY `level` DESC, `start` DESC,`banquet_info_id` DESC";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':nowtime', $now, PDO::PARAM_STR);
$stmt->execute();
$l_count = $stmt->rowCount();
if($l_count > 0){
  $infos = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach($infos as $info){
    $info_live[] = $info;
  }
}

//今後のお知らせ
$info_future = array();
$sql = "SELECT * FROM `banquet_infos` 
  WHERE
    `start` > :nowtime 
    AND `status` = 1
  ORDER BY `start` ASC, `banquet_info_id` DESC";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':nowtime', $now, PDO::PARAM_STR);
$stmt->execute();
$f_count = $stmt->rowCount();
if($f_count > 0){
  $infos = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach($infos as $info){
    $info_future[] = $info;
  }
}

//終了したお知らせ
$info_past = array();
$sql = "SELECT * FROM `banquet_infos` 
  WHERE
    (`end` < :nowtime AND `status` IN(1,2))
    OR (`end` >= :nowtime AND `status` = 2)
  ORDER BY `end` DESC, `banquet_info_id` DESC limit 10";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':nowtime', $now, PDO::PARAM_STR);
$stmt->execute();
$p_count = $stmt->rowCount();
if($p_count > 0){
  $infos = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach($infos as $info){
    $info_past[] = $info;
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
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  

</head>
<body>
<?php include("header.php"); ?>
<main>
  <div class="wrapper">
    <div id="controller">
      <a href="info_entry.php" class="button">お知らせ登録</a>
    </div>
    <div>
      <h1>お知らせ一覧</h1>
      <p>現在時刻: <?= $today ?>(<?=$wd ?>) <?=$time ?></p>
      <p>有効なお知らせ: <?= $l_count ?>件</p>
      <p>今後のお知らせ: <?= $f_count ?>件</p>
    </div>
    <div>
    <?php if($l_count > 0): ?>
      <h2>表示中のお知らせ</h2>
      <table>
        <thead>
          <tr>
            <th>レベル</th>
            <th>タイトル</th>
            <th>内容</th>
            <th>表示期間</th>
            <th>ステータス</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($info_live as $info): ?>
            <?php
            $st= new DateTime($info['start']);
            $startdt= $st->format('Y-m-d H:i');
            $ed= new DateTime($info['end']);
            $enddt= $ed->format('Y-m-d H:i');
            ?>
            <tr>
              <td><?= $info['level'] ?></td>
              <td><a href="info-edit.php?id=<?=$info['banquet_info_id'] ?>"><?= htmlspecialchars($info['title'], ENT_QUOTES, 'UTF-8') ?></a></td>
              <td><?= nl2br(htmlspecialchars($info['content'], ENT_QUOTES, 'UTF-8')) ?></td>
              <td><?= $startdt ?>～<?= $enddt ?></td>
              <td><?= $info['status'] == 1 ? '有効' : '無効' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <h2>表示中のお知らせはありません。</h2>
    <?php endif; ?>
    </div>

    <?php if($f_count > 0): ?>
    <div class="">
      <h2>期日前のおしらせ</h2>
      <table>
        <thead>
          <tr>
            <th>レベル</th>
            <th>タイトル</th>
            <th>内容</th>
            <th>表示期間</th>
            <th>ステータス</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($info_future as $info): ?>
            <?php
            $st= new DateTime($info['start']);
            $startdt= $st->format('Y-m-d H:i');
            $ed= new DateTime($info['end']);
            $enddt= $ed->format('Y-m-d H:i');
            ?>
            <tr>
              <td><?= $info['level'] ?></td>
              <td><a href="info-edit.php?id=<?=$info['banquet_info_id'] ?>"><?= htmlspecialchars($info['title'], ENT_QUOTES, 'UTF-8') ?></a></td>
              <td><?= nl2br(htmlspecialchars($info['content'], ENT_QUOTES, 'UTF-8')) ?></td>
              <td><?= $startdt ?>～<?= $enddt ?></td>
              <td><?= $info['status'] == 1 ? '有効' : '無効' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    
    <?php if($f_count > 0): ?>
    <div class="">
      <h2>終了したおしらせ（10件）</h2>
      <table>
        <thead>
          <tr>
            <th>レベル</th>
            <th>タイトル</th>
            <th>内容</th>
            <th>表示期間</th>
            <th>ステータス</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($info_past as $info): ?>
            <?php
            $st= new DateTime($info['start']);
            $startdt= $st->format('Y-m-d H:i');
            $ed= new DateTime($info['end']);
            $enddt= $ed->format('Y-m-d H:i');
            ?>
            <tr>
              <td><?= $info['level'] ?></td>
              <td><a href="info-edit.php?id=<?=$info['banquet_info_id'] ?>"><?= htmlspecialchars($info['title'], ENT_QUOTES, 'UTF-8') ?></a></td>
              <td><?= nl2br(htmlspecialchars($info['content'], ENT_QUOTES, 'UTF-8')) ?></td>
              <td><?= $startdt ?>～<?= $enddt ?></td>
              <td><?= $info['status'] == 1 ? '有効' : '無効' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>


  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("../common/footer.php"); ?>

</body>
</html>