<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
$date = date('Y-m-d');
if(isset($_REQUEST['event_date']) && $_REQUEST['event_date'] != '') {
  $date = $_REQUEST['event_date'];
}
$events = getBanquetEvents($date);

$today = (new DateTime())->format('Y-m-d');
$currentDate = new DateTime($date);

$day_before = (clone $currentDate)->modify('-1 day')->format('Y-m-d');
$day_after = (clone $currentDate)->modify('+1 day')->format('Y-m-d');
$week_before = (clone $currentDate)->modify('-7 day')->format('Y-m-d');
$week_after = (clone $currentDate)->modify('+7 day')->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>デジサイ表示設定（<?=$date ?>）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="js/admin_banquet.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <div id="controller">
    <div id="controller_left">
      <form  enctype="multipart/form-data" id="schedate_change">
        <input type="date" name="event_date" id="event_date" value="<?= $date ?>">
        <button type="submit">日付変更</button>
      </form>
      <div id="controller_date">
        <div id="week_before"><a href="?event_date=<?= $week_before ?>"><i class="fa-solid fa-arrow-left"></i><i class="fa-solid fa-arrow-left"></i>前週</a></div>
        <div id="day_before"><a href="?event_date=<?= $day_before ?>"><i class="fa-solid fa-arrow-left"></i>前日</a></div>
        <div id="today"><a href="?event_date=<?= $today ?>">今日</a></div>
        <div id="day_after"><a href="?event_date=<?= $day_after ?>">翌日<i class="fa-solid fa-arrow-right"></i></a></div>
        <div id="week_after"><a href="?event_date=<?= $week_after ?>">翌週<i class="fa-solid fa-arrow-right"></i><i class="fa-solid fa-arrow-right"></i></a></div>
      </div>
    </div>
    <div id="controller_right">
      <div id="disp_signage">表示切替</div>
      <div id="preview"><a href="../../banquet/preview.php?event_date=<?= $date ?>" target="_blank">プレビュー</a></div>
    </div>
    
  </div>
  <?php if(sizeof($events) > 0): ?>
    <table class="event_table">
      <tr>
        <th><i class="fa-solid fa-hashtag"></i></th>
        <th>イベント名</th>
        <th><i class="fa-solid fa-calendar-days"></i></th>
        <th><i class="fa-solid fa-arrow-right"></i></th>
        <th><i class="fa-solid fa-arrow-left"></i></th>
        <th><i class="fa-solid fa-location-dot"></i></th>
        <th><i class="fa-solid fa-stairs"></i></th>
        <th><i class="fa-solid fa-signal"></i></th>
        <th><i class="fa-solid fa-layer-group"></i></th>
        <th><i class="fa-solid fa-flag-checkered"></i></th>
        <th><i class="fa-solid fa-display"></i></th>
        <th><i class="fa-solid fa-floppy-disk"></i></th>
        <th><i class="fa-solid fa-gear"></i></th>
        
      </tr>
      <?php for($i=0; $i<sizeof($events); $i++ ) :?>
        <tr id="row_<?=$i ?>" class="event_tr_<?=$events[$i]['enable']  ?><?=$events[$i]['enable']==0?" non_disp":""; ?> ">
          <td><a href="./connection_list.php?resid=<?=$events[$i]['reservation_id'] ?>"><?= $events[$i]['reservation_id'] ?></a></td>
          <td>
            <input type="text" name="events[<?=$i ?>][event_name]" value="<?= $events[$i]['event_name'] ?>">
          </td>
          <td><?= $events[$i]['date'] ?></td>
          <td><?= $events[$i]['start'] ?></td>
          <td><?= $events[$i]['end'] ?></td>
          <td><?= $events[$i]['room_name'] ?></td>
          <td><?= $events[$i]['floor'] ?></td>
          <td><?= statusletter($events[$i]['status']) ?></td>
          <td><?= salescatletter($events[$i]['category_id']) ?></td>
          <td><?= mb_convert_kana($events[$i]['purpose_short'],'KVas') ?></td>
          
          <td>
            <label class="toggleButton">
              <input type="checkbox" class="toggleButton__checkbox" name="events[<?=$i ?>][enable]" <?=$events[$i]['enable']==1? 'checked':''; ?> />
            </label>
          </td>
          <td><?=$events[$i]['modified'] ?></td>
          <td><a href="detail.php?scheid=<?= $events[$i]['banquet_schedule_id'] ?>">詳細</a></td>
          <input type="hidden" name="events[<?=$i ?>][sche_id]" value="<?= $events[$i]['banquet_schedule_id'] ?>">
        </tr>
        
      <?php endfor; ?>
    </table>
  <?php else: ?>
    <p>予約はありません</p>
  <?php endif; ?>
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>