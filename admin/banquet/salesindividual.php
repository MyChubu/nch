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

$pic_id ="";
if(isset($_REQUEST['pic']) && $_REQUEST['pic'] != '') {
  $pic_id = $_REQUEST['pic'];
}


$dbh = new PDO(DSN, DB_USER, DB_PASS);


$individual_sales =array();

$sql = "SELECT 
  `ym`,
  `reservation_date`,
  `reservation_id`,
  `reservation_name`,
  'status',
  `status_name`,
  `pic`,
  `pic_id`,
  `sales_category_id`,
  `sales_category_name`,
  `agent_id`,
  `agent_short`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`,
  SUM(`service_fee`) AS `service_fee`,
  SUM(`tax`) AS `tax`,
  SUM(`discount`) AS `discount`,
  SUM(`ex-ts`) AS `ex-ts`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
  AND `pic_id` = :pic_id
  GROUP BY `ym`,`reservation_id`,`pic`,`pic_id`,`reservation_date`
  ORDER BY `ym`,`reservation_date`,`reservation_id`";


$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
$stmt->bindValue(':pic_id', $pic_id, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if($count > 0) {
  foreach($results as $result) {
    $agent_short = $result['agent_short'];
    if($result['agent_id'] == 0) {
      $agent_short = "直販";
    }
    $individual_sales[] = array(
      'ym' => $result['ym'],
      'reservation_id' => $result['reservation_id'],
      'reservation_date' => $result['reservation_date'],
      'reservation_name' => $result['reservation_name'],
      'sales_category_id' => $result['sales_category_id'],
      'sales_category_name' => $result['sales_category_name'],
      'pic' => mb_convert_kana($result['pic'],'KVas'),
      'pic_id' => $result['pic_id'],
      'agent_id' => $result['agent_id'],
      'agent_short' => $agent_short,
      'status' => $result['status'],
      'status_name' => $result['status_name'],
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
  <title>売上（<?=$nendo ?>年度）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
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
      <select name="nendo" id="nendo">
        <option value=""></option>
        <?php for($i = 2024; $i<= date('Y') + 1; $i++): ?>
          <option value="<?=$i ?>" <?=($nendo == $i)?"selected":"" ?>><?=$i ?></option>
        <?php endfor; ?>
      </select>年度
      <input type="hidden" name="pic" value="<?=$pic_id ?>">
      <button type="submit">変更</button>
    </form>
    <div id="controller_year">
      <div id="before_year"><a href="?nendo=<?= $before_nendo ?>&amp;pic=<?=$pic_id ?>"><i class="fa-solid fa-arrow-left"></i>前年度</a></div>
      <div id="this_year"><a href="?nendo=<?= $this_nendo ?>&amp;pic=<?=$pic_id ?>">今年度</a></div>
      <div id="after_year"><a href="?nendo=<?= $after_nendo ?>&amp;pic=<?=$pic_id ?>">翌年度<i class="fa-solid fa-arrow-right"></i></a></div>
    
    </div>
  </div>
  <h1><?=$nendo ?>年度売上</h1>
  <div>


    <div>
      <h2>担当別</h2>
      <?php if(sizeof($individual_sales) > 0): ?>
        <?php
          $picn = " ";
          $picn_id = " ";
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
        <?php foreach($individual_sales as $row):
          $pic = mb_convert_kana($row['pic'],'KVas');
          $pic_id = $row['pic_id'];
          if($picn != $pic){
            $picn = $pic;
            $picn_id = $pic_id;

            if($counter > 0) {
              ?>
              </tbody>
              <tfoot>
              <tr>
                <td colspan="3">合計</td>
                <td><?=number_format($i_count) ?></td>
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
              <?php
            }
            $i_count = 0;
            $i_gross = 0;
            $i_net = 0;
            $i_service = 0;
            $i_tax = 0;
            $i_discount =0;
            $i_ex_ts =0;
            
            echo "<div><h4>";
            echo $picn==""?"担当なし":$picn."（".$picn_id."）";
            echo "</h4>";
            echo "<table id='data-table'>
          <thead>
            <tr>
              <th>年月<span class='sort-arrow'></span></th>
              <th>予約日<span class='sort-arrow'></span></th>
              <th>予約ID<span class='sort-arrow'></span></th>
              <th>代理店<span class='sort-arrow'></span></th>
              <th>予約名<span class='sort-arrow'></span></th>
              <th>予約状況<span class='sort-arrow'></span></th>
              <th>カテゴリ<span class='sort-arrow'></span></th>
              <th>売上<span class='sort-arrow'></span></th>
              <th>純売上<span class='sort-arrow'></span></th>
              <th>サービス料<span class='sort-arrow'></span></th>
              <th>消費税<span class='sort-arrow'></span></th>
              <th>割引<span class='sort-arrow'></span></th>
              <th>税・サ抜<span class='sort-arrow'></span></th>
            </tr>
          </thead>
          <tbody>";
          }
        ?>
              <tr>
                <td><?=$row['ym'] ?></td>
                <td><?=$row['reservation_date'] ?></td>
                <td><a href="connection_list.php?resid=<?=$row['reservation_id'] ?>"><?=$row['reservation_id'] ?></a></td>
                <td><?=$row['agent_short'] ?></td>
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
                <td colspan="4">合計</td>
                <td><?=sizeof($individual_sales) ?></td>
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