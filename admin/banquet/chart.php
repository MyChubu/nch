<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');

$month_array = array(
  0 => '4月',
  1 => '5月',
  2 => '6月', 
  3 => '7月',
  4 => '8月',
  5 => '9月',
  6 => '10月',
  7 => '11月',
  8 => '12月',  
  9 => '1月',
  10 => '2月',
  11 => '3月'
);

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
SUM(`sales`.`ex-ts`) AS `ex-ts`
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
  SUM(`ex-ts`) AS `ex-ts`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
  GROUP BY `ym`,`reservation_id`, `additional_sales`
  ORDER BY `ym`
 ) AS `sales`
 GROUP BY `ym`
 ORDER BY `ym`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
if($count > 0) {
  foreach($results as $result) {
    $tuki = (new DateTime($result['ym'] . '-01'))->format('n月');
    $sales[] = array(
      'ym' => $result['ym'],
      'tuki' => $tuki,
      'count' => $result['count'],
      'additional_sales' => $result['additional_sales'],
      'subtotal' => $result['subtotal'],
      'gross' => $result['gross'],
      'net' => $result['net'],
      'service_fee' => $result['service_fee'],
      'tax' => $result['tax'],
      'discount' => $result['discount'],
      'ex-ts' => $result['ex-ts']
    );
  }
}
$sales_array = array();
$sales_subtotal_array = array();
$sales_subtotal=0;
for($i = 0; $i < 12; $i++) {
  $tuki = $month_array[$i];
  foreach($sales as $sale) {
    if($sale['tuki'] == $tuki) {
      if(is_null($sale['net'])) {
        $sales_array[$i] = 0;
        $sales_subtotal += $sales_array[$i];
        $sales_subtotal_array[$i] = $sales_subtotal;
      }else{
        $sales_array[$i] = round($sale['net']/1000,0);
        $sales_subtotal += $sales_array[$i];
        $sales_subtotal_array[$i] = $sales_subtotal;
      }
      break;
    } else {
        $sales_array[$i] = 0;
        $sales_subtotal += $sales_array[$i];
        $sales_subtotal_array[$i] = $sales_subtotal;
    }

  }
}


$last_year_sales = array();
$last_nendo = $nendo - 1;
$last_first_day = $last_nendo . '-04-01';
$last_last_day = $last_nendo + 1 . '-03-31';
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $last_first_day, PDO::PARAM_STR);
$stmt->bindValue(':last_day', $last_last_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
if($count > 0) {
  foreach($results as $result) {
    $tuki = (new DateTime($result['ym'] . '-01'))->format('n月');
    $last_year_sales[] = array(
      'ym' => $result['ym'],
      'tuki' => $tuki,
      'count' => $result['count'],
      'additional_sales' => $result['additional_sales'],
      'subtotal' => $result['subtotal'],
      'gross' => $result['gross'],
      'net' => $result['net'],
      'service_fee' => $result['service_fee'],
      'tax' => $result['tax'],
      'discount' => $result['discount'],
      'ex-ts' => $result['ex-ts']
    );
  }
}
$last_year_sales_array = array();
$last_year_sales_subtotal_array = array();
$sales_subtotal=0;
for($i = 0; $i < 12; $i++) {  
  $tuki = $month_array[$i];
  foreach($last_year_sales as $sale) {
    if($sale['tuki'] == $tuki) {
      if(is_null($sale['net'])) {
        $last_year_sales_array[$i] = 0;
        $sales_subtotal += $last_year_sales_array[$i];
        $last_year_sales_subtotal_array[$i] = $sales_subtotal;
      }else{
        $last_year_sales_array[$i] = round($sale['net']/1000,0);
        $sales_subtotal += $last_year_sales_array[$i];
        $last_year_sales_subtotal_array[$i] = $sales_subtotal;
      }
      break;
    } else {
        $last_year_sales_array[$i] = 0;
        $sales_subtotal += $last_year_sales_array[$i];
        $last_year_sales_subtotal_array[$i] = $sales_subtotal;
    }
    
  }
}

//エージェント・直販比率



?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>グラフテスト</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <!--<script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>-->
  <!--<script src="js/admin_banquet.js"></script>-->
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <div>
    <h2>売上推移</h2>
    <canvas id="myChart"></canvas>
    <div>税・サービス料抜(単位：千円)</div>
    <div>
      <ul>
        <li>今年度のグラフは、決定予約/仮予約の積み上げグラフにしたい</li>
      </ul>
    </div>
  </div>
  <div>
    <h2>販売経路</h2>
    <div>代理店と直販の比率</div>
  </div>
  <div>
    <h2>代理店シェア</h2>
    <div>代理店ごとの売上シェア</div>
  </div>
  

  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>
<script type="text/javascript">
var ctx = document.getElementById('myChart').getContext('2d');
var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?= implode(',', array_map(function($m) { return '"' . $m . '"'; }, $month_array)) ?>],
        datasets: [
          {
            label: '<?=$nendo ?>年度累計',
            type: 'line',
            data: [<?= implode(',', $sales_subtotal_array) ?>],
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            yAxisID: 'y-axis-2'
          },
          {
            label: '<?=$last_nendo ?>年度累計',
            type: 'line',
            data: [<?= implode(',', $last_year_sales_subtotal_array) ?>],
            borderColor: 'rgb(255, 159, 64)',
            backgroundColor: 'rgba(255, 159, 64, 0.2)',
            yAxisID: 'y-axis-2'
          },
          {
            label: '<?=$nendo ?>年度',
            type: 'bar',
            fill: false,
            data: [<?= implode(',', $sales_array) ?>],
            borderColor: 'rgb(0, 246, 143)',
            backgroundColor: 'rgba(0, 246, 143, 0.5)',
            yAxisID: 'y-axis-1'
          },
          {
            label: '<?=$last_nendo ?>年度',
            type: 'bar',
            fill: false,
            data: [<?= implode(',', $last_year_sales_array) ?>],
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            yAxisID: 'y-axis-1'
        }
      ]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'nearest',
            intersect: false
        },
        scales: {
            'y-axis-1': {
                type: 'linear',
                position: 'left',
                min: 0,
                max: 50000,
                ticks: {
                    stepSize: 10000
                }
            },
            'y-axis-2': {
                type: 'linear',
                position: 'right',
                min: 0,
                max: 500000,
                ticks: {
                    stepSize: 100000
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
</script>
</body>
</html>