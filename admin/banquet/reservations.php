<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$week = array('日', '月', '火', '水', '木', '金', '土');
$date = date('Y-m-d');
$w = date('w');
$wd= $week[$w];
$h= date('H');
$sd = '2025-05-01';
$ed = '2025-09-30';
$sql ="SELECT
    `reservation_id`,
    `reservation_name`,
    `status`,
    `status_name`,
    `agent_id`,
    `agent_name`,
    `agent_short`,
    `agent_name2`,
    `reserver`,
    `pic`,
    `reservation_date`,
    MIN(`start`) as `start`, 
    MAX(`end`) as `end`, 
    MAX(`people`) as `people`,
    SUM(`gross`) as `gross`,
    SUM(`net`) as `net`
  FROM `view_daily_subtotal` 
  WHERE 
    `reservation_date` BETWEEN :sd AND :ed
    AND `status` IN (1,2) 
  GROUP BY `reservation_id` 
  ORDER BY `reservation_date`,`reservation_id`;";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':sd', $sd, PDO::PARAM_STR);
$stmt->bindValue(':ed', $ed, PDO::PARAM_STR);
$stmt->execute();
$count = $stmt->rowCount();
if($count > 0){
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $reservations = [];
}



?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>
    会議・宴会予約リスト
  </title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/reservations.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="js/admin_banquet.js"></script>
  <link rel="stylesheet" href="css/table_sort.css">
  <script src="js/table_sort.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
  <div class="wrapper">
    <div id="controller">
    </div>
    <div>
      <h1>会議・宴会予約リスト</h1>
      <p>本日は<?= $date ?>（<?= $wd ?>）です。</p>

    </div>
    <div>
      <?php if(sizeof($reservations) > 0): ?>
        <table class="banquet-table" id="data-table">
          <thead>
            <tr>
              <th>予約日<span class="sort-arrow"></span></th>
              <th>曜</th>
              <th>日数<span class="sort-arrow"></span></th>
              <th>予約ID<span class="sort-arrow"></span></th>
              <th>予約名<span class="sort-arrow"></span></th>
              <th>販売<span class="sort-arrow"></span></th>
              <th>代理店名<span class="sort-arrow"></span></th>
              <th>担当<span class="sort-arrow"></span></th>
              <th>ステータス<span class="sort-arrow"></span></th>
              <th>人数<span class="sort-arrow"></span></th>
              <th>売上（税抜）<span class="sort-arrow"></span></th>
              <th>売上（税込）<span class="sort-arrow"></span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($reservations as $reservation): ?>
              <?php 
                $start = new DateTime($reservation['start']);
                $end = new DateTime($reservation['end']);
                // 日数計算
                $start->setTime(0, 0, 0);
                $end->setTime(0, 0, 0);
                $diff = $start->diff($end);
                $days = $diff->days +1;

                $dateObj = new DateTime($reservation['reservation_date']);
                $dayOfWeek = $dateObj->format('w');
                $dayName = $week[$dayOfWeek];
              
                
              ?>
              <tr>
                <td><?= htmlspecialchars($reservation['reservation_date']) ?></td>
                <td><?= htmlspecialchars($dayName) ?></td>
                <td><?= htmlspecialchars($days) ?></td>
                <td><?= htmlspecialchars($reservation['reservation_id']) ?></td>
                <td><?= htmlspecialchars(cleanLanternName($reservation['reservation_name'],20)) ?></td>
                <td>
                  <?= $reservation['agent_id']>0 ? $reservation['agent_short']:"直販" ?>
                </td>
                <td>
                  <?php 
                  if($reservation['agent_id'] > 0){
                    if($reservation['agent_name2'] != ""){
                       echo htmlspecialchars($reservation['agent_name2']);
                    } elseif($reservation['reserver'] != ""){ 
                      echo htmlspecialchars($reservation['reserver']);
                    }else {
                      echo htmlspecialchars($reservation['agent_name']);
                    }
                   
                  } else {
                    echo "&nbsp;";
                  }
                  ?>
                </td>
                <td><?= htmlspecialchars(cleanLanternName($reservation['pic'],3)) ?></td>
                <td><?= htmlspecialchars($reservation['status_name']) ?></td>
                
                <td><?= htmlspecialchars($reservation['people']) ?></td>
                <td><?= number_format($reservation['net']) ?></td>
                <td><?= number_format($reservation['gross']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    </div>
      <?php else: ?>
        <p>現在、予約はありません。</p>
      <?php endif; ?>


    


  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("footer.php"); ?>

</body>
</html>