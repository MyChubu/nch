<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
?>
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

$month = date('n');
if($month < 4) {
  $nendo = date('Y') - 1;
} else {
  $nendo = date('Y');
}

$this_nendo = $nendo;

if(isset($_REQUEST['nendo']) && $_REQUEST['nendo'] != '') {
  $nendo = $_REQUEST['nendo'];
}

$first_day = $nendo . '-04-01';
$last_day = $nendo + 1 . '-03-31';

$before_nendo = $nendo - 1;
$after_nendo = $nendo + 1;

$sales_category_sales = array();
$sql = "SELECT 
  `sales`.`ym`,
  `sales`.`sales_category_id`,
  `sales`.`sales_category_name`,
  COUNT(`reservation_id`) AS `count`,
  SUM(`sales`.`additional_sales`) AS `additional_sales`,
  SUM(`subtotal`) AS `subtotal`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`,
  SUM(`service_fee`) AS `service_fee`,
  SUM(`tax`) AS `tax`,
  SUM(`discount`) AS `discount`,
  SUM(`ex-ts`) AS `ex-ts`,
  SUM(`people`) AS `people`
FROM(
  SELECT 
    `ym`,
    `sales_category_id`,
    `sales_category_name`,
    `reservation_id`,
    `additional_sales`,
    SUM(`subtotal`) AS `subtotal`,
    SUM(`gross`) AS `gross`,
    SUM(`net`) AS `net`,
    SUM(`service_fee`) AS `service_fee`,
    SUM(`tax`) AS `tax`,
    SUM(`discount`) AS `discount`,
    SUM(`ex-ts`) AS `ex-ts`,
    MAX(`people`) AS `people`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
  AND `ym` BETWEEN '".$nendo."-04' AND '".($nendo+1)."-03'
  AND `sales_category_id` IN (1,2,3,4,5,6)
  GROUP BY `ym`,`sales_category_id`,`sales_category_name`,`reservation_id`
  ORDER BY `sales_category_id`,`ym`
  ) AS `sales`
  GROUP BY `ym`,`sales_category_id`,`sales_category_name`
  ORDER BY `ym`,`sales_category_id`";
 $stmt = $dbh->prepare($sql);
 $stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
 $stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
 $stmt->execute();
 $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
 $indexed = [];
foreach ($results as $r) {
  $ym = $r['ym'];
  $cat = (int)$r['sales_category_id'];
  $indexed[$ym][$cat] = [
    'ym' => $ym,
    'sales_category_id' => $cat,
    'sales_category_name' => $r['sales_category_name'],
    'count' => (int)$r['count'],
    'additional_sales' => (int)$r['additional_sales'],
    'subtotal' => (int)$r['subtotal'],
    'gross' => (int)$r['gross'],
    'net' => (int)$r['net'],
    'service_fee' => (int)$r['service_fee'],
    'tax' => (int)$r['tax'],
    'discount' => (int)$r['discount'],
    'ex-ts' => (int)$r['ex-ts'],
    'people' => (int)$r['people'],
  ];
}

$ymList = [];
$start = new DateTimeImmutable(sprintf('%d-04-01', $nendo));
for ($i=0; $i<12; $i++) {
  $ymList[] = $start->modify("+{$i} months")->format('Y-m');
}
$catMaster = [
  1 => '会議',
  2 => '宴会',
  3 => '食事',
  4 => '会議/宴会',
  5 => '会議/食事',
  6 => '会議/宴会/食事'
];

$sales_category_sales = [];

foreach ($ymList as $ym) {
  foreach (range(1,6) as $cat) {
    if (isset($indexed[$ym][$cat])) {
      $sales_category_sales[] = $indexed[$ym][$cat];
    } else {
      // 値が無い組み合わせは0で行を作る
      $sales_category_sales[] = [
        'ym' => $ym,
        'sales_category_id' => $cat,
        'sales_category_name' => $catMaster[$cat] ?? '',
        'count' => 0,
        'additional_sales' => 0,
        'subtotal' => 0,
        'gross' => 0,
        'net' => 0,
        'service_fee' => 0,
        'tax' => 0,
        'discount' => 0,
        'ex-ts' => 0,
        'people' => 0,
      ];
    }
  }
}



?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>売上（<?=$nendo ?>年度）</title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/form.css">
  <link rel="stylesheet" href="css/defect_list.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <div>
    <form  enctype="multipart/form-data" id="schedate_change">
      <select name="nendo" id="nendo">
        <option value=""></option>
        <?php for($i = 2024; $i<= date('Y') + 3; $i++): ?>
          <option value="<?=$i ?>" <?=($nendo == $i)?"selected":"" ?>><?=$i ?></option>
        <?php endfor; ?>
      </select>年度
      
      <button type="submit">変更</button>
    </form>
    <div id="controller_year">
      <div id="before_year"><a href="?nendo=<?= $before_nendo ?>"><i class="fa-solid fa-arrow-left"></i>前年度</a></div>
      <div id="this_year"><a href="?nendo=<?= $this_nendo ?>">今年度</a></div>
      <div id="after_year"><a href="?nendo=<?= $after_nendo ?>">翌年度<i class="fa-solid fa-arrow-right"></i></a></div>
    
    </div>
  </div>
  <h1><?=$nendo ?>年度売上</h1>
  <div>
    <div id="sales_category">
      <h2>カテゴリー別</h2>
      <?php if(sizeof($sales_category_sales) > 0): ?>
        <?php
          $catg = " ";
          $counter = 0;
          $total_count = 0;
          $total_sales_count = 0;
          $total_additional_sales = 0;
          $total_subtotal = 0;
          $total_gross = 0;
          $total_net = 0;
          $total_service_fee = 0;
          $total_tax = 0;
          $total_discount = 0;
          $total_ex_ts = 0;
          $total_people = 0;
          $c_count = 0;
          $c_additional_sales = 0;
          $c_sales_count = 0;
          $c_subtotal = 0;
          $c_gross = 0;
          $c_net = 0;
          $c_service = 0;
          $c_tax = 0;
          $c_discount =0;
          $c_ex_ts =0;
          $c_people =0;
        ?>
        <div>
          <table>
            <thead>
              <tr>
                <th>年</th>
                <th>月</th>
                <!-- <th>部門ID</th> -->
                <th>部門</th>
                <th>人数</th>
                <th>件数</th>
                <th>料理</th>
                <th>飲料</th>
                <th>会場</th>
                <th>ミスク</th>
                <th>他</th>
                <th>純売上</th>
              </tr>
            </thead>
            <tbody>
        <?php foreach($sales_category_sales as $row): ?>
          <?php
            $cat = $row['sales_category_id'];
            $ym= explode("-", $row['ym']);
            $sales_count = $row['count'] - $row['additional_sales'];
         ?>
              <tr>
                <td><?=$ym[0] ?></td>
                <td><?=$ym[1] ?></td>
                <!-- <td><?=$row['sales_category_id'] ?></td> -->
                <td><?=str_replace("/","・",$row['sales_category_name']) ?></td>
                <td><?=number_format($row['people']) ?></td>
                <td><?=$sales_count ?></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td><?=number_format($row['net']) ?></td>
              </tr>
              <?php
                $total_count += $row['count'];
                $total_additional_sales += $row['additional_sales'];
                $total_sales_count += $sales_count;
                $total_subtotal += $row['subtotal'];
                $total_gross += $row['gross'];
                $total_net += $row['net'];
                $total_service_fee += $row['service_fee'];
                $total_tax += $row['tax'];
                $total_discount += $row['discount'];
                $total_ex_ts += $row['ex-ts'];
                $total_people += $row['people'];
                $c_count += $row['count'];
                $c_additional_sales += $row['additional_sales'];
                $c_sales_count += $sales_count;
                $c_subtotal += $row['subtotal'];
                $c_gross += $row['gross'];
                $c_net += $row['net'];
                $c_service += $row['service_fee'];
                $c_tax += $row['tax'];
                $c_discount += $row['discount'];
                $c_ex_ts += $row['ex-ts'];
                $c_people += $row['people'];
                $counter++;
              ?>
            <?php endforeach; ?>
            <tr>
              <td colspan="3">合計</td>
              <td><?=number_format($c_people) ?></td>
              <td><?=number_format($c_sales_count) ?></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td><?=number_format($c_net) ?></td>
            </tr>
          </tbody>
        </table>
        </div>
        
       

      <?php else: ?>
        <p>売上データはありません。</p>
      <?php endif; ?>
    </div>

  


  </div>
  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>