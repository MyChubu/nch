<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
require_once('../../common/conf.php');
require_once('functions/admin_banquet.php');
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
$week = array('日', '月', '火', '水', '木', '金', '土');
$date = date('Y-m-d');
$w = date('w');
$wd= $week[$w];

$items = array(
  'reservation_id',
  'reservation_date1',
  'reservation_date2',
  'status',
  'reservation_name',
  'agent',
  'agent_name',
  'pic',
  'room'
);

if (isset($_POST['search']) && (int)$_POST['search'] === 1) {

  // ------- 正規化（配列化・トリム） -------
  $reservation_id     = isset($_POST['reservation_id'])     && $_POST['reservation_id'] !== '' ? (int)$_POST['reservation_id'] : null;
  $reservation_date1  = isset($_POST['reservation_date1'])  && $_POST['reservation_date1'] !== '' ? $_POST['reservation_date1'] : null;
  $reservation_date2  = isset($_POST['reservation_date2'])  && $_POST['reservation_date2'] !== '' ? $_POST['reservation_date2'] : null;

  // status/room は単一でも配列に寄せる
  $status = [];
  if (isset($_POST['status'])) {
    $status = is_array($_POST['status']) ? $_POST['status'] : [$_POST['status']];
    $status = array_values(array_filter(array_map('intval', $status), fn($v)=>$v!==0)); // 0除外
  }
  $rooms = [];
  if (isset($_POST['room'])) {
    $rooms = is_array($_POST['room']) ? $_POST['room'] : [$_POST['room']];
    $rooms = array_values(array_filter(array_map('intval', $rooms), fn($v)=>$v!==0));
  }

  $reservation_name = isset($_POST['reservation_name']) ? trim((string)$_POST['reservation_name']) : '';
  $agent            = isset($_POST['agent']) && $_POST['agent'] !== '' ? (int)$_POST['agent'] : null; // 0=直販
  $agent_name       = isset($_POST['agent_name']) ? trim((string)$_POST['agent_name']) : '';
  $pic              = isset($_POST['pic']) && $_POST['pic'] !== '' ? (int)$_POST['pic'] : null;

  // ------- WHERE句組み立て -------
  $whereParts = [];
  $params     = [];

  if ($reservation_id) {
    $whereParts[] = 'reservation_id = :reservation_id';
    $params[':reservation_id'] = $reservation_id;
  }

  // 日付レンジ（どちらか片方のみでもOK）
  // ※ view_monthly_new_reservation2 側の実施日カラム名は "reservation_date" を想定
  $colDate = 'reservation_date';
  $d1 = $reservation_date1 ? DateTime::createFromFormat('Y-m-d', $reservation_date1) : null;
  $d2 = $reservation_date2 ? DateTime::createFromFormat('Y-m-d', $reservation_date2) : null;
  if ($d1 && $d2) {
    // 入替え補正
    if ($d1 > $d2) [$d1, $d2] = [$d2, $d1];
    $whereParts[] = "$colDate BETWEEN :d1 AND :d2";
    $params[':d1'] = $d1->format('Y-m-d');
    $params[':d2'] = $d2->format('Y-m-d');
  } elseif ($d1) {
    $whereParts[] = "$colDate >= :d1";
    $params[':d1'] = $d1->format('Y-m-d');
  } elseif ($d2) {
    $whereParts[] = "$colDate <= :d2";
    $params[':d2'] = $d2->format('Y-m-d');
  }

  // 状態（IN 句）
  if (!empty($status)) {
    $in = [];
    foreach ($status as $i => $st) {
      $key = ":st$i";
      $in[] = $key;
      $params[$key] = (int)$st;
    }
    // viewの状態カラム名は "status" を想定
    $whereParts[] = 'status IN ('.implode(',', $in).')';
  }

  // 予約名（部分一致）
  if ($reservation_name !== '') {
    $whereParts[] = 'reservation_name LIKE :reservation_name ESCAPE \'\\\'';
    $params[':reservation_name'] = '%'.addcslashes($reservation_name, '\%_').'%';
  }

  // 代理店（グループ選択: 0=直販 or agent_id）
  // ビュー側のカラム名は運用に合わせて変更してください：
  //   - agent_id がある場合：そのまま agent_id 比較
  //   - 直販=0 を schedules 側で agent_id IS NULL 等にマッピングしているなら CASE 分岐に変更
  if ($agent !== null) {
    if ($agent === 0) {
      // 直販：agent_id が NULL or 0 を直販と定義（運用に合わせて）
      $whereParts[] = '(agent_id IS NULL OR agent_id = 0)';
    } else {
      $whereParts[] = 'agent_id = :agent_id';
      $params[':agent_id'] = $agent;
    }
  }

  // 代理店名（部分一致／ビュー側に agent_name がある想定）
  if ($agent_name !== '') {
    $whereParts[] = 'agent_name LIKE :agent_name ESCAPE \'\\\'';
    $params[':agent_name'] = '%'.addcslashes($agent_name, '\%_').'%';
  }

  // 担当者（pic_id がある想定）
  if ($pic !== null) {
    $whereParts[] = 'pic_id = :pic_id';
    $params[':pic_id'] = $pic;
  }

  // 使用会場（room_id IN ...）
  if (!empty($rooms)) {
    $in = [];
    foreach ($rooms as $i => $rid) {
      $key = ":room$i";
      $in[] = $key;
      $params[$key] = (int)$rid;
    }
    $whereParts[] = 'room_id IN ('.implode(',', $in).')';
  }

  $where = '';
  if (!empty($whereParts)) {
    $where = 'WHERE '.implode(' AND ', $whereParts);
  }

  // ------- 実行 -------
  $sql = "SELECT * FROM `view_monthly_new_reservation2` $where ORDER BY reservation_date ASC, reservation_id ASC";
  $stmt = $dbh->prepare($sql);
  foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
  }
  $stmt->execute();

  $rsvs  = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $count = count($rsvs);
  var_dump($sql, $params, $count); // デバッグ
}


