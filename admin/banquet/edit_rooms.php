<?php
require_once('../../common/conf.php');
include_once('../functions/accesslog.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
session_name('_NCH_ADMIN');
session_start();
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : '';
$user_name = $_SESSION['name'];
accesslog();

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

$sql = "SELECT * FROM banquet_rooms ORDER BY `order` ASC, banquet_room_id ASC";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>宴会場設定</title>
  <link rel="icon" type="image/jpeg" href="../images/nch_mark.jpg">
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/edit_master.css?202512221345">
  <link rel="stylesheet" href="css/form.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="js/edit_common.js"></script>
  <script src="js/edit_rooms.js"></script>
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
    <h1>宴会場設定</h1>
    <div>NEHOPSのマスターデータに合わせてください。</div>
    <div><label><input type="checkbox" id="toggleEdit"  name="editable" <?=$admin!=1?'disabled':''?>> 編集する</label></div>
    <table class="form_table">
      <thead>
        <tr>
          <th class="cell_w80">Room ID</th>
          <th>名称</th>
          <th>英名</th>
          <th class="cell_w60">階</th>
          <th class="cell_w60">㎡</th>
          <th class="cell_w60">使用</th>
          <th class="cell_w60">カレンダー</th>
          <th class="cell_w80">Cal順</th>

        </tr>
      </thead>
      <tbody>
        <?php $i = 0; ?>
        <?php foreach ($results as $room): ?>
        <tr>
          <td>
            <input type="text" name="value[<?=$i ?>][banquet_room_id]" value="<?= htmlspecialchars($room['banquet_room_id'], ENT_QUOTES, 'UTF-8') ?>" class="master_edit master_admin" disabled>
          </td>
          <td>
            <input type="text" name="value[<?=$i ?>][name]" value="<?= htmlspecialchars($room['name'], ENT_QUOTES, 'UTF-8') ?>" class="master_edit master_admin" disabled>
          </td>
          <td>
            <input type="text" name="value[<?=$i ?>][name_en]" value="<?= htmlspecialchars($room['name_en'], ENT_QUOTES, 'UTF-8') ?>" class="master_edit master_admin" disabled>
          </td>
          <td>
            <select name="value[<?=$i ?>][floor]" class="master_edit master_admin" disabled>
              <?php for ($f = 0; $f <= 8; $f++):
                if($f==0){
                  $flr = 'B1';
                }else{
                  $flr = $f. 'F';
                }
                ?>
              <option value="<?= $flr ?>" <?= $room['floor'] == $flr ? 'selected' : '' ?>><?= $flr ?></option>
              <?php endfor; ?>
            </select>
          </td>
          <td>
            <input type="number" name="value[<?=$i ?>][size]" value="<?= htmlspecialchars($room['size'], ENT_QUOTES, 'UTF-8') ?>" class="master_edit master_admin" disabled>
          </td>
          <td>
            <input type="checkbox" class="master_edit master_admin" name="status" value="1" <?= $room['status'] ? 'checked' : '' ?> disabled>
          </td>
          <td>
            <input type="checkbox" class="master_edit master_admin" name="cal" value="1" <?= $room['cal'] ? 'checked' : '' ?> disabled>
          </td>
          <td>
            <input type="number" class="master_edit master_admin" name="order" value="<?= htmlspecialchars($room['order'], ENT_QUOTES, 'UTF-8') ?>" disabled>
          </td>
          <input type="hidden" name="value[<?=$i ?>][r_id]" value="<?= htmlspecialchars($room['banquet_room_id'], ENT_QUOTES, 'UTF-8') ?>">
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div>
    <h2>宴会場追加</h2>
    <form id="add_room_form" action="edit_rooms.php" method="post">
      <table class="form_table">
        <tr>
          <th class="cell_w80">Room ID</th>
          <th>名称</th>
          <th>英名</th>
          <th class="cell_w60">階</th>
          <th class="cell_w60">㎡</th>
          <th class="cell_w60">使用</th>
          <th class="cell_w60">カレンダー</th>  
          <th class="cell_w80">表示順</th>
        </tr>
        <tr>
          <td>
            <input type="text" name="new_room_id" value="" class="master_add master_admin" placeholder="000" required disabled>
          </td>
          <td>
            <input type="text" name="new_name" value="" class="master_add master_admin" placeholder="うめ" required disabled>
          </td>
          <td>
            <input type="text" name="new_name_en" value="" class="master_add master_admin" placeholder="Ume" disabled>
          </td>
          <td>
            <select name="new_floor" class="master_add master_admin" disabled>
              <?php for ($f = 0; $f <= 8; $f++):
                if($f==0){
                  $flr = 'B1';
                }else{
                  $flr = $f. 'F';
                }
                ?>
              <option value="<?= $flr ?>"><?= $flr ?></option>
              <?php endfor; ?>
            </select>
          </td>
          <td>
            <input type="number" name="new_size" value="" class="master_add master_admin" placeholder="100" required disabled>
          </td>
          <td>
            <input type="checkbox" class="master_add master_admin" name="new_status" value="1" checked disabled>
          </td>
          <td>
            <input type="checkbox" class="master_add master_admin" name="new_cal" value="1" checked disabled>
          </td>
          <td>
            <input type="number" class="master_add master_admin" name="new_order" value="40" min="1" max="100" required disabled>
          </td>
        </tr>
      </table>
      <div class="form_button">
        <button type="submit" class="btn btn-primary master_add master_admin" disabled>追加</button> 
        <button type="reset" class="btn btn-secondary master_add master_admin" disabled>リセット</button>
      </div>
    </form>
  </div>


</div>
<?php include("aside.php"); ?>
</main>
<?php include("../common/footer.php"); ?>

</body>
</html>