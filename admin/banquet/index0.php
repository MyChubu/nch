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
require_once('functions/admin_banquet_chart.php');


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
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/index2.css?<?= date('YmdHis') ?>">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  
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
        <div class="chartbox cb_third">
          <h2>カテゴリー別 売上比率</h2>
          <canvas id="catSalesDoughnutChart"></canvas>
          <div class="text_right">税・サービス料抜(単位：千円)</div>
        </div>
        <div class="chartbox cb_third">
          <h2>販売経路</h2>
          <canvas id="daChart"></canvas>
          <div class="text_right">税・サービス料抜(単位：千円)</div>
          <div><i class="fa-solid fa-square-arrow-up-right"></i> <a href="salesdirect.php"><?=$nendo ?>年度 直販</a></div>
        </div>
        <div class="chartbox cb_third">
          <h2>代理店シェア</h2>
          <canvas id="agentChart"></canvas>
          <div class="text_right">税・サービス料抜(単位：千円)</div>
          <div><i class="fa-solid fa-square-arrow-up-right"></i> <a href="salesagents.php"><?=$nendo ?>年度 エージェント</a></div>
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
  const ctx3 = document.getElementById('daChart').getContext('2d');

  // データ（外：売上、内：件数）
  const salesData = [<?= $d_a[0] ?>, <?= $d_a[1] ?>].map(v => Math.round(v / 1000)); // 千円
  const countData = [<?= $d_a_count[0] ?>, <?= $d_a_count[1] ?>];

  const salesTotal = salesData.reduce((a, b) => a + b, 0); // 千円
  const countTotal = countData.reduce((a, b) => a + b, 0);

  // 中央テキスト描画プラグイン
  const centerTextPlugin = {
    id: 'centerTextPlugin',
    afterDraw(chart, args, pluginOptions) {
      const { ctx, chartArea } = chart;
      if (!chartArea) return;

      const cx = (chartArea.left + chartArea.right) / 2;
      const cy = (chartArea.top + chartArea.bottom) / 2;

      const lines = pluginOptions?.lines ?? [];
      const lineGap = pluginOptions?.lineGap ?? 18;

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle = pluginOptions?.color ?? '#333';

      if (lines[0]) {
        ctx.font = pluginOptions?.fontTop ?? 'bold 16px sans-serif';
        ctx.fillText(lines[0], cx, cy - lineGap / 2);
      }
      if (lines[1]) {
        ctx.font = pluginOptions?.fontBottom ?? 'bold 14px sans-serif';
        ctx.fillText(lines[1], cx, cy + lineGap / 2);
      }

      ctx.restore();
    }
  };

  const data3 = {
    labels: ['直販', '代理店'],
    datasets: [
      // 外側：売上
      {
        label: '売上（千円）',
        data: salesData,
        backgroundColor: [
          'rgba(0, 246, 143, 0.85)',
          'rgba(54, 162, 235, 0.85)'
        ],
        borderColor: [
          'rgba(0, 246, 143, 1)',
          'rgba(54, 162, 235, 1)'
        ],
        borderWidth: 1,
        radius: '100%',
        cutout: '40%'
      },
      // 内側：件数
      {
        label: '件数',
        data: countData,
        backgroundColor: [
          'rgba(0, 246, 143, 0.35)',
          'rgba(54, 162, 235, 0.35)'
        ],
        borderColor: [
          'rgba(0, 246, 143, 1)',
          'rgba(54, 162, 235, 1)'
        ],
        borderWidth: 1,
        radius: '100%',
        cutout: '40%'
      }
    ]
  };

  const config3 = {
    type: 'doughnut',
    data:data3,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            generateLabels(chart) {
              const labels = chart.data.labels || [];
              const colors = chart.data.datasets[0].backgroundColor;
              return labels.map((text, i) => ({
                text,
                fillStyle: colors[i],
                strokeStyle: colors[i],
                hidden: false,
                index: i
              }));
            }
          },
          onClick(e, legendItem, legend) {
            const index = legendItem.index;
            const chart = legend.chart;

            chart.data.datasets.forEach((ds, di) => {
              const meta = chart.getDatasetMeta(di);
              if (meta.data[index]) {
                meta.data[index].hidden =
                  meta.data[index].hidden === true ? false : true;
              }
            });

            chart.update();
          }
        },
        title: {
          display: true,
          text: '代理店・直販比率'
        },
        subtitle: {
          display: true,
          text: '外側：売上（千円） / 内側：件数',
          padding: { bottom: 6 }
        },
        datalabels: {
          formatter: (value, context) => {
            const ds = context.dataset.data;
            const total = ds.reduce((a, b) => a + b, 0);
            return total
              ? (value / total * 100).toFixed(1) + '%'
              : '0.0%';
          },
          color: '#fff',
          font: { weight: 'bold', size: 12 }
        },
        centerTextPlugin: {
          lines: [
            `売上合計 ${salesTotal.toLocaleString()} 千円`,
            `件数合計 ${countTotal.toLocaleString()} 件`
          ],
          color: '#333',
          fontTop: 'bold 16px sans-serif',
          fontBottom: 'bold 14px sans-serif',
          lineGap: 20
        }
      }
    },
    plugins: [ChartDataLabels, centerTextPlugin]
  };

  new Chart(ctx3, config3);
