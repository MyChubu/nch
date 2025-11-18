<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
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
$scheid = isset($_REQUEST['scheid']) ? $_REQUEST['scheid'] : '';
if( $scheid == '' ){
  header('Location: signage.php');
  exit;
}

// 予定の情報を取得
$sql = "SELECT * FROM banquet_schedules WHERE banquet_schedule_id = :scheid";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':scheid', $scheid, PDO::PARAM_INT);
$stmt->execute();
$rowCount = $stmt->rowCount();
if ($rowCount < 1) {
  header('Location: signage.php');
  exit;
}
$row = $stmt->fetch();
$dateObj = new DateTime($row['date']);
$date = $dateObj->format('Y-m-d');
$startObj = new DateTime($row['start']);
$endObj = new DateTime($row['end']);
$starttime=$startObj->format('H:i');
$endtime=$endObj->format('H:i');

// 会場（部屋）の情報を取得
$sql2 = 'select * from banquet_rooms where banquet_room_id = ?';
$stmt2 = $dbh->prepare($sql2);
$stmt2->execute([$row['room_id']]);
$room = $stmt2->fetch();
$floor = $room['floor']; // 階数

// 使用目的の情報を取得
$sql3 = 'select * from banquet_purposes where banquet_purpose_id = ?';
$stmt3 = $dbh->prepare($sql3);
$stmt3->execute([$row['purpose_id']]);
$purpose = $stmt3->fetch();
$purpose_name = $purpose['banquet_purpose_name'];
$purpose_short = $purpose['banquet_purpose_short'];
$banquet_category_id = $purpose['banquet_category_id'];

// カテゴリー情報を取得
$sql4 = 'select * from banquet_categories where banquet_category_id = ?';
$stmt4 = $dbh->prepare($sql4);
$stmt4->execute([$banquet_category_id]);
$category = $stmt4->fetch();
$category_name = $category['banquet_category_name'];

//例外表示新規追加
if($_POST['cat'] == 2){
  // var_dump($_POST['n']);
  $n=array();
  $er=array();
  $err=0;
  $items=  array('sche_id','date','start','end','event_name','enable','memo');
  $requireds = array('sche_id','date','start','end','event_name');
  foreach($items as $item){
    $n[$item] = isset($_POST['n'][$item]) ? $_POST['n'][$item] : '';
    $er[$item] = 0;
  }
  foreach($requireds as $req){
    if($n[$req] == ''){
      $er[$req] = 1;
      $err++;
    }
  }
  // var_dump($er);
  // var_dump($err);
  if($err == 0){
    $start=$n['date'].' '.$n['start'].':00';
    $end=$n['date'].' '.$n['end'].':00';
  }
  $n['enable'] = ($n['enable'] == '1') ? 1 : 0;
  $n['event_name'] = mb_convert_kana($n['event_name'],'KVas');
  $sqln = 'insert into banquet_ext_sign (
  sche_id,
  date,
  start,
  end,
  event_name,
  memo,
  enable,
  added,
  modified
  ) values (
  :sche_id,
  :date,
  :start,
  :end,
  :event_name,
  :memo,
  :enable,
  now(),
  now()
  )';
  $stmtn = $dbh->prepare($sqln);
  $stmtn->bindParam(':sche_id', $n['sche_id'], PDO::PARAM_INT);
  $stmtn->bindParam(':date', $n['date'], PDO::PARAM_STR);
  $stmtn->bindParam(':start', $start, PDO::PARAM_STR);
  $stmtn->bindParam(':end', $end, PDO::PARAM_STR);
  $stmtn->bindParam(':event_name', $n['event_name'], PDO::PARAM_STR);
  $stmtn->bindParam(':memo', $n['memo'], PDO::PARAM_STR);
  $stmtn->bindParam(':enable', $n['enable'], PDO::PARAM_INT);
  $stmtn->execute(); 
}

