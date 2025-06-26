<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');

$month_array = array('4月','5月','6月', '7月','8月','9月','10月','11月','12月','1月','2月','3月');

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

$last_nendo = $nendo - 1;
$last_first_day = $last_nendo . '-04-01';
$last_last_day = $last_nendo + 1 . '-03-31';

$before_nendo = $nendo - 1;
$after_nendo = $nendo + 1;

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sales = array();
$sql = "SELECT 
`sales`.`ym`,
COUNT(`sales`.`reservation_id`) AS `count`,
SUM(`sales`.`subtotal`) AS `subtotal`,
SUM(`sales`.`gross`) AS `gross`,
SUM(`sales`.`net`) AS `net`
FROM (
  SELECT 
  `ym`,
  `reservation_id`,
  SUM(`subtotal`) AS `subtotal`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
  GROUP BY `ym`,`reservation_id`
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
      'subtotal' => $result['subtotal'],
      'gross' => $result['gross'],
      'net' => $result['net']
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
      'subtotal' => $result['subtotal'],
      'gross' => $result['gross'],
      'net' => $result['net']
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
$this_nendo_sales =array();
$this_determined_sales = array();
$this_tentative_sales = array();
$this_other_sales = array();
$subtotal= 0;
$determ=0;
$this_nendo_subtotal = array();
$this_nendo_determined_subtotal = array();
$last_nendo_sales = array();
$last_determined_sales = array();
$last_nendo_subtotal = array();
$lastsub=0;

$sql = "SELECT 
`sales`.`ym`,
`sales`.`status`,
COUNT(`sales`.`reservation_id`) AS `count`,
SUM(`sales`.`gross`) AS `gross`,
SUM(`sales`.`net`) AS `net`
FROM (
  SELECT 
  `ym`,
  `reservation_id`,
  `status`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
  GROUP BY `ym`,`reservation_id`,  `status`
  ORDER BY `ym`
 ) AS `sales`
 GROUP BY `ym`, `status`
 ORDER BY `ym`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($results as $result) {
  $tuki = (new DateTime($result['ym'] . '-01'))->format('n月');
  $this_nendo_sales[] = array(
    'ym' => $result['ym'],
    'tuki' => $tuki,
    'status' => $result['status'],
    'count' => $result['count'],
    'gross' => $result['gross'],
    'net' => $result['net']
  );
}

$sql = "SELECT 
`sales`.`ym`,
COUNT(`sales`.`reservation_id`) AS `count`,
SUM(`sales`.`gross`) AS `gross`,
SUM(`sales`.`net`) AS `net`
FROM (
  SELECT 
  `ym`,
  `reservation_id`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
  AND `status` IN (1,2,3) 
  GROUP BY `ym`,`reservation_id`
  ORDER BY `ym`
 ) AS `sales`
 GROUP BY `ym`
 ORDER BY `ym`";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $last_first_day, PDO::PARAM_STR);
$stmt->bindValue(':last_day', $last_last_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($results as $result) {
  $tuki = (new DateTime($result['ym'] . '-01'))->format('n月');
  $last_nendo_sales[] = array(
    'ym' => $result['ym'],
    'tuki' => $tuki,
    'count' => $result['count'],
    'gross' => $result['gross'],
    'net' => $result['net']
  );
}

for($i = 0; $i < 12; $i++) {
  $tuki = $month_array[$i];
  $this_determined_sales[$i] = 0;
  $this_tentative_sales[$i] = 0;
  $this_other_sales[$i] = 0;
  foreach($this_nendo_sales as $sale) {
    if($sale['tuki'] == $tuki) {
      $ym = explode('-', $sale['ym']);
      $cyear = $ym[0];
      $cmonth = (int)$ym[1];
      if($cmonth < 4) {
        $c_nendo = $cyear - 1;
      } else {
        $c_nendo = $cyear;
      }
      if($c_nendo != $nendo) {
        continue; // Skip if not the current fiscal year
      }
      if($sale['status'] == 1) {
        if(isset($sale['net'])) {
          $this_determined_sales[$i] = round($sale['net']/1000,0);
        }
        $subtotal += $this_determined_sales[$i];
        $determ += $this_determined_sales[$i];
        $this_nendo_subtotal[$i] = $subtotal;
        $this_nendo_determined_subtotal[$i] = $determ;
      }elseif($sale['status'] == 2) {
        if(isset($sale['net'])) {
          $this_tentative_sales[$i] = round($sale['net']/1000,0);
        }
        $subtotal += $this_tentative_sales[$i];
        $this_nendo_subtotal[$i] = $subtotal;
      }elseif($sale['status'] == 3) {
        if(isset($sale['net'])) {
          $this_other_sales[$i] = round($sale['net']/1000,0);
        }
        $subtotal += $this_other_sales[$i];
        $this_nendo_subtotal[$i] = $subtotal;
      }
    }
  }
  if(!isset($this_nendo_subtotal[$i])) {
    $this_nendo_subtotal[$i] = $subtotal;
  }
  if(!isset($this_nendo_determined_subtotal[$i])) {
    $this_nendo_determined_subtotal[$i] = $determ;
  }
}

for($i = 0; $i < 12; $i++) {
  $tuki = $month_array[$i];
  $last_determined_sales[$i] = 0;
  foreach($last_nendo_sales as $sale) {
    if($sale['tuki'] == $tuki) {
      $ym = explode('-', $sale['ym']);
      $cyear = $ym[0];
      $cmonth = (int)$ym[1];
      if($cmonth < 4) {
        $c_nendo = $cyear - 1;
      } else {
        $c_nendo = $cyear;
      }
      if($c_nendo != $last_nendo) {
        continue; // Skip if not the last fiscal year
      }
      if(isset($sale['net'])) {
        $last_determined_sales[$i] = round($sale['net']/1000,0);
      }
      $lastsub += $last_determined_sales[$i];
      $last_nendo_subtotal[$i] = $lastsub;
    }
  }
  if(!isset($last_nendo_subtotal[$i])) {
    $last_nendo_subtotal[$i] = $lastsub;
  }
}


//エージェント・直販比率
$direct = 0;
$agent = 0;
$d_count = 0;
$a_count = 0;
$da_total=0;
$agents= array();
$agent_sales = array();
$agent_count = array();
$sql="SELECT
`S`.`agent_id`,
`S`.`agent_name`,
COUNT(`S`.`reservation_id`) AS `count`,
SUM(`S`.`gross`) AS `gross`,
SUM(`S`.`net`) AS `net`
FROM(
  SELECT 
  `agent_id`,
  `agent_name`,
  `reservation_id`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :firsr_day AND :last_day
  GROUP BY `reservation_id`
  ORDER BY `gross` DESC) AS `S`
GROUP BY `agent_id`
ORDER BY `gross` DESC";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':firsr_day', $first_day, PDO::PARAM_STR);
$stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($results as $result) {
  if($result['agent_id'] == 0 ) {
    $direct += $result['net'];
    $d_count += $result['count'];
  } else {
    $agent += $result['net'];
    $a_count += $result['count'];
    array_push($agents, $result['agent_name']);
    array_push($agent_sales, $result['net']);
    array_push($agent_count, $result['count']);
  }
  $da_total += $result['net'];
}
$d_a=array($direct, $agent);
$d_a_count = array($d_count, $a_count);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>グラフテスト</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

  <!--<script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>-->
  <!--<script src="js/admin_banquet.js"></script>-->
  <style>
    .pie_charts {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 20px;
    }
    .chartbox {
      width: calc(48% - 20px)  ;
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <div>
    <h2>売上推移</h2>
    <canvas id="myChart"></canvas>
    <div>税・サービス料抜(単位：千円)</div>
  </div>

  <div>
    <h2>売上推移2</h2>
    <canvas id="myChart2"></canvas>
    <div>税・サービス料抜(単位：千円)</div>

  </div>

  <div class="pie_charts">
    <div class="chartbox">
      <h2>販売経路</h2>
      <div>代理店と直販の比率</div>
      <canvas id="daChart"></canvas>
    </div>
    <div class="chartbox">
      <h2>代理店シェア</h2>
      <div>代理店ごとの売上シェア</div>
      <canvas id="agentChart"></canvas>
    </div>
  </div>
  

  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>
<script type="text/javascript">
const ctx = document.getElementById('myChart').getContext('2d');
const labels = [<?= implode(',', array_map(function($m) { return '"' . $m . '"'; }, $month_array)) ?>];
const data = {
  labels: labels,
  datasets: [
    // 折れ線グラフ（2025年度累計）
    {
      label: '<?=$nendo ?>年度累計',
      data: [<?= implode(',', $sales_subtotal_array) ?>],
      borderColor: 'rgba(255, 99, 132, 1)',
      backgroundColor: 'rgba(255, 99, 132, 0.2)',
      type: 'line',
      fill: false,
      yAxisID: 'y-axis-2'
    },
    // 折れ線グラフ（2024年度累計）
    {
      label: '<?=$last_nendo ?>年度累計',
      data: [<?= implode(',', $last_year_sales_subtotal_array) ?>],
      borderColor: 'rgba(255, 159, 64, 1)',
      backgroundColor: 'rgba(255, 159, 64, 0.2)',
      type: 'line',
      fill: false,
      yAxisID: 'y-axis-2'
    },
    // 棒グラフ（2025年度）
    {
      label: '<?=$nendo ?>年度',
      data: [<?= implode(',', $sales_array) ?>],
      backgroundColor: 'rgba(0, 246, 143, 0.5)',
      borderColor: 'rgb(0, 246, 143)',
      type: 'bar',
      fill: false,
      yAxisID: 'y-axis-1'
    },
    // 棒グラフ（2024年度）
    {
      label: '<?=$last_nendo ?>年度',
      data: [<?= implode(',', $last_year_sales_array) ?>],
      backgroundColor: 'rgba(54, 162, 235, 0.5)',
      borderColor: 'rgb(54, 162, 235)',
      type: 'bar',
      fill: false,
      yAxisID: 'y-axis-1'
    }
    
  ]
};
const config = {
  type: 'bar',
  data: data,
  options: {
    responsive: true,
    pulugins: {
      legend: {
        position: 'top',
      },
      title: {
        display: true,
        text: '<?=$nendo ?>・<?=$last_nendo ?>年度 売上推移'
      }
    },
    scales: {
      'y-axis-1': {
        bginAtZero: true,
        type: 'linear',
        position: 'left',
        title: {
          display: true,
          text: '月次売上'
        }
      },
      'y-axis-2': {
        bginAtZero: true,
        type: 'linear',
        position: 'right',
        title: {
          display: true,
          text: '累計売上'
        },
        grid: {
          drawOnChartArea: false
        },
        title: {
          display: true,
          text: '累計売上'
        }
      } 
    }
  }
};
new Chart(ctx, config);

</script>
<script>
    const ctx2 = document.getElementById('myChart2').getContext('2d');
    const labels2 = [<?= implode(',', array_map(function($m) { return '"' . $m . '"'; }, $month_array)) ?>];
    const data2 = {
      labels: labels2,
      datasets: [
        // 折れ線グラフ（2025 subtotal）
        {
          label: '<?=$nendo ?> 累計',
          data: [<?= implode(',', $this_nendo_subtotal) ?>],
          borderColor: 'rgba(54, 162, 235, 1)',
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          type: 'line',
          fill: false,
          yAxisID: 'y-right'
        },
        // 折れ線グラフ（2025 決定累計）
        {
          label: '<?=$nendo ?> 決定',
          data: [<?= implode(',', $this_nendo_determined_subtotal) ?>],
          borderColor: 'rgb(1, 130, 55)',
          backgroundColor: 'rgba(54, 162, 235, 0.1)',
          type: 'line',
          fill: false,
          yAxisID: 'y-right'
        },
        // 折れ線グラフ（2024 累計小計）
        {
          label: '<?=$last_nendo ?> 累計',
          data: [<?= implode(',', $last_nendo_subtotal) ?>],
          borderColor: 'rgba(255, 99, 132, 0.8)',
          backgroundColor: 'rgba(255, 99, 132, 0.2)',
          type: 'line',
          fill: false,
          yAxisID: 'y-right'
        },


        // 2025棒グラフ
        {
          label: '<?=$nendo ?> 決定',
          data: [<?= implode(',', $this_determined_sales) ?>],
          backgroundColor: 'rgba(0, 246, 143, 0.8)',
          stack: '<?=$nendo ?>',
          yAxisID: 'y-left'
        },
        {
          label: '<?=$nendo ?> 仮',
          data: [<?= implode(',', $this_tentative_sales) ?>],
          backgroundColor: 'rgba(54, 162, 235, 0.5)',
          stack: '<?=$nendo ?>',
          yAxisID: 'y-left'
        },
        {
          label: '<?=$nendo ?> 他',
          data: [<?= implode(',', $this_other_sales) ?>],
          backgroundColor: 'rgba(54, 235, 151, 0.5)',
          stack: '<?=$nendo ?>',
          yAxisID: 'y-left'
        },
        // 2024棒グラフ
        {
          label: '<?=$last_nendo ?> 実績',
          data: [<?= implode(',', $last_determined_sales) ?>],
          backgroundColor: 'rgba(255, 99, 132, 0.4)',
          stack: '<?=$last_nendo ?>',
          yAxisID: 'y-left'
        }
        
        
      ]
    };

    const config2 = {
      type: 'bar',
      data: data2,
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top'
          },
          title: {
            display: true,
            text: '<?=$nendo ?>・<?=$last_nendo ?>年度 売上（積み上げ＋累計折れ線）'
          }
        },
        scales: {
          x: {
            stacked: true
          },
          'y-left': {
            stacked: true,
            beginAtZero: true,
            position: 'left',
            title: {
              display: true,
              text: '月次売上'
            }
          },
          'y-right': {
            beginAtZero: true,
            position: 'right',
            grid: {
              drawOnChartArea: false
            },
            title: {
              display: true,
              text: '累計売上'
            }
          }
        }
      }
    };

    new Chart(ctx2, config2);
