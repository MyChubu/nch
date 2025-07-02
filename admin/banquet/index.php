<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
require_once('functions/admin_banquet_chart.php');

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$week = array('日', '月', '火', '水', '木', '金', '土');

$now = new DateTime();
$date = $now->format('Y-m-d');
$w = $now->format('w');
$wd = $week[$w];
$h = (int)$now->format('H');

if ($h < 18) {
    $sche_title = '本日のスケジュール';
    $offset = '+1 day';
    $next_sche_title = '明日のスケジュール';
} else {
    $sche_title = '明日のスケジュール';
    $offset = '+2 day';
    $next_sche_title = '明後日のスケジュール';
}

$next_day = (clone $now)->modify($offset);
$next_date = $next_day->format('Y-m-d');
$next_w = $next_day->format('w');
$next_wd = $week[$next_w];


$sql="SELECT MAX(`date`) as `max_date`, MIN(`date`) as `min_date` FROM `banquet_schedules`";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$min_date = $row['min_date'];
$max_date = $row['max_date'];

$sql2 = "SELECT MAX(`modified`) as `last_update` FROM `banquet_schedules` WHERE `modified_by`='csvdata'";
$stmt2 = $dbh->prepare($sql2);
$stmt2->execute();
$row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
$l_s_update = $row2['last_update'];
$l_s_u = new DateTime($l_s_update) ;
$last_sche_update = $l_s_u->format('Y年m月d日 H:i');


$sql3 ="SELECT MAX(`modified`) as `last_update` FROM `banquet_charges` WHERE `modified_by`='csvdata'";
$stmt3 = $dbh->prepare($sql3);
$stmt3->execute();
$row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
$last_charge_update = $row3['last_update'];

$l_c_update = $row3['last_update'];
$l_c_u = new DateTime($l_c_update) ;
$last_charge_update = $l_c_u->format('Y年m月d日 H:i');


