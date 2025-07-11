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

$sql = "SELECT * FROM banquet_items ORDER BY banquet_item_group ASC, item_id ASC";
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
  <title>アイテム設定</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/edit_master.css">
  <link rel="stylesheet" href="css/form.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="js/edit_common.js"></script>
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
    <h1>アイテム設定</h1>
    <div>NEHOPSのマスターデータに合わせてください。</div>
    <div><label><input type="checkbox" id="toggleEdit" name="editable"> 編集する</label></div>
    <table class="form_table">
      <thead>
        <tr>
          <th>ID</th>
          <th>区分</th>
          <th>区分内ID</th>
          <th>名称</th>
          <th>略称</th>
          <th>食事</th>
          <th>飲み放題</th>
          <th>バイオーダー</th>
          <th>使用</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 0; ?>
        <?php foreach ($results as $item): ?>
        <tr>
          <td><?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <input type="text" class="master_edit" name="value[<?=$i ?>][banquet_item_group]" value="<?= htmlspecialchars($item['banquet_item_group'], ENT_QUOTES, 'UTF-8') ?>" disabled>
          </td>
          <td>
            <input type="text" class="master_edit" name="value[<?=$i ?>][item_id]" value="<?= htmlspecialchars($item['item_id'], ENT_QUOTES, 'UTF-8') ?>" disabled>
          </td>  
          <td>
            <input type="text" class="master_edit" name="value[<?=$i ?>][name]" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
          </td>
          <td>
          <input type="text" class="master_edit" name="value[<?=$i ?>][name_short]" value="<?= htmlspecialchars($item['name_short'], ENT_QUOTES, 'UTF-8') ?>" disabled>
          </td>  
          <td>
            <input type="checkbox" class="master_edit" name="value[<?=$i ?>][meal]" value="1" <?= $item['meal'] ? 'checked' : '' ?> disabled>
          </td>
          <td>
            <input type="checkbox" class="master_edit" name="value[<?=$i ?>][drink1]" value="1" <?= $item['drink1'] ? 'checked' : '' ?> disabled>
          </td>
          <td>
            <input type="checkbox" class="master_edit" name="value[<?=$i ?>][drink2]" value="1" <?= $item['drink2'] ? 'checked' : '' ?> disabled>
          </td>
          <td>
            <input type="checkbox" class="master_edit" name="value[<?=$i ?>][enable]" value="1" <?= $item['enable'] ? 'checked' : '' ?> disabled>
          </td>
          <input type="hidden" name="value[<?=$i ?>][id]" value="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>">
        </tr>
        <?php $i++; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>


</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>