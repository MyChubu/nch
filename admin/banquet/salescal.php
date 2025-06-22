<?php
// ▼ 開発中のエラー出力を有効にする（本番環境では無効化すること）
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');

// 年月を初期化
$ym = date('Y-m');
if (isset($_REQUEST['ym']) && $_REQUEST['ym'] != '') {
  $ym = $_REQUEST['ym'];
}
$week = array('日', '月', '火', '水', '木', '金', '土');

// データ取得
$array = getMonthlySales($ym);
$last_day = $array['last_day'];
$year_month = $array['year_month'];
$rooms = $array['rooms'];
$sales = $array['sales'];
$total_kaigi = $array['total_kaigi'];
$total_enkai = $array['total_enkai'];
$total_shokuji = $array['total_shokuji'];
$total_others = $array['total_others'];
$total = $array['total'];
$add_coll = (32 - $last_day);

// 月の数字を取得（DateTimeを使用）
$month = (new DateTime($ym))->format('n');

// 月初日を取得
$first_day = (new DateTime($ym))->format('Y-m-01');

// 今月
$this_month = date('Y-m');

// 前月・翌月をDateTimeで計算
$before_month = (new DateTime($first_day))->modify('-1 month')->format('Y-m');
$after_month = (new DateTime($first_day))->modify('+1 month')->format('Y-m');
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>カレンダー表示（<?=$ym ?>）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/salescal.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="js/admin_banquet.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
  <div>
    <form enctype="multipart/form-data" id="schedate_change">
      <input type="month" name="ym" id="ym" value="<?= $ym ?>">
      <button type="submit">月変更</button>
    </form>
    <div id="controller_month">
      <div id="before_month"><a href="?ym=<?= $before_month ?>"><i class="fa-solid fa-arrow-left"></i>前月</a></div>
      <div id="this_month"><a href="?ym=<?= $this_month ?>">今月</a></div>
      <div id="after_month"><a href="?ym=<?= $after_month ?>">翌月<i class="fa-solid fa-arrow-right"></i></a></div>
      <div id="download"><a href="output/salescal-csv2.php?ym=<?= $ym ?>" target="_blank"><i class="fa-solid fa-download"></i>CSV</a></div>
      <div id="download"><a href="output/salescal-excel-export.php?ym=<?= $ym ?>" target="_blank"><i class="fa-solid fa-file-excel"></i>Excel</a></div>

    </div>
  </div>
  <div><?=$year_month ?></div>

  <!-- 前半（1〜16日） -->
  <div class="block">
    <table>
      <tr>
        <th class="floor bb1">階</th><th class="room bb1">会場名</th><th class="item bb1">項目</th>
        <?php
        for($i=1; $i<=16; $i++) {
          $date = $ym . '-' . sprintf('%02d', $i);
          $w = (new DateTime($date))->format('w'); // 曜日取得
          $wd = $week[$w];
          echo "<th class='bb1'>" . ($i == 1 ? "$month/$i" : "$i") . "<br>$wd</th>";
        }
        ?>
      </tr>
      <?php foreach($rooms as $room): ?>
        <?php $room_id = $room['room_id']; ?>
        <tr>
          <td rowspan="3" class="floor bb1"><?=$room['floor'] ?></td>
          <td rowspan="3" class="room bb1"><?=$room['room_name'] ?></td>
          <td class="item">名称</td>
          <?php for($i=1; $i<=16; $i++): ?>
            <?php
              $bc = "";
              $reservation_name = "&nbsp;";
              $date = $ym . '-' . sprintf('%02d', $i);
              foreach($sales as $sale) {
                if($sale['room_id'] == $room_id && $sale['date'] == $date && $sale['additional_sales'] != 1) {
                  $reservation_name = $sale['reservation_name'];
                  $bc = $sale['banquet_category_id'];
                  break;
                }
              }
              echo "<td class='bc$bc'>$reservation_name</td>";
            ?>
          <?php endfor; ?>
        </tr>
        <tr>
          <td class="item">時間（人数）</td>
          <?php for($i=1; $i<=16; $i++): ?>
            <?php
              $bc = "";
              $value = "&nbsp;";
              $date = $ym . '-' . sprintf('%02d', $i);
              foreach($sales as $sale) {
                if($sale['room_id'] == $room_id && $sale['date'] == $date && $sale['additional_sales'] != 1) {
                  $start = (new DateTime($sale['start']))->format('H:i');
                  $end = (new DateTime($sale['end']))->format('H:i');
                  $people = $sale['people'];
                  $value = "$start-$end ($people)";
                  $bc = $sale['banquet_category_id'];
                  break;
                }
              }
              echo "<td class='bc$bc'>$value</td>";
            ?>
          <?php endfor; ?>
        </tr>
        <tr>
          <td class="item bb1">金額</td>
          <?php for($i=1; $i<=16; $i++): ?>
            <?php
              $bc = "";
              $ex_ts = "";
              $date = $ym . '-' . sprintf('%02d', $i);
              foreach($sales as $sale) {
                if($sale['room_id'] == $room_id && $sale['date'] == $date && $sale['additional_sales'] != 1) {
                  $ex_ts = number_format($sale['ex_ts'] ?? 0);
                  $bc = $sale['banquet_category_id'];
                  break;
                }
              }
              echo "<td class='bc$bc bb1'>$ex_ts</td>";
            ?>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- 後半（17日〜末日） -->
  <div class="block">
    <table>
      <tr>
        <th class="floor bb1">階</th><th class="room bb1">会場名</th><th class="item bb1">項目</th>
        <?php
        for($i=17; $i<=$last_day; $i++) {
          $date = $ym . '-' . sprintf('%02d', $i);
          $w = (new DateTime($date))->format('w');
          $wd = $week[$w];
          echo "<th class='bb1'>" . ($i == 17 ? "$month/$i" : "$i") . "<br>$wd</th>";
        }
        for($i=1; $i<=$add_coll; $i++) {
          echo "<th class='bc0 bb1'>&nbsp;</th>";
        }
        ?>
      </tr>
      <?php foreach($rooms as $room): ?>
        <?php $room_id = $room['room_id']; ?>
        <tr>
          <td rowspan="3" class="floor bb1"><?=$room['floor'] ?></td>
          <td rowspan="3" class="room bb1"><?=$room['room_name'] ?></td>
          <td class="item">名称</td>
          <?php for($i=17; $i<=$last_day; $i++): ?>
            <?php
              $bc = "";
              $reservation_name = "&nbsp;";
              $date = $ym . '-' . sprintf('%02d', $i);
              foreach($sales as $sale) {
                if($sale['room_id'] == $room_id && $sale['date'] == $date && $sale['additional_sales'] != 1) {
                  $reservation_name = $sale['reservation_name'];
                  $bc = $sale['banquet_category_id'];
                  break;
                }
              }
              echo "<td class='bc$bc'>$reservation_name</td>";
            ?>
          <?php endfor; ?>
          <?php for($i=1; $i<=$add_coll; $i++) echo "<td class='bc0'>&nbsp;</td>"; ?>
        </tr>
        <tr>
          <td class="item">時間（人数）</td>
          <?php for($i=17; $i<=$last_day; $i++): ?>
            <?php
              $bc = "";
              $value = "&nbsp;";
              $date = $ym . '-' . sprintf('%02d', $i);
              foreach($sales as $sale) {
                if($sale['room_id'] == $room_id && $sale['date'] == $date && $sale['additional_sales'] != 1) {
                  $start = (new DateTime($sale['start']))->format('H:i');
                  $end = (new DateTime($sale['end']))->format('H:i');
                  $people = $sale['people'];
                  $value = "$start-$end ($people)";
                  $bc = $sale['banquet_category_id'];
                  break;
                }
              }
              echo "<td class='bc$bc'>$value</td>";
            ?>
          <?php endfor; ?>
          <?php for($i=1; $i<=$add_coll; $i++) echo "<td class='bc0'>&nbsp;</td>"; ?>
        </tr>
        <tr>
          <td class="item bb1">金額</td>
          <?php for($i=17; $i<=$last_day; $i++): ?>
            <?php
              $bc = "";
              $ex_ts = "";
              $date = $ym . '-' . sprintf('%02d', $i);
              foreach($sales as $sale) {
                if($sale['room_id'] == $room_id && $sale['date'] == $date && $sale['additional_sales'] != 1) {
                  $ex_ts = number_format($sale['ex_ts'] ?? 0);
                  $bc = $sale['banquet_category_id'];
                  break;
                }
              }
              echo "<td class='bc$bc bb1'>$ex_ts</td>";
            ?>
          <?php endfor; ?>
          <?php for($i=1; $i<=$add_coll; $i++) echo "<td class='bc0'>&nbsp;</td>"; ?>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- 合計表 -->
  <div class="block">
    <table>
      <tr>
        <th class="item">項目</th>
        <th class="item">金額</th>
      </tr>
      <tr><td class="bc1">会議</td><td class="bc1"><?=number_format($total_kaigi) ?></td></tr>
      <tr><td class="bc2">宴会</td><td class="bc2"><?=number_format($total_enkai) ?></td></tr>
      <tr><td class="bc3">食事</td><td class="bc3"><?=number_format($total_shokuji) ?></td></tr>
      <tr><td class="bc9">その他</td><td class="bc9"><?=number_format($total_others) ?></td></tr>
      <tr style="background-color:#ddd;"><td>合計</td><td><?=number_format($total) ?></td></tr>
    </table>
  </div>
  <div class="block">
    <p>※金額は税・サービス料抜きです。</p>
    <p>※このカレンダーは、各会場の予約状況を月単位で表示しています。</p>
    <p>※同日同会場で複数の利用がある場合、金額が高い・利用人数が多い予約が表示されますが、表示金額は同会場の合計値を表示します。</p>
    <p>※追加売上は表示されませんが、合計には加算されます。</p>
    <p>※月を跨ぐ案件がある場合は、他の集計と合計値が異なることがあります。</p>
  </div>
</main>
<?php include("footer.php"); ?>
</body>
</html>