$month = date('n');
if($month < 4) {
  $nendo = date('Y') - 1;
} else {
  $nendo = date('Y');
}

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
$category_counts = $chartdata['category_counts'];
$category_subtotals = $chartdata['category_subtotals'];
$category_total_sales = $chartdata['category_total_sales'];
$category_total_counts = $chartdata['category_total_counts'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <meta http-equiv="refresh" content="300">
  <meta name="robots" content="noindex, nofollow">
  <title>会議・宴会サマリー</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/index2.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <script src="js/admin_banquet.js"></script>
  <!--<script src="js/getKaEnData.js"></script>-->
  <!--<script src="js/getKaEnNextData.js"></script>-->
  <script src="js/getIndexKaEnData.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
  <div class="wrapper">
    <div id="controller">
    </div>
    <div>
      <h1>会議・宴会サマリー</h1>
      <p>本日は<?= $date ?>（<?= $wd ?>）です。</p>
      <p>会議・宴会の予約状況を確認できます。</p>

    </div>
    <div>
      <div class="update_info">
        <p>最終更新日時（スケジュール）: <?= $last_sche_update ? $last_sche_update : '未更新' ?></p>
        <p>最終更新日時（料金）: <?= $last_charge_update ? $last_charge_update : '未更新' ?></p>
      </div>
      <div class="date_info">
        <p>表示可能期間: <?= $min_date ?> 〜 <?= $max_date ?></p>
        <p>表示可能な日付は、スケジュールの更新により変動する場合があります。</p>
        <p>システムの性質上、表示されたデータが最新ではない場合あります。最新データはNEHOPSでご確認ください。</p>
      </div>
    </div>
    <div class="top_sche_area">
      <div class="top_shcedule">
        <h2><?=$sche_title ?></h2>
        <div id="banquet-schedule">
          <div id="schedate"></div>
          <h2><i class="fa-solid fa-champagne-glasses"></i> 宴会</h2>
          <div id="eventsEn"></div>
          <h2><i class="fa-solid fa-users"></i> 会議</h2>
          <div id="eventsKa"></div>
          <div id="eventsOther"></div>
        </div>
      </div>
      <div class="top_shcedule">
        <h2><?=$next_sche_title ?></h2>
        <div id="banquet-next-schedule">
          <div id="nextSchedate"></div>
          <h2><i class="fa-solid fa-champagne-glasses"></i> 宴会</h2>
          <div id="nextEventsEn"></div>
          <h2><i class="fa-solid fa-users"></i> 会議</h2>
          <div id="nextEventsKa"></div>
          <div id="nextEventsOther"></div>
        </div>
      </div>
 
    </div>

    <div>
      <div class="graph_charts">
        <div class="chartbox cb_half">
          <h2>売上推移</h2>
          <canvas id="myChart"></canvas>
          <div class="text_right">税・サービス料抜(単位：千円)</div>
          <div><i class="fa-solid fa-square-arrow-up-right"></i> <a href="salesyear.php"><?=$nendo ?>年度売上</a></div>
        </div>
        <div class="chartbox cb_half">
          <h2>売上推移2</h2>
          <canvas id="myChart2"></canvas>
          <div class="text_right">税・サービス料抜(単位：千円)</div>
          <div><i class="fa-solid fa-square-arrow-up-right"></i> <a href="reservations.php"><?=$nendo ?>年度受注リスト</a></div>
        </div>
      </div>
      <div class="graph_charts">
        <div class="chartbox cb_half">
          <h2>カテゴリー別（金額）</h2>
          <canvas id="catSalesChart"></canvas>
          <div class="text_right">税・サービス料抜(単位：千円)</div>
          <div><i class="fa-solid fa-square-arrow-up-right"></i> <a href="salesyear.php#sales_category"><?=$nendo ?>年度 カテゴリー別</a></div>
        </div>
        <div class="chartbox cb_half">
          <h2>カテゴリー別（件数）</h2>
          <canvas id="catCountChart"></canvas>
          <div><i class="fa-solid fa-square-arrow-up-right"></i> <a href="salesyear.php#sales_category"><?=$nendo ?>年度 カテゴリー別</a></div>
        </div>
      </div>
      <div class="graph_charts">
        <div class="chartbox cb_quarter">
          <h2>カテゴリー別 売上比率</h2>
          <canvas id="catSalesDoughnutChart"></canvas>
          <div class="text_right">税・サービス料抜(単位：千円)</div>
        </div>
        <div class="chartbox cb_quarter">
          <h2>カテゴリー別 件数比率</h2>
          <canvas id="catCountDoughnutChart"></canvas>
        </div>
      </div>
      <div class="graph_charts">
        <div class="chartbox cb_quarter">
          <h2>販売経路（金額）</h2>
          <canvas id="daChart"></canvas>
          <div class="text_right">税・サービス料抜(単位：千円)</div>
          <div><i class="fa-solid fa-square-arrow-up-right"></i> <a href="salesdirect.php"><?=$nendo ?>年度 直販</a></div>
        </div>
        <div class="chartbox cb_quarter">
          <h2>販売経路（件数）</h2>
          <canvas id="dacChart"></canvas>
        </div>
        <div class="chartbox cb_quarter">
          <h2>代理店シェア（金額）</h2>
          <canvas id="agentChart"></canvas>
          <div class="text_right">税・サービス料抜(単位：千円)</div>
          <div><i class="fa-solid fa-square-arrow-up-right"></i> <a href="salesagents.php"><?=$nendo ?>年度 エージェント</a></div>
        </div>
        <div class="chartbox cb_quarter">
          <h2>代理店シェア（件数）</h2>
          <canvas id="agentcChart"></canvas>
        </div>
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
      // 棒グラフ（2024年度）
      {
        label: '<?=$last_nendo ?>年度',
        data: [<?= implode(',', $last_year_sales_array) ?>],
        backgroundColor: 'rgba(54, 162, 235, 0.5)',
        borderColor: 'rgb(54, 162, 235)',
        type: 'bar',
        fill: false,
        yAxisID: 'y-axis-1'
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
          text: '<?=$nendo ?>・<?=$last_nendo ?>年度 売上'
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
        // 2024棒グラフ
        {
          label: '<?=$last_nendo ?> 実績',
          data: [<?= implode(',', $last_determined_sales) ?>],
          backgroundColor: 'rgba(255, 99, 132, 0.4)',
          stack: '<?=$last_nendo ?>',
          yAxisID: 'y-left'
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
      data: [<?= $d_a[0] ?>, <?= $d_a[1] ?>].map(v => Math.round(v / 1000)),
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
            size: 12
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
            size: 12
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
      data: [<?= implode(',', $agent_sales) ?>].map(v => Math.round(v / 1000)),
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
            size: 12
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
            size: 12
          }
        }
      }
    },
    plugins: [ChartDataLabels]
  };
  new Chart(ctx6, agentcConfig);
