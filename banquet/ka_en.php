<?php
require_once('../common/conf.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$week = array('日', '月', '火', '水', '木', '金', '土');
$date = date('Y-m-d');
$hizuke =date('Y年m月d日 ', strtotime($date));
$hizuke .= '（' . $week[date('w', strtotime($date))] . '）';
$sql = 'select * from banquet_schedules where date = ?  order by start ASC, branch ASC';
$stmt = $dbh->prepare($sql);
$stmt->execute([$date]);
$count = $stmt->rowCount();
$events=array();
$events_en=array();
$events_ka=array();
if($count >0){
  foreach ($stmt as $row) {
    $event_date = date('Y/m/d ', strtotime($row['date']));
    $event_start = date('H:i', strtotime($row['start']));
    $event_end = date('H:i', strtotime($row['end']));
    $sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
    $stmt2 = $dbh->prepare($sql2);
    $stmt2->execute([$row['room_id']]);
    $room = $stmt2->fetch();
    $floor = $room['floor'];
    $sql3 = 'select * from banquet_purposes where banquet_purpose_id = ?';
    $stmt3 = $dbh->prepare($sql3);
    $stmt3->execute([$row['purpose_id']]);
    $purpose = $stmt3->fetch();
    $purpose_name = $purpose['banquet_purpose_name'];
    $banquet_category_id = $purpose['banquet_category_id'];
    $summary_category = $purpose['summary_category'];
    $sql4 = 'select * from banquet_categories where banquet_category_id = ?';
    $stmt4 = $dbh->prepare($sql4);
    $stmt4->execute([$banquet_category_id]);
    $category = $stmt4->fetch();
    $category_name = $category['banquet_category_name'];
    $pic = mb_convert_kana($row['pic'], 'KVas');
    $pic = explode(' ', $pic);

    $events[] = array(
      'reservation_id' => $row['reservation_id'],
      'banquet_schedule_id' => $row['banquet_schedule_id'],
      'event_name' => $row['event_name'],
      'start' => $event_start,
      'end' => $event_end,
      'people' => $row['people'],
      'room_name' => $row['room_name'],
      'floor' => $floor,
      'status' => $row['status'],
      'status_name' => $row['status_name'],
      'purpose_id' => $row['purpose_id'],
      'purpose_name' => $purpose_name,
      'category_id' => $banquet_category_id,
      'category_name' => $category_name,
      'summary_category' => $summary_category,
      'pic' => $pic[0]
    );
    if($row['status'] != 4 && $row['status'] != 5){
      if($summary_category == 1 ){
        $events_ka[] = array(
          'reservation_id' => $row['reservation_id'],
          'banquet_schedule_id' => $row['banquet_schedule_id'],
          'event_name' => $row['event_name'],
          'start' => $event_start,
          'end' => $event_end,
          'people' => $row['people'],
          'room_name' => $row['room_name'],
          'floor' => $floor,
          'status' => $row['status'],
          'status_name' => $row['status_name'],
          'purpose_id' => $row['purpose_id'],
          'purpose_name' => $purpose_name,
          'category_id' => $banquet_category_id,
          'category_name' => $category_name,
          'summary_category' => $summary_category,
          'pic' => $pic[0]

        );
      }elseif($summary_category == 2 ){
        $events_en[] = array(
          'reservation_id' => $row['reservation_id'],
          'banquet_schedule_id' => $row['banquet_schedule_id'],
          'event_name' => $row['event_name'],
          'start' => $event_start,
          'end' => $event_end,
          'people' => $row['people'],
          'room_name' => $row['room_name'],
          'floor' => $floor,
          'status' => $row['status'],
          'status_name' => $row['status_name'],
          'purpose_id' => $row['purpose_id'],
          'purpose_name' => $purpose_name,
          'category_id' => $banquet_category_id,
          'category_name' => $category_name,
          'summary_category' => $summary_category,
          'pic' => $pic[0]
        );
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="300">
  <title>本日の会議・宴会</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/ka_en.css">
</head>
<body>
  <header>
    <div class="header_box">
      <h1>本日の会議・宴会</h1>
      <h2><?=$hizuke ?></h2>
    </div>
  </header>
  <main>
    <section>
      <h2>宴会</h2>
      <div>
        <?php if(sizeof($events_en) > 0): ?>
          <table class="event_table">
            <tr>
              <th>イベント名</th>
              <th>担当</th>
              <th>部屋</th>
              <th>フロア</th>
              <th>利用時間</th>
              <th>料理</th>
              <th>@</th>
              <th>人数</th>
              <th>使用目的</th>
              
            </tr>
          <?php for($i=0; $i<sizeof($events_en); $i++ ) :?>
            <?php if($events_en[$i]['status'] !=5):  ?>
          
            <tr id="row_<?=$i ?>">
              <td class="event_name"><?= $events_en[$i]['event_name'] ?></td>
              <td><?= $events_en[$i]['pic'] ?></td>
              <td><?= $events_en[$i]['room_name'] ?></td>
              <td><?= $events_en[$i]['floor'] ?></td>
              <td><?= $events_en[$i]['start'] ?> ～ <?= $events_en[$i]['end'] ?></td>
              <td>個々盛</td>
              <td>6,210</td>
              <td><?= $events_en[$i]['people'] ?></td>
              <td><?= mb_convert_kana($events_en[$i]['purpose_name'],'KVas') ?></td>
              
            </tr>
            <?php endif; ?>
          <?php endfor; ?>
          </table>
        <?php else: ?>
          <p>本日の宴会はありません。</p>
        <?php endif; ?>
      </div>
    </section>
    <section>
      <h2>会議</h2>
      <div>
      <?php if(sizeof($events_ka) > 0): ?>
        <table class="event_table">
          <tr>
            <th>イベント名</th>
            <th>担当</th>
            <th>部屋</th>
            <th>フロア</th>
            <th>利用時間</th>
            <th>人数</th>
            <th>使用目的</th>
            
          </tr>
          <?php for($i=0; $i<sizeof($events_ka); $i++ ) :?>
            <?php if($events_ka[$i]['status'] !=5):  ?>
              
            <tr id="row_<?=$i ?>">
              <td class="event_name"><?= $events_ka[$i]['event_name'] ?></td>
              <td><?= $events_ka[$i]['pic'] ?></td>
              <td><?= $events_ka[$i]['room_name'] ?></td>
              <td><?= $events_ka[$i]['floor'] ?></td>
              <td><?= $events_ka[$i]['start'] ?> ～ <?= $events_ka[$i]['end'] ?></td>
              <td><?= $events_ka[$i]['people'] ?></td>
              <td><?= mb_convert_kana($events_ka[$i]['purpose_name'],'KVas') ?></td>
              
            </tr>
            <?php endif; ?>
          <?php endfor; ?>
        </table>
      <?php else: ?>
        <p>宴会の予約はありません</p>
      <?php endif; ?>
      </div>
      </section>
  </main>

  <footer>

  </footer>
</body>
</html>