<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
$pic= "伊藤　良行";
$ym = date('Y-m');
if(isset($_REQUEST['ym']) && $_REQUEST['ym'] != '') {
  $ym = $_REQUEST['ym'];
}
$sql = "SELECT * FROM `view_banquet_pic` where `pic` = :pic and `ym` = :ym order by `date` asc";
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':pic', $pic, PDO::PARAM_STR);
$stmt->bindValue(':ym', $ym, PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);   
$s_count = count($rows);
$events = array();
if($s_count > 0) {
  foreach($rows as $row) {
    $room_id = $row['room_id'];
    $room_name = $row['room_name'];
    $date = $row['date'];
    $reservation_name = $row['reservation_name'];
    $short_name = cleanLanternName($reservation_name);
    $people = $row['people'];
    $banquet_category_id = $row['banquet_category_id'];
    $banquet_category_name = $row['banquet_category_name'];
    $start= $row['start'];
    $end = $row['end'];
    $gross = $row['gross'];
    $ex_ts = $row['ex-ts'];
    if($banquet_category_id == 1) {
      continue;
    } elseif($banquet_category_id == 2) {
      continue;
    } elseif($banquet_category_id == 3) {
      continue;
    } elseif($banquet_category_id == 9) {
      continue;
    }
    if(!isset($events[$date])) {
      $events[$date] = array();
    }
    if(!isset($events[$date][$room_id])) {
      $events[$date][$room_id] = array(
        'room_name' => $room_name,
        'events' => array()
      );
    }
    array_push($events[$date][$room_id]['events'], array(
      'reservation_name' => $reservation_name,
      'short_name' => $short_name,
      'people' => $people,
      'start' => $start,
      'end' => $end,
      'gross' => $gross,
      'ex_ts' => $ex_ts
    ));
  }
  var_dump($events);
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <div>
    <form  enctype="multipart/form-data" id="schedate_change">
      <input type="month" name="ym" id="ym" value="<?= $ym ?>">
      <button type="submit">月変更</button>

    </form>
  </div>
</div>
</main>
<?php include("footer.php"); ?>
</body>
</html>