//例外表示変更
if($_POST['cat'] == 1){
  //var_dump($_POST['v']);
  $v=$_POST['v'];
  for($i=0; $i < sizeof($v); $i++){
    $item = $v[$i];
    $er=array();
    $err=0;
    $items=  array('sche_id','date','start','end','event_name','enable','id','memo');
    $requireds = array('sche_id','date','start','end','event_name','id');
    foreach($items as $it){
      $item[$it] = isset($item[$it]) ? $item[$it] : '';
      $er[$it] = 0;
    }
    foreach($requireds as $req){
      if($item[$req] == ''){
        $er[$req] = 1;
        $err++;
      }
    }
    // var_dump($er);
    // var_dump($err);
    if($err == 0){
      $start=$item['date'].' '.$item['start'].':00';
      $end=$item['date'].' '.$item['end'].':00';
      $item['enable'] = ($item['enable'] == 'on') ? 1 : 0;
      $item['event_name'] = mb_convert_kana($item['event_name'],'KVas');
      $sqla = 'update banquet_ext_sign set
      start = :start,
      end = :end,
      event_name = :event_name,
      memo = :memo,
      enable = :enable,
      modified = now()
      where banquet_ext_sign_id = :id
      ';
      $stmta = $dbh->prepare($sqla);
      $stmta->bindParam(':start', $start, PDO::PARAM_STR);
      $stmta->bindParam(':end', $end, PDO::PARAM_STR);
      $stmta->bindParam(':event_name', $item['event_name'], PDO::PARAM_STR);
      $stmta->bindParam(':memo', $item['memo'], PDO::PARAM_STR);
      $stmta->bindParam(':enable', $item['enable'], PDO::PARAM_INT);
      $stmta->bindParam(':id', $item['id'], PDO::PARAM_INT);
      $stmta->execute(); 
    }
  }

}