</script>
<script>
  const ctx7 = document.getElementById('catSalesChart').getContext('2d');

  const labels7 = [<?= implode(',', array_map(function($m) { return '"' . $m . '"'; }, $months)) ?>];
  const catSalesData = {
    labels: labels7,
    datasets: [
      {
        label: '会',
        data: [<?= implode(',', $category_sales['cat_1']) ?>].map(v => Math.round(v / 1000)),
        backgroundColor: 'rgba(255, 99, 132, 0.7)', // 赤
        stack: '売上'
      },
      {
        label: '宴',
        data: [<?= implode(',', $category_sales['cat_2']) ?>].map(v => Math.round(v / 1000)),
        backgroundColor: 'rgba(54, 162, 235, 0.7)', // 青
        stack: '売上'
      },
      {
        label: '食',
        data: [<?= implode(',', $category_sales['cat_3']) ?>].map(v => Math.round(v / 1000)),
        backgroundColor: 'rgba(75, 192, 192, 0.7)', // 緑
        stack: '売上'
      },
      {
        label: '会/宴',
        data: [<?= implode(',', $category_sales['cat_4']) ?>].map(v => Math.round(v / 1000)),
        backgroundColor: 'rgba(255, 206, 86, 0.7)', // 黄
        stack: '売上'
      },
      {
        label: '会/食',
        data: [<?= implode(',', $category_sales['cat_5']) ?>].map(v => Math.round(v / 1000)),
        backgroundColor: 'rgba(153, 102, 255, 0.7)', // 紫
        stack: '売上'
      },
      {
        label: '会/宴/食',
        data: [<?= implode(',', $category_sales['cat_6']) ?>].map(v => Math.round(v / 1000)),
        backgroundColor: 'rgba(255, 159, 64, 0.7)', // オレンジ
        stack: '売上'
      }
    ]
  };

  const catSalesConfig = {
    type: 'bar',
    data: catSalesData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'カテゴリ別 売上（積み上げグラフ）'
        }
      },
      scales: {
        x: {
          stacked: true
        },
        y: {
          stacked: true,
          beginAtZero: true,
          title: {
            display: true,
            text: '売上金額'
          }
        }
      }
    },
    plugins: [{
      id: 'totalLabelPlugin',
      afterDatasetsDraw(chart) {
        const { ctx, chartArea: { top }, data, scales: { x, y } } = chart;
        const totals = [];

        // 合計を計算
        data.datasets.forEach((ds, dsIndex) => {
          ds.data.forEach((v, i) => {
            totals[i] = (totals[i] || 0) + v;
          });
        });

        // 合計ラベルを描画
        totals.forEach((total, i) => {
          const xPos = x.getPixelForValue(i);
          const yPos = y.getPixelForValue(total);

          ctx.save();
          ctx.font = 'bold 12px sans-serif';
          ctx.fillStyle = '#000';
          ctx.textAlign = 'center';
          ctx.fillText(total.toLocaleString() , xPos, yPos - 5);
          ctx.restore();
        });
      }
    }]
  };

  new Chart(ctx7, catSalesConfig);
