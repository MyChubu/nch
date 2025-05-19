<?php
require_once('../common/conf.php');
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');
if(isset($_REQUEST['event_date']) && $_REQUEST['event_date'] != '') {
  $today = $_REQUEST['event_date'];
  $now = date('Y-m-d H:i:s', strtotime($today));
}
$hizuke =date('Y年m月d日 ', strtotime($today));
$week = array('日', '月', '火', '水', '木', '金', '土');
$hizuke .= '(' . $week[date('w', strtotime($today))] . '曜日)';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js"></script>
  <script src="js/particles.js"></script>
  <title>会議・宴会予定-<?=$hizuke ?></title>
</head>
<body>

<div id="wrapper">
  <header>
    <div class="headbox">
      <div class="headbox_left">
        <h1>Today's Schedule</h1>
        <h2><?= $hizuke ?></h2>
      </div>
      <div class="headbox_right">
        <div class="headbox_logo">
          <img src="images/NCH_symbol.png" alt="Nagoya Crown Hotel">
        </div>
      </div>
    </div>
  </header>
</div>

</body>
</html>