//部屋リスト
$sql = "SELECT `banquet_room_id`, `name`,`floor` FROM `banquet_rooms` WHERE `status` = 1 ORDER BY `order`, `banquet_room_id`";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

//担当者リスト
$sql = "SELECT `pic_id`, `name` FROM `users` WHERE `status` = 1 AND `group` IN(1,5) ORDER BY `user_id`";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$pics = $stmt->fetchAll(PDO::FETCH_ASSOC);

//代理店リスト
$sql = "SELECT `agent_id`, `agent_group` FROM `banquet_agents`  ORDER BY `agent_id`";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>
    案件検索
  </title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/form.css?<?=date('YmdHis') ?>">
  <link rel="stylesheet" href="css/reservations.css?<?=date('YmdHis') ?>">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  
  <link rel="stylesheet" href="css/table_sort.css?<?=date('YmdHis') ?>">
  <script src="js/table_sort.js"></script>
</head>
<body>
<?php include("header.php"); ?>
<main>
  <div class="wrapper">
    <div id="controller">
      <div id="controller_left">
        <div class="searchbox">
          <form action="search.php" method="post" enctype="multipart/form-data">
          <div>
            予約ID：
            <input type="number" name="reservation_id" id="reservation_id" value="" size="10">
          </div>
          <div>
            実施日：
            <input type="date" name="reservation_date1" id="reservation_date1" value="">
            ～
            <input type="date" name="reservation_date2" id="reservation_date2" value="">
          </div>
          <div>状態：
            <label><input type="checkbox" name="status[]" value="1">決定</label>
            <label><input type="checkbox" name="status[]" value="2">仮予約</label>
            <label><input type="checkbox" name="status[]" value="3">営業押さえ</label>
            <label><input type="checkbox" name="status[]" value="5">キャンセル</label>
          </div>
          <div>
            予約名：
            <input type="text" name="reservation_name" id="reservation_name" value="" size="20">
          </div>
          <div>
            代理店種類：
            <select name="agent" id="agent">
              <option value="">--</option>
              <option value="0">直販</option>
              <?php foreach($agents as $agent): ?>
                <option value="<?=$agent['agent_id'] ?>"><?=$agent['agent_group'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            代理店名：
            <input type="text" name="agent_name" id="agent_name" value="" size="20">
          </div>
          <div>
            担当者：
            <select name="pic" id="pic">
              <option value="">--</option>
              <?php foreach($pics as $pic): ?>
                <option value="<?=$pic['pic_id'] ?>"><?=$pic['name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            使用会場：
            <?php $flr = ""; ?>
            <?php foreach($rooms as $room): ?>
              <?php if($flr != $room['floor']): ?><br><?php endif; ?>
              <label><input type="checkbox" name="room[]" id="" value="<?=$room['banquet_room_id'] ?>"><?=$room['name'] ?>　</label>
            <?php $flr = $room['floor']; ?>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="search" value="1">
          <button type="submit">検索</button>
          <button type="reset" onclick="location.href='search.php'">リセット</button>
          </form>
        </div>
      </div>
      <div id="controller_right2">
       
        
      </div>
    </div>
    <div>
      <h1>案件検索</h1>
    </div>
    <div>
      <h2>決定予約</h2>
      <table class="">
        <thead>
          <tr>
            <th>実施日</th>
            <th>状態</th>
            <th>種類</th>
            <th>予約名</th>
            <th>販売</th>
            <th>人数</th>
            <th>金額</th>
            <th>担当名</th>
            <th>予約ID</th>
            <th>代理店名</th>
            <th>仮期限</th>
            <th>予約登録</th>
            <th>仮予約日</th>
            <th>キャンセル日</th>
            <th>決定日</th>
            <th>memo</th>
            <th>最終</th>
          </tr>
        </thead>
      </table>
    </div>



  </div>
  <?php include("aside.php"); ?>
</main>
  <?php include("footer.php"); ?>

</body>
</html>