<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../common/conf.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);


$banq_min_date = '2025-08-01';
$banq_max_date = '2025-08-31';

$sql = "SELECT reservation_id FROM banquet_schedules 
  WHERE date BETWEEN :banq_min_date AND :banq_max_date
    AND status IN (1,2,3)
  GROUP BY reservation_id";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':banq_min_date', $banq_min_date, PDO::PARAM_STR);
$stmt->bindValue(':banq_max_date', $banq_max_date, PDO::PARAM_STR);
$stmt->execute();
$count = $stmt->rowCount();
if($count > 0) {
  $active_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
  $active_ids = implode(',', $active_ids);
  #var_dump($active_ids);

  $sql = "SELECT reservation_id, branch FROM banquet_schedules
    WHERE reservation_id IN ($active_ids)
      AND status = 5
    GROUP BY reservation_id, branch
    ORDER BY reservation_id, branch";
  $stmt = $dbh->prepare($sql);
  $stmt->execute();
  $ina_cnt= $stmt->rowCount();
  if($ina_cnt > 0) {
    $inas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    var_dump($inas);
    var_dump("\n<br><br>\n");
    foreach($inas as $ina) {
      $reservation_id = $ina['reservation_id'];
      $branch = $ina['branch'];

      $sql ="SELECT reservation_id, branch, detail_number, package_category FROM banquet_charges
        WHERE reservation_id = :reservation_id
          AND branch = :branch
       GROUP BY reservation_id, branch, detail_number, package_category
       ORDER BY reservation_id, branch, detail_number, package_category";
      $stmt = $dbh->prepare($sql);
      $stmt->bindValue(':reservation_id', $reservation_id, PDO::PARAM_INT);
      $stmt->bindValue(':branch', $branch, PDO::PARAM_INT);
      $stmt->execute();
      $detail_count = $stmt->rowCount();
      if($detail_count > 0) {
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        var_dump($details);
        var_dump("\n<br><br>\n");
      }
    }
  }
}

?>