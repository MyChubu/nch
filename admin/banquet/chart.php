<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
require_once('functions/admin_banquet_chart.php');



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
$before_nendo = $nendo - 1;
$after_nendo = $nendo + 1;

$chartdata = getChartData($nendo);
$last_nendo = $chartdata['last_nendo'];
$months = $chartdata['months'];
$sales_subtotal_array = $chartdata['sales_subtotal'];
$last_year_sales_subtotal_array = $chartdata['last_year_sales_subtotal'];
$sales_array = $chartdata['sales'];
$last_year_sales_array = $chartdata['last_year_sales'];
$this_nendo_subtotal = $chartdata['this_nendo_subtotal'];
$this_nendo_determined_subtotal = $chartdata['this_nendo_determined_subtotal'];
$last_nendo_subtotal = $chartdata['last_nendo_subtotal'];
$this_determined_sales = $chartdata['this_determined_sales'];
$this_tentative_sales = $chartdata['this_tentative_sales'];
$this_other_sales = $chartdata['this_other_sales'];
$last_determined_sales = $chartdata['last_determined_sales'];
$d_a = $chartdata['d_a'];
$d_a_count = $chartdata['d_a_count'];
$agent_sales = $chartdata['agent_sales'];
$agent_count = $chartdata['agent_count'];
$agents = $chartdata['agents'];
$category_sales = $chartdata['category_sales'];
$category_subtotals = $chartdata['category_subtotals'];
$category_counts = $chartdata['category_counts'];
$category_s = $chartdata['category_s'];


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
      gap: 10px;
    }
    .chartbox {
      width: calc(48% - 10px)  ;
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
      <h2>販売経路（金額）</h2>
      <canvas id="daChart"></canvas>
    </div>
    <div class="chartbox">
      <h2>販売経路（件数）</h2>
      <canvas id="dacChart"></canvas>
    </div>
    <div class="chartbox">
      <h2>代理店シェア（金額）</h2>
      <canvas id="agentChart"></canvas>
    </div>
    <div class="chartbox">
      <h2>代理店シェア（件数）</h2>
      <canvas id="agentcChart"></canvas>
    </div>
  </div>
  

  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>
<script type="text/javascript">
  const ctx = document.getElementById('myChart').getContext('2d');
  const labels = [<?= implode(',', array_map(function($m) { return '"' . $m . '"'; }, $months)) ?>];
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
    const labels2 = [<?= implode(',', array_map(function($m) { return '"' . $m . '"'; }, $months)) ?>];
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
  // 代理店・直販比率の円グラフ
  const ctx5 = document.getElementById('dacChart').getContext('2d');
  const dacData = {
    labels: ['直販', '代理店'],
    datasets: [{
      label: '件数比率',
      data: [<?= $d_a_count[0] ?>, <?= $d_a_count[1] ?>],
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
  const dacConfig = {
    type: 'doughnut',
    data: dacData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: '代理店・直販比率(件数)'
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
  new Chart(ctx5, dacConfig);
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
<script>
  // 代理店ごとの売上シェアの円グラフ
  const ctx6 = document.getElementById('agentcChart').getContext('2d');
  const agentcData = {
    labels: [<?= implode(',', array_map(function($agent) { return '"' . $agent . '"'; }, $agents)) ?>],
    datasets: [{
      label: '代理店売上シェア(件数)',
      data: [<?= implode(',', $agent_count) ?>],
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
  const agentcConfig = {
    type: 'doughnut',
    data: agentcData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: '代理店ごとの件数シェア'
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
  new Chart(ctx6, agentcConfig);
</script>

</body>
</html>