</script>
<script>
  // 代理店・直販比率の円グラフ
  const ctx3 = document.getElementById('daChart').getContext('2d');
  const daData = {
    labels: ['直販', '代理店'],
    datasets: [{
      label: '売上比率',
      data: [<?= $d_a[0] ?>, <?= $d_a[1] ?>],
      backgroundColor: [
        'rgba(0, 246, 143, 0.8)',
        'rgba(54, 162, 235, 0.8)'
      ],
      borderColor: [
        'rgba(0, 246, 143, 1)',
        'rgba(54, 162, 235, 1)'
      ],
      borderWidth: 1
    }]
  };
  const daConfig = {
    type: 'doughnut',
    data: daData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: '代理店・直販比率'
        },
        datalabels: {
          formatter: (value, context) => {
            const data = context.chart.data.datasets[0].data;
            const total = data.reduce((a, b) => a + b, 0);
            const percentage = (value / total * 100).toFixed(1);
            return percentage + '%';
          },
          color: '#fff',
          font: {
            weight: 'bold',
            size: 14
          }
        }
      }
    },
    plugins: [ChartDataLabels]
  };
  new Chart(ctx3, daConfig);
</script>
<script>
  // 代理店ごとの売上シェアの円グラフ
  const ctx4 = document.getElementById('agentChart').getContext('2d');
  const agentData = {
    labels: [<?= implode(',', array_map(function($agent) { return '"' . $agent . '"'; }, $agents)) ?>],
    datasets: [{
      label: '代理店売上シェア',
      data: [<?= implode(',', $agent_sales) ?>],
      backgroundColor: [
        'rgba(255, 99, 132, 0.8)',
        'rgba(54, 162, 235, 0.8)',
        'rgba(255, 206, 86, 0.8)',
        'rgba(75, 192, 192, 0.8)',
        'rgba(153, 102, 255, 0.8)',
        'rgba(255, 159, 64, 0.8)',
        'rgba(0, 246, 143, 0.8)',
        'rgba(54, 235, 151, 0.8)',
        'rgba(255, 99, 132, 0.5)',
        'rgba(54, 162, 235, 0.5)',
        'rgba(255, 206, 86, 0.5)',
        'rgba(75, 192, 192, 0.5)',
        'rgba(153, 102, 255, 0.5)',
        'rgba(255, 159, 64, 0.5)',
      ],
      borderColor: [
        'rgba(255, 99, 132, 1)',
        'rgba(54, 162, 235, 1)',
        'rgba(255, 206, 86, 1)',
        'rgba(75, 192, 192, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(255, 159, 64, 1)',
        'rgba(0, 246, 143, 1)', 
        'rgba(54, 235, 151, 1)',
        'rgba(255, 99, 132, 0.8)',
        'rgba(54, 162, 235, 0.8)',
        'rgba(255, 206, 86, 0.8)',
        'rgba(75, 192, 192, 0.8)',
        'rgba(153, 102, 255, 0.8)',
        'rgba(255, 159, 64, 0.8)',
      ],
      borderWidth: 1
    }]
  };
  const agentConfig = {
    type: 'doughnut',
    data: agentData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: '代理店ごとの売上シェア'
        },
        datalabels: {
          formatter: (value, context) => {
            const data = context.chart.data.datasets[0].data;
            const total = data.reduce((a, b) => a + b, 0);
            const percentage = (value / total * 100).toFixed(1);
            if (isNaN(percentage)) {
              return ''; // NaNの場合は表示しない
            }
            else if (percentage < 3) {
              return ''; // 3%未満は表示しない
            }else{
              return percentage + '%';
            }
            
          },
          color: '#fff',
          font: {
            weight: 'bold',
            size: 14
          }
        }
      }
    },
    plugins: [ChartDataLabels]
  };
  new Chart(ctx4, agentConfig);
</script>

</body>
</html>