</script>
<script>
  const ctx8 = document.getElementById('catCountChart').getContext('2d');

  const labels8 = [<?= implode(',', array_map(function($m) { return '"' . $m . '"'; }, $months)) ?>];
  const catCountData = {
    labels: labels8,
    datasets: [
      {
        label: '会',
        data: [<?= implode(',', $category_counts['cat_1']) ?>],
        backgroundColor: 'rgba(255, 99, 132, 0.7)', // 赤
        stack: '売上'
      },
      {
        label: '宴',
        data: [<?= implode(',', $category_counts['cat_2']) ?>],
        backgroundColor: 'rgba(54, 162, 235, 0.7)', // 青
        stack: '売上'
      },
      {
        label: '食',
        data: [<?= implode(',', $category_counts['cat_3']) ?>],
        backgroundColor: 'rgba(75, 192, 192, 0.7)', // 緑
        stack: '売上'
      },
      {
        label: '会/宴',
        data: [<?= implode(',', $category_counts['cat_4']) ?>],
        backgroundColor: 'rgba(255, 206, 86, 0.7)', // 黄
        stack: '売上'
      },
      {
        label: '会/食',
        data: [<?= implode(',', $category_counts['cat_5']) ?>],
        backgroundColor: 'rgba(153, 102, 255, 0.7)', // 紫
        stack: '売上'
      },
      {
        label: '会/宴/食',
        data: [<?= implode(',', $category_counts['cat_6']) ?>],
        backgroundColor: 'rgba(255, 159, 64, 0.7)', // オレンジ
        stack: '売上'
      }
    ]
  };

  const catCountConfig = {
    type: 'bar',
    data: catCountData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'カテゴリ別 件数（積み上げグラフ）'
        }
      },
      scales: {
        x: {
          stacked: true
        },
        y: {
          stacked: true,
          beginAtZero: true,
          title: {
            display: true,
            text: '受注件数'
          }
        }
      }
    },
    plugins: [{
      id: 'totalLabelPlugin',
      afterDatasetsDraw(chart) {
        const { ctx, chartArea: { top }, data, scales: { x, y } } = chart;
        const totals = [];

        // 合計を計算
        data.datasets.forEach((ds, dsIndex) => {
          ds.data.forEach((v, i) => {
            totals[i] = (totals[i] || 0) + v;
          });
        });

        // 合計ラベルを描画
        totals.forEach((total, i) => {
          const xPos = x.getPixelForValue(i);
          const yPos = y.getPixelForValue(total);

          ctx.save();
          ctx.font = 'bold 12px sans-serif';
          ctx.fillStyle = '#000';
          ctx.textAlign = 'center';
          ctx.fillText(total.toLocaleString() , xPos, yPos - 5);
          ctx.restore();
        });
      }
    }]
  };

  new Chart(ctx8, catCountConfig);
</script>
<script>
  // カテゴリごとの売上シェアの円グラフ
  const ctx9 = document.getElementById('catSalesDoughnutChart').getContext('2d');
  
  const catSlalesDoughData = {
    labels: ['会', '宴', '食', '会/宴', '会/食', '会/宴/食'],
    datasets: [{
      label: 'カテゴリー売上シェア',
      data: [<?= implode(',', $category_total_sales) ?>].map(v => Math.round(v / 1000)),
      backgroundColor: [
        'rgba(255, 99, 132, 0.8)',
        'rgba(54, 162, 235, 0.8)',
        'rgba(75, 192, 192, 0.8)',
        'rgba(255, 206, 86, 0.8)',
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
        'rgba(75, 192, 192, 1)',
        'rgba(255, 206, 86, 1)',
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
  const catSlalesDoughConfig = {
    type: 'doughnut',
    data: catSlalesDoughData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'カテゴリー売上シェア'
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
  new Chart(ctx9, catSlalesDoughConfig);
</script>
<script>
  // カテゴリごとの売上シェアの円グラフ
  const ctx10 = document.getElementById('catCountDoughnutChart').getContext('2d');
  
  const catCountsDoughData = {
    labels: ['会', '宴', '食', '会/宴', '会/食', '会/宴/食'],
    datasets: [{
      label: 'カテゴリー売上シェア',
      data: [<?= implode(',', $category_total_counts) ?>],
      backgroundColor: [
        'rgba(255, 99, 132, 0.8)',
        'rgba(54, 162, 235, 0.8)',
        'rgba(75, 192, 192, 0.8)',
        'rgba(255, 206, 86, 0.8)',
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
        'rgba(75, 192, 192, 1)',
        'rgba(255, 206, 86, 1)',
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
  const catCountsDoughConfig = {
    type: 'doughnut',
    data: catCountsDoughData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'カテゴリー件数シェア'
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
  new Chart(ctx10, catCountsDoughConfig);
</script>
</body>
</html>