</script>
<script>
  // ===== canvas =====
  const ctx4 = document.getElementById('agentChart').getContext('2d');

  // ===== 元データ =====
  const agentLabels = [<?= implode(',', array_map(fn($a) => '"' . $a . '"', $agents)) ?>];

  const agtSalesData = [<?= implode(',', $agent_sales) ?>].map(v => Math.round(v / 1000)); // 千円
  const agtCountData = [<?= implode(',', $agent_count) ?>];

  const agtSalesTotal = agtSalesData.reduce((a, b) => a + b, 0);
  const agtCountTotal = agtCountData.reduce((a, b) => a + b, 0);

  // ===== カラーパレット（共通）=====
  const agtBgColors = [
    'rgba(255, 99, 132, 0.85)',
    'rgba(54, 162, 235, 0.85)',
    'rgba(255, 206, 86, 0.85)',
    'rgba(75, 192, 192, 0.85)',
    'rgba(153, 102, 255, 0.85)',
    'rgba(255, 159, 64, 0.85)',
    'rgba(0, 246, 143, 0.85)',
    'rgba(54, 235, 151, 0.85)',
    'rgba(255, 99, 132, 0.5)',
    'rgba(54, 162, 235, 0.5)',
    'rgba(255, 206, 86, 0.5)',
    'rgba(75, 192, 192, 0.5)',
    'rgba(153, 102, 255, 0.5)',
    'rgba(255, 159, 64, 0.5)',
  ];

  // ===== 中央テキストプラグイン =====
  const agtCenterTextPlugin = {
    id: 'agtCenterTextPlugin',
    afterDraw(chart, args, opts) {
      const { ctx, chartArea } = chart;
      if (!chartArea) return;

      const x = (chartArea.left + chartArea.right) / 2;
      const y = (chartArea.top + chartArea.bottom) / 2;

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle = '#333';

      ctx.font = 'bold 16px sans-serif';
      ctx.fillText(`売上合計 ${agtSalesTotal.toLocaleString()} 千円`, x, y - 10);

      ctx.font = 'bold 14px sans-serif';
      ctx.fillText(`件数合計 ${agtCountTotal.toLocaleString()} 件`, x, y + 12);

      ctx.restore();
    }
  };

  // ===== data4 =====
  const data4 = {
    labels: agentLabels,
    datasets: [
      // 外側：売上
      {
        label: '売上（千円）',
        data: agtSalesData,
        backgroundColor: agtBgColors,
        borderColor: agtBgColors.map(c => c.replace(/0\.\d+\)/, '1)')),
        borderWidth: 1,
        radius: '100%',
        cutout: '40%'
      },
      // 内側：件数
      {
        label: '件数',
        data: agtCountData,
        backgroundColor: agtBgColors.map(c => c.replace('0.85', '0.35')),
        borderColor: agtBgColors.map(c => c.replace(/0\.\d+\)/, '1)')),
        borderWidth: 1,
        radius: '100%',
        cutout: '40%'
      }
    ]
  };

  // ===== config =====
  const agentConfig = {
    type: 'doughnut',
    data: data4,
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: '代理店ごとのシェア（外：売上 / 内：件数）'
        },
        subtitle: {
          display: true,
          text: '色：代理店別'
        },
        legend: {
          position: 'top'
        },
        datalabels: {
          formatter: (value, context) => {
            const ds = context.dataset.data;
            const total = ds.reduce((a, b) => a + b, 0);
            const pct = total ? (value / total * 100) : 0;

            if (pct < 3) return '';   // 3%未満は非表示
            return pct.toFixed(1) + '%';
          },
          color: '#fff',
          font: {
            weight: 'bold',
            size: 12
          }
        }
      }
    },
    plugins: [ChartDataLabels, agtCenterTextPlugin]
  };

  new Chart(ctx4, agentConfig);
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
  // カテゴリ別（外：売上 / 内：件数）2重ドーナツ
  const ctx9 = document.getElementById('catSalesDoughnutChart').getContext('2d');

  const catLabels = ['会', '宴', '食', '会/宴', '会/食', '会/宴/食'];

  const catSalesDoughData = [<?= implode(',', $category_total_sales) ?>].map(v => Math.round(v / 1000)); // 千円
  const catCountDoughData = [<?= implode(',', $category_total_counts) ?>];

  const catSalesDoughTotal = catSalesDoughData.reduce((a, b) => a + b, 0);
  const catCountDoughTotal = catCountDoughData.reduce((a, b) => a + b, 0);

  // 6カテゴリなので6色で十分（余分があっても問題ないですが整理）
  const doughBgColors = [
    'rgba(255, 99, 132, 0.85)',
    'rgba(54, 162, 235, 0.85)',
    'rgba(75, 192, 192, 0.85)',
    'rgba(255, 206, 86, 0.85)',
    'rgba(153, 102, 255, 0.85)',
    'rgba(255, 159, 64, 0.85)'
  ];
  const doughBorderColors = [
    'rgba(255, 99, 132, 1)',
    'rgba(54, 162, 235, 1)',
    'rgba(75, 192, 192, 1)',
    'rgba(255, 206, 86, 1)',
    'rgba(153, 102, 255, 1)',
    'rgba(255, 159, 64, 1)'
  ];

  // 中央テキストプラグイン
  const doughCenterTextPlugin = {
    id: 'doughCenterTextPlugin',
    afterDraw(chart) {
      const { ctx, chartArea } = chart;
      if (!chartArea) return;

      const x = (chartArea.left + chartArea.right) / 2;
      const y = (chartArea.top + chartArea.bottom) / 2;

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle = '#333';

      ctx.font = 'bold 16px sans-serif';
      ctx.fillText(`売上合計 ${catSalesDoughTotal.toLocaleString()} 千円`, x, y - 10);

      ctx.font = 'bold 14px sans-serif';
      ctx.fillText(`件数合計 ${catCountDoughTotal.toLocaleString()} 件`, x, y + 12);

      ctx.restore();
    }
  };

  // data9
  const data9 = {
    labels: catLabels,
    datasets: [
      // 外側：売上
      {
        label: '売上（千円）',
        data: catSalesDoughData,
        backgroundColor: doughBgColors,
        borderColor: doughBorderColors,
        borderWidth: 1,
        radius: '100%',
        cutout: '40%'
      },
      // 内側：件数（同色で薄く）
      {
        label: '件数',
        data: catCountDoughData,
        backgroundColor: doughBgColors.map(c => c.replace('0.85', '0.35')),
        borderColor: doughBorderColors,
        borderWidth: 1,
        radius: '100%',
        cutout: '40%'
      }
    ]
  };

  const config9 = {
    type: 'doughnut',
    data: data9,
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: 'カテゴリ別シェア（外：売上 / 内：件数）'
        },
        subtitle: {
          display: true,
          text: '色：カテゴリ別'
        },
        legend: {
          position: 'top'
        },
        datalabels: {
          formatter: (value, context) => {
            const ds = context.dataset.data;
            const total = ds.reduce((a, b) => a + b, 0);
            const pct = total ? (value / total * 100) : 0;

            if (pct < 3) return ''; // 3%未満は表示しない
            return pct.toFixed(1) + '%';
          },
          color: '#fff',
          font: {
            weight: 'bold',
            size: 12
          }
        }
      }
    },
    plugins: [ChartDataLabels, doughCenterTextPlugin]
  };

  new Chart(ctx9, config9);
</script>
</body>
</html>