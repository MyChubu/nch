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
require_once('functions/admin_banquet.php');
$week = array('日', '月', '火', '水', '木', '金', '土');
$date = date('Y-m-d');
$w = date('w');
$wd= $week[$w];

$ym=$_REQUEST['ym'] ?? date('Y-m');
$mon = $_REQUEST['mon'] ?? 3;


$sts = $_REQUEST['sts'] ?? 'all';

if($sts == 'final'){
  $status_arr = "(1)";
}elseif($sts == 'tentative') {
  $status_arr = "(2)";
}else{
  $status_arr = "(1,2)";
}

$sd = $ym . '-01';
$edate = new DateTime($sd);
switch($mon){
  case 1:
    $ed = $edate->modify('last day of this month')->format('Y-m-d');
    break;
  case 2://翌月の最終日
    $ed = $edate->modify('last day of next month')->format('Y-m-d');
    break;
  case 3: //2ヶ月後の最終日
    $ed = $edate->modify('last day of +2 month')->format('Y-m-d');
    break;
  case 6:
    $ed = $edate->modify('last day of +5 month')->format('Y-m-d');
    break;
  case 12:
    $ed = $edate->modify('last day of +11 month')->format('Y-m-d');
    break;
  default:
    $ed = date('Y-m-t', strtotime($sd . ' +2 month'));
}

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
    `due_date`,
    `sales_category_id`,
    MIN(`start`) as `start`, 
    MAX(`end`) as `end`, 
    MAX(`people`) as `people`,
    SUM(`gross`) as `gross`,
    SUM(`net`) as `net`
  FROM `view_daily_subtotal` 
  WHERE 
    `reservation_date` BETWEEN :sd AND :ed
    AND `status` IN " . $status_arr . "
    AND `additional_sales` = 0
  GROUP BY `reservation_id`, `status`
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

#var_dump($reservations);


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
    会議・宴会予約リスト
  </title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/form.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/reservations.css?<?=date('YmdHis') ?>">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  
  <link rel="stylesheet" href="css/table_sort.css?<?=date('YmdHis') ?>">
  <script src="js/table_sort.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
  <div class="wrapper">
    <div id="controller">
      <form  method="get" enqtype="application/x-www-form-urlencoded">
        <input type="month" name="ym" id="ym" value="<?= htmlspecialchars($ym) ?>" required> から
        <select name="mon" id="mon">
          <option value="1" <?= $mon == 1 ? 'selected' : '' ?>>1ヶ月</option>
          <option value="2" <?= $mon == 2 ? 'selected' : '' ?>>2ヶ月</option>
          <option value="3" <?= $mon == 3 ? 'selected' : '' ?>>3ヶ月</option>
          <option value="6" <?= $mon == 6 ? 'selected' : '' ?>>6ヶ月</option>
          <option value="12" <?= $mon == 12 ? 'selected' : '' ?>>1年</option>
        </select>
        
        <select name="sts" id="sts">
          <option value="all" <?= $sts == 'all' ? 'selected' : '' ?>>すべて</option>
          <option value="final" <?= $sts == 'final' ? 'selected' : '' ?>>決定のみ</option>
          <option value="tentative" <?= $sts == 'tentative' ? 'selected' : '' ?>>仮予約のみ</option>
        </select>
        <button type="submit">表示</button>
      </form>
    </div>
    <div>
      <h1>会議・宴会予約リスト</h1>
      <p>本日は<?= $date ?>（<?= $wd ?>）です。</p>

    </div>
    <div>
      <div><?= $sd ?> から <?= $ed ?> までの予約を表示しています。</div>
      <?php if(sizeof($reservations) > 0): ?>
        
        <table class="banquet-table" id="data-table">
          <thead>
            <tr>
              <th class="cell_w100"><i class="fa-solid fa-calendar-days"></i></th>
              <th class="cell_w40">日</th>
              <th class="cell_w50"><i class="fa-solid fa-hashtag"></i></th>
              <th>予約名</th>
              <th class="cell_w40"><i class="fa-solid fa-flag"></i></th>
              <th><i class="fa-solid fa-building"></i></th>
              <th class="cell_w50"><i class="fa-solid fa-user"></i></th>
              <th class="cell_w30"><i class="fa-solid fa-signal"></i></th>
              <th class="cell_w30">部門</th>
              <th class="cell_w30"><i class="fa-solid fa-users"></i></th>
              <th class="cell_w100">売上（税抜）</th>
              <th class="cell_w100">売上（税込）</th>
              <th class="cell_w100">仮期限</th>
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
                <td class="cell_w100"><?= htmlspecialchars($reservation['reservation_date']) ?> (<?= htmlspecialchars($dayName) ?>)</td>
                <td class="cell_w40"><?= htmlspecialchars($days) ?></td>
                <td class="cell_w50"><a href="connection_list.php?resid=<?=htmlspecialchars($reservation['reservation_id']) ?>"><?= htmlspecialchars($reservation['reservation_id']) ?></a></td>
                <td><?= htmlspecialchars(cleanLanternName($reservation['reservation_name'],20)) ?></td>
                <td class="cell_w40">
                  <?= $reservation['agent_id']>0 ? $reservation['agent_short']:"直販" ?>
                </td>
                <td>
                  <?php 
                  if($reservation['agent_id'] > 0){
                    if($reservation['agent_name2'] != "" && $reservation['agent_name2'] != " "){
                       echo htmlspecialchars(cleanLanternName2($reservation['agent_name2'],30));
                    } elseif($reservation['reserver'] != "" && $reservation['reserver'] != " "){ 
                      echo htmlspecialchars(cleanLanternName2($reservation['reserver'],30));
                    }else {
                      echo htmlspecialchars(cleanLanternName2($reservation['agent_name'],30));
                    }
                   
                  } else {
                    echo "&nbsp;";
                  }
                  ?>
                </td>
                <td class="cell_w50"><?= htmlspecialchars(cleanLanternName($reservation['pic'],3)) ?></td>
                <td class="cell_w30"><?=statusletter($reservation['status']) ?></td>
                <td class="cell_w30"><?= salescatletter($reservation['sales_category_id']) ?></td>
                <td class="cell_w30"><?= htmlspecialchars($reservation['people']) ?></td>
                <td class="cell_w100"><?= number_format($reservation['net']) ?></td>
                <td class="cell_w100"><?= number_format($reservation['gross']) ?></td>
                <td class="cell_w100">
                  <?php if ($reservation['status'] != 1): ?>
                    <?php if($date > $reservation['due_date']): ?>
                      <span class="text_red text_bold"><?= htmlspecialchars($reservation['due_date']) ?></span>
                    <?php else: ?>
                      <?= htmlspecialchars($reservation['due_date']) ?>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
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