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

require_once('functions/admin_banquet.php');

$ym = date('Y-m');
if(isset($_REQUEST['ym']) && $_REQUEST['ym'] != '') {
  $ym = $_REQUEST['ym'];
}


$first_day = new DateTime($ym . '-01');
// 今月（不要であれば省略可）
$this_month = (new DateTime())->format('Y-m');

// 前月
$before_month = clone $first_day;
$before_month->modify('-1 month');
$before_month = $before_month->format('Y-m');

// 翌月
$after_month = clone $first_day;
$after_month->modify('+1 month');
$after_month = $after_month->format('Y-m');

$defects = getDefectList($ym);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>あやしいデータリスト（<?=$this_month ?>以降）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/form.css">
  <link rel="stylesheet" href="css/defect_list.css">
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
  <div>
    <form  enctype="multipart/form-data" id="schedate_change">
      <input type="month" name="ym" id="ym" value="<?= $ym ?>">以降
      <button type="submit">月変更</button>
    </form>
    <div id="controller_month">
      <div id="before_month"><a href="?ym=<?= $before_month ?>"><i class="fa-solid fa-arrow-left"></i>前月</a></div>
      <div id="this_month"><a href="?ym=<?= $this_month ?>">今月</a></div>
      <div id="after_month"><a href="?ym=<?= $after_month ?>">翌月<i class="fa-solid fa-arrow-right"></i></a></div>
      <div id="download"><a href="./output/defact-csv.php?ym=<?=$ym ?>" target="_blank"><i class="fa-solid fa-download"></i>CSV</a></div>
    
    </div>

    <?php if(sizeof($defects) > 0): ?>

      <table id="data-table">
        <thead>
          <tr>
            <th>エラー名<span class="sort-arrow"></span></th>
            <th>状況<span class="sort-arrow"></span></th>
            <th>日付<span class="sort-arrow"></span></th>
            <th>予約ID<span class="sort-arrow"></span></th>
            <th>枝番<span class="sort-arrow"></span></th>
            <th>予約名<span class="sort-arrow"></span></th>
            <th>目的ID<span class="sort-arrow"></span></th>
            <th>目的名<span class="sort-arrow"></span></th>
            <th>部屋ID<span class="sort-arrow"></span></th>
            <th>部屋名<span class="sort-arrow"></span></th>
            <th>担当者<span class="sort-arrow"></span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($defects as $row): ?>
            <?php if(
              //コード記述がインチキです。
              // statusが3で、かつ、purpose_idが3のもの (営業押さえで、コンパニオン控室)
              // statusが1または2で、かつ、purpose_idが35または97のもの (決定予約または仮予約　かつ 朝食(35)またはCI/CO(97))
              // 上記のものは表示しない
              // 本来であれば、上記以外の場合は表示しないようにするべき
              ($row['status'] == 3 && $row['purpose_id'] == 3) ||
              (($row['status'] == 1 || $row['status'] == 2) && ($row['purpose_id'] == 35 || $row['purpose_id'] == 97))
              ): ?>

            <?php else: ?>
              <tr>
                <td><?= $row['error_name'] ?></td>
                <td><?= statusletter($row['status']) ?></td>
                <td><a href="./signage.php?event_date=<?= $row['res_date'] ?>"><?= $row['res_date'] ?></a></td>
                <td><a href="connection_list.php?resid=<?= $row['reservation_id'] ?>"><?= $row['reservation_id'] ?></a></td>
                <td><a href="detail.php?scheid=<?=$row['sche_id'] ?>"><?= $row['branch'] ?></a></td>
                <td><?= $row['reservation_name'] ?></td>
                <td><?= $row['purpose_id'] ?></td>
                <td><?= $row['purpose_name'] ?></td>
                <td><?= $row['room_id'] ?></td>
                <td><?= $row['room_name'] ?></td>
                <td><?= cleanLanternName($row['pic']) ?></td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php else: ?>
      <div class="no-data">
        <p>エラーはありません</p>
      </div>
    <?php endif; ?>
  </div>
  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>