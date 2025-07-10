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

$sql = "SELECT * FROM banquet_purposes ORDER BY banquet_purpose_id ASC";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT * FROM banquet_categories WHERE banquet_category_id IN(1,2,3,9) ORDER BY banquet_category_id ASC";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT * FROM banquet_categories WHERE banquet_category_id IN(1,2) ORDER BY banquet_category_id ASC";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$summaries = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <title>使用目的設定</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/edit_master.css">
  <link rel="stylesheet" href="css/form.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  <script src="js/edit_purposes.js"></script>
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
    <h1>使用目的設定</h1>
    <table class="form_table">
      <thead>
        <tr>
          <th class="cell_w40">ID</th>
          <th>名称</th>
          <th class="cell_w80">略称</th>
          <th>デジサイ分類</th>
          <th>二分類</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=0; ?>
        <?php foreach ($results as $purp): ?>
        <tr id="purp_<?=$i ?>">
          <td>
            <select name="value[<?=$i ?>][banquet_purpose_id]" class="master_edit purpose_id" >
              <option value="">↓↓↓</option>
              <?php for($s=0; $s<100; $s++): ?>
              <option value="<?=$s ?>" <?= $s==$purp['banquet_purpose_id']  ? 'selected' : '' ?>><?=$s ?></option>
              <?php endfor; ?>
            </select>
          </td>
          <td>
            <input type="text" name="value[<?=$i ?>][banquet_purpose_name]" value="<?= htmlspecialchars($purp['banquet_purpose_name'], ENT_QUOTES, 'UTF-8') ?>" class="master_edit purpose_name"  >
          </td>
          <td>
            <input type="text" name="value[<?=$i ?>][banquet_purpose_short]" value="<?= htmlspecialchars($purp['banquet_purpose_short'], ENT_QUOTES, 'UTF-8') ?>" class="master_edit purpose_short"  >
         </td>
          <td>
            <select name="value[<?=$i ?>][banquet_category_id]" class="master_edit purpose_cat" >
              <option value="">↓選択</option>
              <?php foreach ($categories as $category): ?>
              <option value="<?= htmlspecialchars($category['banquet_category_id'], ENT_QUOTES, 'UTF-8') ?>" <?= $purp['banquet_category_id'] == $category['banquet_category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['banquet_category_name'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <select name="value[<?=$i ?>][summary_category]" class="master_edit summary_cat" >
              <option value="">↓選択</option>
              <?php foreach ($summaries as $summary): ?>
              <option value="<?= htmlspecialchars($summary['banquet_category_id'], ENT_QUOTES, 'UTF-8') ?>" <?= $purp['summary_category'] == $summary['banquet_category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($summary['banquet_category_name'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <input type="hidden" name="value[<?=$i ?>][pur_id]" value="<?= htmlspecialchars($purp['banquet_purpose_id'], ENT_QUOTES, 'UTF-8') ?>">
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