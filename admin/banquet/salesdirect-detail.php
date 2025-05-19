<?php
require_once('../../common/conf.php');
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




$dbh = new PDO(DSN, DB_USER, DB_PASS);




$sales =array();

$sql = "SELECT 
  `ym`,
  `reservation_date`,
  `reservation_id`,
  `reservation_name`,
  `status`,
  `status_name`,
  `pic_id`,
  `pic`,
  `agent_name2`,
  `reserver`,
  `sales_category_id`,
  `sales_category_name`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`,
  SUM(`service_fee`) AS `service_fee`,
  SUM(`tax`) AS `tax`,
  SUM(`discount`) AS `discount`,
  SUM(`ex-ts`) AS `ex-ts`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
  AND `agent_id` = :agent_id
  GROUP BY `ym`,`reservation_id`,`reservation_date`,`agent_name2`,`reserver`
  ORDER BY `ym`,`reservation_date`,`reservation_id`";


$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
$stmt->bindValue(':agent_id', 0, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if($count > 0) {
  foreach($results as $result) {
    $agent_name2 = $result['agent_name2'];
    $reserver = $result['reserver'];
    if($agent_name2 == "" || $agent_name2 == null || $agent_name2 == " ") {
      $sales_office = $reserver;
    }else {
      $sales_office = $agent_name2;
    }
    $sales[] = array(
      'ym' => $result['ym'],
      'reservation_id' => $result['reservation_id'],
      'reservation_date' => $result['reservation_date'],
      'reservation_name' => $result['reservation_name'],
      'sales_category_id' => $result['sales_category_id'],
      'sales_category_name' => $result['sales_category_name'],
      'pic' => mb_convert_kana($result['pic'],'KVas'),
      'pic_id' => $result['pic_id'],
      'status' => $result['status'],
      'status_name' => $result['status_name'],
      'agent_id' => $result['agent_id'],
      'agent_name' => $result['agent_name'],
      'agent_short' => $result['agent_short'],
      'agent_name2' => $agent_name2,
      'reserver' => $reserver,
      'sales_office' => $sales_office,
      'gross' => $result['gross'],
      'net' => $result['net'],
      'service_fee' => $result['service_fee'],
      'tax' => $result['tax'],
      'discount' => $result['discount'],
      'ex-ts' => $result['ex-ts']
    );
  }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=$agent_name ?> 販売実績（<?=$nendo ?>年度）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/defect_list.css">
  <link rel="stylesheet" href="css/table_sort.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="js/admin_banquet.js"></script>
  <script src="js/table_sort.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <div>
    <form  enctype="multipart/form-data" id="schedate_change">
      <select name="nendo" id="nendo">
        <option value=""></option>
        <?php for($i = 2024; $i<= date('Y') + 1; $i++): ?>
          <option value="<?=$i ?>" <?=($nendo == $i)?"selected":"" ?>><?=$i ?></option>
        <?php endfor; ?>
      </select>年度
      <input type="hidden" name="agent" value="<?=$agent_id ?>">
      <button type="submit">変更</button>
    </form>
    <div id="controller_year">
      <div id="before_year"><a href="?nendo=<?= $before_nendo ?>&amp;agent=<?=$agent_id ?>"><i class="fa-solid fa-arrow-left"></i>前年度</a></div>
      <div id="this_year"><a href="?nendo=<?= $this_nendo ?>&amp;agent=<?=$agent_id ?>">今年度</a></div>
      <div id="after_year"><a href="?nendo=<?= $after_nendo ?>&amp;agent=<?=$agent_id ?>">翌年度<i class="fa-solid fa-arrow-right"></i></a></div>
    
    </div>
  </div>
  <h1><?=$nendo ?>年度販売実績</h1>
  <div>


    <div>
      <h2>直販</h2>
      <?php if(sizeof($sales) > 0): ?>
        <?php
          $counter = 0;
          $total_count = 0;
          $total_gross = 0;
          $total_net = 0;
          $total_service_fee = 0;
          $total_tax = 0;
          $total_discount = 0;
          $total_ex_ts = 0;
          $counter =0;

          $i_count = 0;
          $i_gross = 0;
          $i_net = 0;
          $i_service = 0;
          $i_tax = 0;
          $i_discount =0;
          $i_ex_ts =0;
        ?>
        <table id="data-table">
          <thead>
            <tr>
              <th onclick="sortTable(0)">
                申込団体
                <span class="sort-arrow"></span>
              </th>
              <th onclick="sortTable(1)">担当<span class="sort-arrow"></span></th>
              <th onclick="sortTable(2)">年月<span class="sort-arrow"></span></th>
              <th onclick="sortTable(3)">予約日<span class="sort-arrow"></span></th>
              <th onclick="sortTable(4)">予約ID<span class="sort-arrow"></span></th>
              <th onclick="sortTable(5)">予約名<span class="sort-arrow"></span></th>
              <th onclick="sortTable(6)">予約状況<span class="sort-arrow"></span></th>
              <th onclick="sortTable(7)">カテゴリ<span class="sort-arrow"></span></th>
              <th onclick="sortTable(8)">売上<span class="sort-arrow"></span></th>
              <th onclick="sortTable(9)">純売上<span class="sort-arrow"></span></th>
              <th onclick="sortTable(10)">サービス料<span class="sort-arrow"></span></th>
              <th onclick="sortTable(11)">消費税<span class="sort-arrow"></span></th>
              <th onclick="sortTable(12)">割引<span class="sort-arrow"></span></th>
              <th onclick="sortTable(13)">税・サ抜<span class="sort-arrow"></span></th>
            </tr>
          </thead>
          <tbody>
        <?php foreach($sales as $row):?>
          <?php
            $sales_office = cleanLanternName($row['sales_office']);
          ?>
              <tr>
                <td><?=$sales_office ?></td>
                <td><?=$row['pic'] ?></td>
                <td><?=$row['ym'] ?></td>
                <td><?=$row['reservation_date'] ?></td>
                <td><a href="connection_list.php?resid=<?=$row['reservation_id'] ?>"><?=$row['reservation_id'] ?></a></td>
                <td><?=cleanLanternName($row['reservation_name']) ?></td>
                <td><?=$row['status_name'] ?></td>
                <td><?=$row['sales_category_name'] ?></td>
                <td><?=number_format($row['gross']) ?></td>
                <td><?=number_format($row['net']) ?></td>
                <td><?=number_format($row['service_fee']) ?></td>
                <td><?=number_format($row['tax']) ?></td>
                <td><?=number_format($row['discount']) ?></td>
                <td><?=number_format($row['ex-ts']) ?></td>
              </tr>
              <?php
                $total_count += $row['count'];
                $total_gross += $row['gross'];
                $total_net += $row['net'];
                $total_service_fee += $row['service_fee'];
                $total_tax += $row['tax'];
                $total_discount += $row['discount'];
                $total_ex_ts += $row['ex-ts'];

                $i_count += $row['count'];
                $i_gross += $row['gross'];
                $i_net += $row['net'];
                $i_service += $row['service_fee'];
                $i_tax += $row['tax'];
                $i_discount += $row['discount'];
                $i_ex_ts += $row['ex-ts'];
                $counter++;
              ?>
              
              
            <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="5">合計</td>
                <td><?=sizeof($sales) ?></td>
                <td></td>
                <td></td>
                <td><?=number_format($i_gross) ?></td>
                <td><?=number_format($i_net) ?></td>
                <td><?=number_format($i_service) ?></td>
                <td><?=number_format($i_tax) ?></td>
                <td><?=number_format($i_discount) ?></td>
                <td><?=number_format($i_ex_ts) ?></td>
              </tr>
            </tfoot>
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