// 拡張表示があるか調べる
$ext_signs = array();
$sql5 = 'select * from banquet_ext_sign where sche_id = ? order by enable desc, start asc, end asc';
$stmt5 = $dbh->prepare($sql5);
$stmt5->execute([$row['banquet_schedule_id']]);
$ext_count = $stmt5->rowCount();
if ($ext_count > 0) {
  foreach ($stmt5 as $ext_row) {
    $ext_signs[] = $ext_row;
  }
}
        

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>デジサイ拡張表示設定（<?=$row['event_name'] ?>）</title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/form.css">
  <link rel="stylesheet" href="css/table_sort.css">
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
 

    </div>
    <div id="controller_right">

    </div>
    
  </div>
  <div>
    <h2><?=$row['event_name'] ?></h2>
    <h3>基本表示</h3>
    <table class="event_table">
      <tr>
        <th><i class="fa-solid fa-signal"></i></th>
        <th><i class="fa-solid fa-hashtag"></i></th>
        <th><i class="fa-solid fa-code-branch"></i></th>
        <th><i class="fa-solid fa-calendar-days"></i></th>
        <th><i class="fa-solid fa-arrow-right"></i></th>
        <th><i class="fa-solid fa-arrow-left"></i></th>
        <th><i class="fa-solid fa-location-dot"></i></th>
        <th><i class="fa-solid fa-users"></i></th>
        <th><i class="fa-solid fa-user"></i></th>
        <th><i class="fa-solid fa-bell-concierge"></i></th>
        <th><i class="fa-solid fa-flag-checkered"></i></th>
        <th><i class="fa-solid fa-display"></i></th>
      </tr>
      <tr>
        <td><?=statusletter($row['status']) ?></td>
        <td><?=$row['reservation_id'] ?></td>
        <td><?=$row['branch'] ?></td>
        <td><?=$date ?></td>
        <td><?=$starttime ?></td> 
        <td><?=$endtime ?></td>
        <td><?=$row['room_name'] ?></td>
        <td><?=$row['people'] ?></td>
        <td><?=cleanLanternName($row['pic']) ?></td>
        <td><?=salescatletter($banquet_category_id) ?></td>
        <td><?=mb_convert_kana($purpose_short,'KVas') ?></td>
        <td>
          <?php if($row['enable'] == 1): ?>
            <i class="fa-solid fa-square-check"></i>
          <?php else: ?>
            &nbsp;
          <?php endif; ?>
        </td>
      </tr>
    </table>
  </div>
  <h2>例外表示設定</h2>
  <div>
  <?php if(sizeof($ext_signs) > 0): ?>
    <form action="" method="post" enctype="multipart/form-data" id="extsignagemod">
    <table class="event_table">
      <tr>
        <th><i class="fa-solid fa-arrow-right"></i></th>
        <th><i class="fa-solid fa-arrow-left"></i></th>
        <th>デジサイ表示名</th>
        <th><i class="fa-solid fa-display"></i></th>
        <th><i class="fa-solid fa-pen"></i></th>
      </tr>
      
      <?php $i=0; ?>
      <?php foreach ($ext_signs as $ext) : ?>
        <?php
          $ext_startObj = new DateTime($ext['start']);
          $ext_endObj = new DateTime($ext['end']);
          $ext_starttime = $ext_startObj->format('H:i');
          $ext_endtime = $ext_endObj->format('H:i');
        ?>
      <tr class="event_tr_<?=$ext['enable'] ?>">
        <td><input type="time" name="v[<?=$i ?>][start]" min="<?=$starttime ?>" max="<?=$endtime ?>" value="<?=$ext_starttime ?>" required></td>
        <td><input type="time" name="v[<?=$i ?>][end]" min="<?=$starttime ?>" max="<?=$endtime ?>" value="<?=$ext_endtime ?>" required></td>
        <td><input type="text" name="v[<?=$i ?>][event_name]" value="<?=$ext['event_name'] ?>" placeholder="<?= $row['event_name'] ?>" required></td>
        <td><input type="hidden" name="v[<?=$i ?>][enable]" value="0"><input type="checkbox" name="v[<?=$i ?>][enable]" <?= $ext['enable'] == 1 ? 'checked' : '' ?>></td>
        <td><textarea name="v[<?=$i ?>][memo]"><?=$ext['memo'] ?></textarea></td>
        <input type="hidden" name="v[<?=$i ?>][sche_id]" value="<?= $scheid ?>">
        <input type="hidden" name="v[<?=$i ?>][id]" value="<?= $ext['banquet_ext_sign_id'] ?>">
        <input type="hidden" name="v[<?=$i ?>][date]" value="<?= $date ?>">
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </table>
    <div class="form_buttons">
      <button type="submit" form="extsignagemod">変更を保存</button>
      <input type="hidden" name="cat" value="1">
    </div>
    </form>
  <?php else: ?>
    <p>現在、例外表示設定はありません。</p>
  <?php endif; ?>
  </div>
  <!-- 新規追加行 -->
  <h2>表示追加</h2>
  <div>
    <form action="" method="post" enctype="multipart/form-data" id="extsignagenew">
    <table class="event_table">
      <tr>
        <th><i class="fa-solid fa-arrow-right"></i></th>
        <th><i class="fa-solid fa-arrow-left"></i></th>
        <th>デジサイ表示名</th>
        <th><i class="fa-solid fa-display"></i></th>
        <th><i class="fa-solid fa-pen"></i></th>
      </tr>
      <tr>
        <td><input type="time" name="n[start]" min="<?=$starttime ?>" max="<?=$endtime ?>" value="<?=$starttime ?>" required></td>
        <td><input type="time" name="n[end]" min="<?=$starttime ?>" max="<?=$endtime ?>" value="<?=$endtime ?>" required></td>
        <td><input type="text" name="n[event_name]" placeholder="<?= $row['event_name'] ?>" required></td>
        <td><input type="hidden" name="n[enable]" value="0"><input type="checkbox" name="n[enable]" value="1" checked></td>
        <td><textarea name="n[memo]"></textarea></td>
      </tr>
        <input type="hidden" name="n[sche_id]" value="<?= $scheid ?>">
    </table>
    <div class="form_buttons">
      <button type="submit" form="extsignagenew">新規登録</button>
      <input type="hidden" name="cat" value="2">
      <input type="hidden" name="n[date]" value="<?= $date ?>">
    </div>
    </form>
  </div>
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>