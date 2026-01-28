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
$next_ym = ($nendo + 1) . '-04';

$sales = array();
$sql = "SELECT 
`sales`.`ym`,
COUNT(`sales`.`reservation_id`) AS `count`,
SUM(`sales`.`additional_sales`) AS `additional_sales`,
SUM(`sales`.`subtotal`) AS `subtotal`,
SUM(`sales`.`gross`) AS `gross`,
SUM(`sales`.`net`) AS `net`,
SUM(`sales`.`service_fee`) AS `service_fee`,
SUM(`sales`.`tax`) AS `tax`,
SUM(`sales`.`discount`) AS `discount`,
SUM(`sales`.`ex-ts`) AS `ex-ts`,
SUM(`sales`.`people`) AS `people`
FROM (
  SELECT 
  `ym`,
  `reservation_id`,
  `additional_sales`,
  SUM(`subtotal`) AS `subtotal`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`,
  SUM(`service_fee`) AS `service_fee`,
  SUM(`tax`) AS `tax`,
  SUM(`discount`) AS `discount`,
  SUM(`ex-ts`) AS `ex-ts`,
  SUM(`people`) AS `people`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
  AND `ym` BETWEEN :ym_start AND :ym_end
  AND `sales_category_id` IN (1,2,3,4,5,6)
  GROUP BY `ym`,`reservation_id`, `additional_sales`
  ORDER BY `ym`
 ) AS `sales`
 GROUP BY `ym`
 ORDER BY `ym`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
$stmt->bindValue(':ym_start', $nendo."-04", PDO::PARAM_STR);
$stmt->bindValue(':ym_end', ($nendo+1)."-03", PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
if($count > 0) {
  foreach($results as $result) {
    $sales[] = array(
      'ym' => $result['ym'],
      'count' => $result['count'],
      'additional_sales' => $result['additional_sales'],
      'subtotal' => $result['subtotal'],
      'gross' => $result['gross'],
      'net' => $result['net'],
      'service_fee' => $result['service_fee'],
      'tax' => $result['tax'],
      'discount' => $result['discount'],
      'ex-ts' => $result['ex-ts'],
      'people' => $result['people']
    );
  }
}

$category_sales = array();

$sql = "SELECT
    `ym`,
    `banquet_category_id`,
    `banquet_category_name`,
    COUNT(`reservation_id`) AS `count`,
    SUM(`additional_sales`) AS `additional_sales`,
    SUM(`subtotal`) AS `subtotal`,
    SUM(`gross`) AS `gross`,
    SUM(`net`) AS `net`,
    SUM(`service_fee`) AS `service_fee`,
    SUM(`tax`) AS `tax`,
    SUM(`discount`) AS `discount`,
    SUM(`ex-ts`) AS `ex-ts`,
    SUM(`people`) AS `people`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
  AND `ym` BETWEEN '".$nendo."-04' AND '".($nendo+1)."-03'
  AND `sales_category_id` IN (1,2,3,4,5,6)
  GROUP BY `ym`,`banquet_category_id`,`banquet_category_name`
  ORDER BY `banquet_category_id`,`ym`";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
if($count > 0) {
  foreach($results as $result) {
    $category_sales[] = array(
      'ym' => $result['ym'],
      'banquet_category_id' => $result['banquet_category_id'],
      'banquet_category_name' => $result['banquet_category_name'],
      'count' => $result['count'],
      'additional_sales' => $result['additional_sales'],
      'subtotal' => $result['subtotal'],
      'gross' => $result['gross'],
      'net' => $result['net'],
      'service_fee' => $result['service_fee'],
      'tax' => $result['tax'],
      'discount' => $result['discount'],
      'ex-ts' => $result['ex-ts'],
      'people' => $result['people']
    );
  }
}
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
if(sizeof($sales) > 0) {
  foreach($sales as $row) {
    $total_count += $row['count'];
    $total_additional_sales += $row['additional_sales'];
    $total_sales_count += $row['count'] - $row['additional_sales'];
    $total_subtotal += $row['subtotal'];
    $total_gross += $row['gross'];
    $total_net += $row['net'];
    $total_service_fee += $row['service_fee'];
    $total_tax += $row['tax'];
    $total_discount += $row['discount'];
    $total_ex_ts += $row['ex-ts'];
    $total_people += $row['people'];
  }
}

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
    SUM(`people`) AS `people`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
  AND `ym` BETWEEN '".$nendo."-04' AND '".($nendo+1)."-03'
  AND `sales_category_id` IN (1,2,3,4,5,6)
  GROUP BY `ym`,`sales_category_id`,`sales_category_name`,`reservation_id`
  ORDER BY `sales_category_id`,`ym`
  ) AS `sales`
  GROUP BY `ym`,`sales_category_id`,`sales_category_name`
  ORDER BY `sales_category_id`,`ym`";
 $stmt = $dbh->prepare($sql);
 $stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
 $stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
 $stmt->execute();
 $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
 $count = $stmt->rowCount();
 if($count > 0) {
   foreach($results as $result) {
     $sales_category_sales[] = array(
       'ym' => $result['ym'],
       'sales_category_id' => $result['sales_category_id'],
       'sales_category_name' => $result['sales_category_name'],
       'count' => $result['count'],
        'additional_sales' => $result['additional_sales'],
       'subtotal' => $result['subtotal'],
       'gross' => $result['gross'],
       'net' => $result['net'],
       'service_fee' => $result['service_fee'],
       'tax' => $result['tax'],
       'discount' => $result['discount'],
       'ex-ts' => $result['ex-ts'],
        'people' => $result['people']
     );
   }
   // カテゴリ年計（分母）を作る：sales_category_id ごとに合算
    $cat_year = []; // [catId => ['net'=>..., 'sales_count'=>..., 'people'=>...]]
    foreach ($sales_category_sales as $r) {
      $cat = (int)$r['sales_category_id'];
      $sales_count = (int)$r['count'] - (int)$r['additional_sales'];

      if (!isset($cat_year[$cat])) {
        $cat_year[$cat] = ['net' => 0, 'sales_count' => 0, 'people' => 0];
      }
      $cat_year[$cat]['net']        += (int)$r['net'];
      $cat_year[$cat]['sales_count']+= $sales_count;
      $cat_year[$cat]['people']     += (int)$r['people'];
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
    <div>
      <h2>月別売上</h2>
      <?php if(sizeof($sales) > 0): ?>


        <table>
          <thead>
            <tr>
              <th>年月</th>
              <th>件数</th>
              <th>件数シェア</th>
              <th>人数</th>
              <th>人数シェア</th>
              <th>売上(NET)</th>
              <th>売上シェア</th>

            </tr>
          </thead>
          <tbody>
            <?php foreach($sales as $row): ?>
              <?php $sales_count = $row['count'] - $row['additional_sales']; ?>
              <tr>
                <td><?=$row['ym'] ?></td>
                <td><?=number_format($sales_count) ?></td>
                <td><?=round(($sales_count / $total_sales_count) * 100, 2) ?>%</td>
                <td><?=number_format($row['people']) ?></td>
                <td><?=round(($row['people'] / $total_people) * 100, 2) ?>%</td>
                <td><?=number_format($row['net']) ?></td>
                <td><?=round(($row['net'] / $total_net) * 100, 2) ?>%</td>
              </tr>
            <?php endforeach; ?>
            <tr>
              <td>合計</td>
              <td><?=number_format($total_sales_count) ?></td>
              <td>100%</td>
              <td><?=number_format($total_people) ?></td>
              <td>100%</td>
              <td><?=number_format($total_net) ?></td>
              <td>100%</td>
            </tr>
          </tbody>
        </table>
      <?php else: ?>
        <p>売上データはありません。</p>
      <?php endif; ?>
    </div>

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
        <?php foreach($sales_category_sales as $row): ?>
          <?php
            $cat = $row['sales_category_id'];
            $sales_count = $row['count'] - $row['additional_sales'];
        if($catg != $cat){
              $catg = $cat;
              if($counter > 0) {
                ?>
                <tr>
                  <td colspan="2">合計</td>
                  <td><?=number_format($c_sales_count) ?></td>
                  <td>100%</td>
                  <td><?=number_format($c_people) ?></td>
                  <td>100%</td>
                  <td><?=number_format($c_net) ?></td>
                  <td>100%</td>
                </tr>
              </tbody>
              </table>
                </div>
              <?php
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
                }
              ?>
                <div>
                  <h4><?=$catg==""?"部門なし":"<a href='salescategory.php?nendo=".$nendo."&amp;cat=".$row['sales_category_id']."'>".$row['sales_category_name']."</a>" ?></h4>
                  <table>
                    <thead>
                      <tr>
                        <th>年月</th>
                        <th>部門</th>
                        <th>件数</th>
                        <th>件数シェア</th>
                        <th>人数</th>
                        <th>人数シェア</th>
                        <th>売上(NET)</th>
                        <th>売上シェア</th>
                      </tr>
                    </thead>
                    <tbody>
             <?php  } ?>
              <tr>
                <td><?=$row['ym'] ?></td>
                <td><?= salescatletter($row['sales_category_id']) ?></td>
                <td><?=$sales_count ?></td>
                <td><?=round(($sales_count / $cat_year[$catg]['sales_count']) * 100, 2) ?>%</td>
                <td><?=number_format($row['people']) ?></td>
                <td><?=round(($row['people'] / $cat_year[$catg]['people']) * 100, 2) ?>%</td>
                <td><?=number_format($row['net']) ?></td>
                <td><?=round(($row['net'] / $cat_year[$catg]['net']) * 100, 2) ?>%</td>
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
              <td colspan="2">合計</td>
              <td><?=number_format($c_sales_count) ?></td>
              <td>100%</td>
              <td><?=number_format($c_people) ?></td>
              <td>100%</td>
              <td><?=number_format($c_net) ?></td>
              <td>100%</td>
            </tr>
          </tbody>
        </table>
        </div>
        
        <div>
            <h4>合計</h4>
            <table>
              <thead>
                <tr>
                  <th>年月</th>
                  <th>部門</th>
                  <th>件数</th>
                  <th>件数シェア</th>
                  <th>人数</th>
                  <th>人数シェア</th>
                  <th>売上(NET)</th>
                  <th>売上シェア</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="2">合計</td>
                  <td><?=number_format($total_sales_count) ?></td>
                  <td>100%</td>
                  <td><?=number_format($total_people) ?></td>
                  <td>100%</td>
                  <td><?=number_format($total_net) ?></td>
                  <td>100%</td>
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