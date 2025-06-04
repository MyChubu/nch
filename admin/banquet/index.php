<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$week = array('日', '月', '火', '水', '木', '金', '土');
$date = date('Y-m-d');
$w = date('w');
$wd= $week[$w];
$h= date('H');
if($h < 18){
 $sche_title = '本日のスケジュール';
}else{
  $sche_title = '明日のスケジュール';
}


$sql="SELECT MAX(`date`) as `max_date`, MIN(`date`) as `min_date` FROM `banquet_schedules`";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$min_date = $row['min_date'];
$max_date = $row['max_date'];

$sql2 = "SELECT MAX(`modified`) as `last_update` FROM `banquet_schedules` WHERE `modified_by`='csvdata'";
$stmt2 = $dbh->prepare($sql2);
$stmt2->execute();
$row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
$l_s_update = $row2['last_update'];
$l_s_u = new DateTime($l_s_update) ;
$last_sche_update = $l_s_u->format('Y年m月d日 H:i');


$sql3 ="SELECT MAX(`modified`) as `last_update` FROM `banquet_charges` WHERE `modified_by`='csvdata'";
$stmt3 = $dbh->prepare($sql3);
$stmt3->execute();
$row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
$last_charge_update = $row3['last_update'];

$l_c_update = $row3['last_update'];
$l_c_u = new DateTime($l_c_update) ;
$last_charge_update = $l_c_u->format('Y年m月d日 H:i');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="300">
  <meta name="robots" content="noindex, nofollow">
  <title>会議・宴会サマリー</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/index2.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="js/admin_banquet.js"></script>
  <script src="js/getKaEnData.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
  <div class="wrapper">
    <div id="controller">
    </div>
    <div>
      <h1>会議・宴会サマリー</h1>
      <p>本日は<?= $date ?>（<?= $wd ?>）です。</p>
      <p>会議・宴会の予約状況を確認できます。</p>

    </div>
    <div>
      <div class="update_info">
        <p>最終更新日時（スケジュール）: <?= $last_sche_update ? $last_sche_update : '未更新' ?></p>
        <p>最終更新日時（料金）: <?= $last_charge_update ? $last_charge_update : '未更新' ?></p>
      </div>
      <div class="date_info">
        <p>表示可能期間: <?= $min_date ?> 〜 <?= $max_date ?></p>
        <p>表示可能な日付は、スケジュールの更新により変動する場合があります。</p>
        <p>システムの性質上、表示されたデータが最新ではない場合あります。最新データはNEHOPSでご確認ください。</p>
      </div>
    </div>
    <div class="top_sche_area">
      <div class="top_shcedule">
        <h2><?=$sche_title ?></h2>
        <div id="banquet-schedule">
          <div id="schedate"></div>
          <h2><i class="fa-solid fa-champagne-glasses"></i> 宴会</h2>
          <div id="eventsEn"></div>
          <h2><i class="fa-solid fa-users"></i> 会議</h2>
          <div id="eventsKa"></div>
          <div id="eventsOther"></div>
        </div>
      </div>
      <div class="top_signage">

      </div>
    </div>
    


  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("footer.php"); ?>

</body>
</html>