<?php
// ▼ 開発中のエラー出力を有効にする（本番環境では無効化すること）
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

// 必要な設定ファイルと関数を読み込み
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');

// 今日の日付を取得（フォーマット: Y-m-d）
$today = (new DateTime())->format('Y-m-d');

// 指定された年月があれば取得、なければ今月
$ym = date('Y-m');
if (isset($_REQUEST['ym']) && $_REQUEST['ym'] !== '') {
    $ym = $_REQUEST['ym'];
}

// 年と月を分解（表示用）
$yearmonth = explode('-', $ym);

// 指定年月の月初日を取得
$first_day_dt = new DateTime($ym . '-01');
$first_day = $first_day_dt->format('Y-m-d');

// 月の最終日を取得（例: 31）
$last_day = $first_day_dt->format('t');

// 前月と翌月を計算
$before_month = (clone $first_day_dt)->modify('-1 month')->format('Y-m');
$next_month = (clone $first_day_dt)->modify('+1 month')->format('Y-m');

// 月初日の曜日番号を取得（0=日, 1=月, ..., 6=土）
$fw = (int)$first_day_dt->format('w');

// カレンダーの開始日を算出（週の先頭＝月曜日にそろえる）
$start_date_dt = clone $first_day_dt;
$start_date_dt->modify('-' . (($fw + 6) % 7) . ' days'); // 月曜始まり
$start_date = $start_date_dt->format('Y-m-d');

// カレンダーの終了日を算出
$end_date_dt = (clone $first_day_dt)->modify('+' . $last_day . ' days');
$ew = (int)$end_date_dt->format('w');
$days = 35; // デフォルトは5週分

if ($ew <= 3) {
    // 表示行が6行必要になるケース（次月の数日が1週分になる場合）
    $end_date_dt->modify('+' . (13 - $ew) . ' days');
    $days = 42;
} elseif ($ew <= 5) {
    // 金・土で終わる場合、2～3日追加
    $end_date_dt->modify('+' . (5 - $ew) . ' days');
} elseif ($ew === 6) {
    // 土曜終わり→日曜追加
    $end_date_dt->modify('+1 day');
}
$end_date = $end_date_dt->format('Y-m-d');

// データベース接続と予約データの取得
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = "SELECT
        `reservation_id`,
        `reservation_name`,
        `date`,
        `status`,
        min(`start`) as `start`,
        max(`end`) as `end`,
        count(`reservation_id`) as `count`,
        `pic`
        FROM `banquet_schedules`
        WHERE `date` BETWEEN :start_date AND :end_date
        AND `status` <> 5
        AND `reservation_name` NOT LIKE '朝食会場'
        AND `reservation_name` NOT LIKE '倉庫'
        GROUP BY `reservation_id`, `date`
        ORDER BY `date`, `start`, `end`, `reservation_id`";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':start_date', $start_date, PDO::PARAM_STR);
$stmt->bindValue(':end_date', $end_date, PDO::PARAM_STR);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
$stmt->closeCursor();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=$ym ?>予定</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="css/monthly.css">
</head>
<body>
<?php include("header.php"); ?>
<main>
  <!-- 月のコントローラー（前月・当月・翌月） -->
  <div id="controller_month">
    <div id="before_month"><a href="?ym=<?= $before_month ?>" title="<?= $before_month ?>"><i class="fa-solid fa-arrow-left"></i>前月</a></div>
    <div id="this_month"><span><?= $yearmonth[0] ?>年<?= $yearmonth[1] ?>月</span></div>
    <div id="next_month"><a href="?ym=<?= $next_month ?>" title="<?= $next_month ?>">翌月<i class="fa-solid fa-arrow-right"></i></a></div>
  </div>

<?php if($count > 0): ?>
<!-- カレンダー本体 -->
<table class="cal3w">
  <tr>
    <th>月</th>
    <th>火</th>
    <th>水</th>
    <th>木</th>
    <th>金</th>
    <th>土</th>
    <th>日</th>
  </tr>
  <?php
  // 表示日数分ループ
  for ($i = 0; $i < $days; $i++) {
      $event_date_dt = (clone $start_date_dt)->modify("+$i days");
      $event_date = $event_date_dt->format('Y-m-d');
      $w = (int)$event_date_dt->format('w'); // 曜日番号

      if ($w === 1) echo "<tr>"; // 月曜日なら新しい行

      // 今日／過去日／未来日で背景色を変える
      if ($today === $event_date) {
          echo "<td class='today'>";
      } elseif ($event_date < $today) {
          echo "<td class='pastday'>";
      } else {
          echo "<td>";
      }

      // 日付リンク表示
      echo "<div class='event_date'>【<a href='ka_en_list.php?event_date=$event_date' target='_blank' title='$event_date の予定'>" . $event_date_dt->format('m/d') . "</a>】</div>";

      $store_time = "";
      $store_name = "";

      // 当日のイベントを表示
      foreach ($events as $event) {
          if ($event['date'] === $event_date) {
              $purpose = (strpos($event['reservation_name'], '下見') !== false) ? '下見' : '';

              // 開始時刻をフォーマット
              $start_dt = DateTime::createFromFormat('Y-m-d H:i:s', $event['start']);
              if (!$start_dt) continue;
              $event_time = $start_dt->format('H:i');

              $event_name = cleanLanternName($event['reservation_name']);
              $pic = mb_convert_kana($event['pic'], 'KVas');
              $pic = explode(' ', $pic);

              // 同時刻・同名イベントの重複防止
              if ($store_time !== $event_time || $store_name !== $event_name) {
                  echo ($event['status'] == 2) ? "<div class='event event-kari'>" : "<div class='event'>";
                  echo "<span class='event-time'>{$event_time}</span>";
                  if ($purpose === '下見') {
                      echo '<span class="pur"><i class="fa-regular fa-eye"></i></span>';
                  }
                  echo "<span class='event-name'>{$event_name}</span>";
                  if ($pic[0] !== "") {
                      echo "<span class='pic'>{$pic[0]}</span>";
                  }
                  echo "</div>";
                  $store_time = $event_time;
                  $store_name = $event_name;
              }
          }
      }

      echo "</td>";
      if ($w === 0) echo "</tr>"; // 日曜日なら行を閉じる
  }
  ?>
</table>

<!-- 凡例の表示 -->
<div>
  <div class="legend">凡例：
    <span class="event-kari">仮予約</span>
    <span class="pur"><i class="fa-regular fa-eye"></i>下見</span>
  </div>
</div>

<?php else: ?>
<!-- イベントがない場合 -->
  <div class="no_event">データはありません。</div>
<?php endif; ?>
</main>
<?php include("../common/footer.php"); ?>
</body>
</html>
