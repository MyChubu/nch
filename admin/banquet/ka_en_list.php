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
require_once('functions/admin_banquet.php');
$date = date('Y-m-d');
if(isset($_REQUEST['event_date']) && $_REQUEST['event_date'] != '') {
  $date = $_REQUEST['event_date'];
}
$array = getKaEnList($date);

$events_en = $array['events_en'];
$events_ka = $array['events_ka'];
$events_other = $array['events_other'];
$events = $array['events'];
$amount_en = $array['amount_en'];
$amount_ka = $array['amount_ka'];

$today = date('Y-m-d');

$dt = new DateTime($date);

$dt_day_before = clone $dt;
$dt_day_before->modify('-1 day');
$day_before = $dt_day_before->format('Y-m-d');
// 1日後
$dt_day_after = clone $dt;
$dt_day_after->modify('+1 day');
$day_after = $dt_day_after->format('Y-m-d');

// 1週間前
$dt_week_before = clone $dt;
$dt_week_before->modify('-7 days');
$week_before = $dt_week_before->format('Y-m-d');

// 1週間後
$dt_week_after = clone $dt;
$dt_week_after->modify('+7 days');
$week_after = $dt_week_after->format('Y-m-d');

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>会議・宴会一覧（<?=$date ?>）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/form.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">

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
        <div id="week_before"><a href="?event_date=<?= $week_before ?>" title="前週：<?= $week_before ?>"><i class="fa-solid fa-arrow-left"></i><i class="fa-solid fa-arrow-left"></i>前週</a></div>
        <div id="day_before"><a href="?event_date=<?= $day_before ?>" title="<?= $day_before ?>"><i class="fa-solid fa-arrow-left"></i>前日</a></div>
        <div id="today"><a href="?event_date=<?= $today ?>" title="<?= $today ?>">今日</a></div>
        <div id="day_after"><a href="?event_date=<?= $day_after ?>" title="<?= $day_after ?>">翌日<i class="fa-solid fa-arrow-right"></i></a></div>
        <div id="week_after"><a href="?event_date=<?= $week_after ?>" title="翌週：<?= $week_after ?>">翌週<i class="fa-solid fa-arrow-right"></i><i class="fa-solid fa-arrow-right"></i></a></div>
      </div>
    </div>
    <div id="controller_right">

    </div>
    
  </div>
  <h1>【宴会】</h1>
  <?php if(sizeof($events_en) > 0): ?>
    <table class="event_table">
      <tr>
        <th><i class="fa-solid fa-hashtag"></i></th>
        <th>イベント名</th>
        <th><i class="fa-solid fa-user"></i></th>
        <th><i class="fa-solid fa-location-dot"></i></th>
        <th><i class="fa-solid fa-stairs"></i></th>
        <th><i class="fa-solid fa-arrow-right"></i></th>
        <th><i class="fa-solid fa-arrow-left"></i></th>
        <th><i class="fa-solid fa-utensils"></i></th>
        <th><i class="fa-solid fa-people-group"></i></th>
        <th><i class="fa-solid fa-wine-glass"></i></th>
        <th><i class="fa-solid fa-signal"></i></th>
        <th><i class="fa-solid fa-flag-checkered"></i></th>
        <th><i class="fa-solid fa-display"></i></th>
      </tr>
      <?php for($i=0; $i<sizeof($events_en); $i++ ) :?>
        <?php if($events_en[$i]['status'] !=5):  ?>
        <tr id="row_<?=$i ?>">
          <td><a href="./connection_list.php?resid=<?=$events_en[$i]['reservation_id'] ?>" title="<?= $events_en[$i]['reservation_id'] ?>"><?= $events_en[$i]['reservation_id'] ?></a></td>
          <td>
            <?php
              $agent_name ="";
              if($events_en[$i]['agent_id'] > 0):
                if($events_en[$i]['agent_name'] != ""){
                  $agent_name = $events_en[$i]['agent_name'];
                }elseif($events_en[$i]['reserver'] != ""){
                  $agent_name = $events_en[$i]['reserver'];
                }elseif($events_en[$i]['agent_group'] != ""){
                  $agent_name = $events_en[$i]['agent_group'];
                }
              ?>
                <div class="agent_name"><?= $agent_name ?></div>
            <?php endif; ?>

            <div class="event_name">
              <?php if($events_en[$i]['additional_sales'] == 1): ?>
                <span class="additional_sales">追</span>
              <?php endif; ?>
              <?= $events_en[$i]['event_name'] ?>
            </div>
          </td>
          <td><?= cleanLanternName($events_en[$i]['pic']) ?></td>
          <td><?= $events_en[$i]['room_name'] ?></td>
          <td><?= $events_en[$i]['floor'] ?></td>
          <td><?= $events_en[$i]['start'] ?></td>
          <td><?= $events_en[$i]['end'] ?></td>
          <td>
            <?php 
              if($events_en[$i]['meal'] != null):
                foreach($events_en[$i]['meal'] as $meal):
                  echo '<div>'.$meal['short_name'] . ' @' . number_format($meal['unit_price']) . ' x ' . $meal['qty'] . '</div>';
                endforeach;
              else:
                echo '&nbsp;';
              endif;
            ?>
          </td>
          <td><?= $events_en[$i]['people'] ?></td>
          <td>
            <?php
              if( sizeof($events_en[$i]['drink1']) > 0 ):
                foreach($events_en[$i]['drink1'] as $drink):
                  $dc=$drink['short_name'];
                  explode('-',$dc);
                  echo '<div><span class="dc dc-'.$dc[3].'">'.$dc[3]. '</span></div>';
                endforeach;
              else:
                echo '-';
              endif;
            ?>
          </td>
          <td><?= statusletter($events_en[$i]['status']) ?></td>
          <td><?= mb_convert_kana($events_en[$i]['purpose_short'],'KVas') ?></td>
          <td>
            <?php if($events_en[$i]['enable'] == 1): ?>
              <i class="fa-solid fa-square-check"></i>
            <?php else: ?>
              &nbsp;
            <?php endif; ?>
          </td>
        </tr>
        <?php endif; ?>
      <?php endfor; ?>
    </table>
    <div class="ka_en_amount">&yen;<?=number_format($amount_en) ?></div>
  <?php else: ?>
    <p>会議の予約はありません</p>
  <?php endif; ?>
  <h1>【会議】</h1>
  <?php if(sizeof($events_ka) > 0): ?>
    <table class="event_table">
      <tr>
        <th><i class="fa-solid fa-hashtag"></i></th>
        <th>イベント名</th>
        <th><i class="fa-solid fa-user"></i></th>
        <th><i class="fa-solid fa-location-dot"></i></th>
        <th><i class="fa-solid fa-stairs"></i></th>
        <th><i class="fa-solid fa-arrow-right"></i></th>
        <th><i class="fa-solid fa-arrow-left"></i></th>
        <th><i class="fa-solid fa-people-group"></i></th>
        <th><i class="fa-solid fa-signal"></i></th>
        <th><i class="fa-solid fa-flag-checkered"></i></th>
        <th><i class="fa-solid fa-display"></i></th>
      </tr>
      <?php for($i=0; $i<sizeof($events_ka); $i++ ) :?>
        <?php if($events_ka[$i]['status'] !=5):  ?>
          
        <tr id="row_<?=$i ?>">
          <td><a href="./connection_list.php?resid=<?=$events_ka[$i]['reservation_id'] ?>" title="<?=$events_ka[$i]['reservation_id'] ?>"><?= $events_ka[$i]['reservation_id'] ?></a></td>
          <td>
            <?php
              $agent_name ="";
              if($events_ka[$i]['agent_id'] > 0):
                if($events_ka[$i]['agent_name'] != ""){
                  $agent_name = $events_ka[$i]['agent_name'];
                }elseif($events_ka[$i]['reserver'] != ""){
                  $agent_name = $events_ka[$i]['reserver'];
                }elseif($events_ka[$i]['agent_group'] != ""){
                  $agent_name = $events_ka[$i]['agent_group'];
                }
              ?>
                <div class="agent_name"><?= $agent_name ?></div>
            <?php endif; ?>
            <div class="event_name">
              <?php if($events_ka[$i]['additional_sales'] == 1): ?>
                <span class="additional_sales">追</span>
              <?php endif; ?>
              <?= $events_ka[$i]['event_name'] ?>
            </div>
          </td>
          <td><?= cleanLanternName($events_ka[$i]['pic']) ?></td>
          <td><?= $events_ka[$i]['room_name'] ?></td>
          <td><?= $events_ka[$i]['floor'] ?></td>
          <td><?= $events_ka[$i]['start'] ?></td>
          <td><?= $events_ka[$i]['end'] ?></td>
          <td><?= $events_ka[$i]['people'] ?></td>
          <td><?= statusletter($events_ka[$i]['status']) ?></td>
          <td><?= mb_convert_kana($events_ka[$i]['purpose_short'],'KVas') ?></td>
          <td>
            <?php if($events_ka[$i]['enable'] == 1): ?>
              <i class="fa-solid fa-square-check"></i>
            <?php else: ?>
              &nbsp;
            <?php endif; ?>
          </td>
        </tr>
        <?php endif; ?>
      <?php endfor; ?>
    </table>
    <div class="ka_en_amount">&yen;<?=number_format($amount_ka) ?></div>
  <?php else: ?>
    <p>宴会の予約はありません</p>
  <?php endif; ?>
  <h1>【その他】</h1>
  <?php if(sizeof($events_other) > 0): ?>
    <table class="event_table">
      <tr>
        <th><i class="fa-solid fa-hashtag"></i></th>
        <th>イベント名</th>
        <th><i class="fa-solid fa-user"></i></th>
        <th><i class="fa-solid fa-location-dot"></i></th>
        <th><i class="fa-solid fa-stairs"></i></th>
        <th><i class="fa-solid fa-arrow-right"></i></th>
        <th><i class="fa-solid fa-arrow-left"></i></th>
        <th><i class="fa-solid fa-people-group"></i></th>
        <th><i class="fa-solid fa-signal"></i></th>
        <th><i class="fa-solid fa-flag-checkered"></i></th>
        <th><i class="fa-solid fa-display"></i></th>
      </tr>
      <?php for($i=0; $i<sizeof($events_other); $i++ ) :?>
        <?php if($events_other[$i]['status'] !=5):  ?>
        <tr id="row_<?=$i ?>">
          <td><a href="./connection_list.php?resid=<?=$events_other[$i]['reservation_id'] ?>" title="<?=$events_other[$i]['reservation_id'] ?>"><?= $events_other[$i]['reservation_id'] ?></a></td>
          <td>
            <?php
              $agent_name ="";
              if($events_other[$i]['agent_id'] > 0):
                if($events_other[$i]['agent_name'] != ""){
                  $agent_name = $events_other[$i]['agent_name'];
                }elseif($events_other[$i]['reserver'] != ""){
                  $agent_name = $events_other[$i]['reserver'];
                }elseif($events_other[$i]['agent_group'] != ""){
                  $agent_name = $events_other[$i]['agent_group'];
                }
              ?>
                <div class="agent_name"><?= $agent_name ?></div>
            <?php endif; ?>
            <div class="event_name">
              <?php if($events_other[$i]['additional_sales'] == 1): ?>
                <span class="additional_sales">追</span>
              <?php endif; ?>
              <?= $events_other[$i]['event_name'] ?>
            </div>
          </td>
          <td><?= cleanLanternName($events_other[$i]['pic']) ?></td>
          <td><?= $events_other[$i]['room_name'] ?></td>
          <td><?= $events_other[$i]['floor'] ?></td>
          <td><?= $events_other[$i]['start'] ?></td>
          <td><?= $events_other[$i]['end'] ?></td>
          <td><?= $events_other[$i]['people'] ?></td>
          <td><?= statusletter($events_other[$i]['status']) ?></td>
          <td><?= mb_convert_kana($events_other[$i]['purpose_short'],'KVas') ?></td>
          <td>
            <?php if($events_other[$i]['enable'] == 1): ?>
              <i class="fa-solid fa-square-check"></i>
            <?php else: ?>
              &nbsp;
            <?php endif; ?>
          </td>
        </tr>
        <?php endif; ?>
      <?php endfor; ?>
    </table>
  <?php else: ?>
    <p>宴会の予約はありません</p>
  <?php endif; ?>
  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>