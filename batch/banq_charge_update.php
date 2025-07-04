
<?php
// ▼ 設定ファイルの読み込み
require_once('../common/conf.php');

// ▼ CSVファイル保存ディレクトリの定義（未定義なら定義）
if (!defined('CSV_DATA_PATH')) {
  define('CSV_DATA_PATH', '../data/csv/');
}

// ▼ データベース接続確認
if (!isset($dbh)) {
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
}

// ▼ 処理対象のCSVファイルを取得（status = 2, csv_kind = 3）
$sql = 'SELECT * FROM csvs WHERE status = 2 AND csv_kind = 3 ORDER BY csv_id ASC';
$res = $dbh->query($sql);

if ($res->rowCount() > 0) {
  foreach ($res as $value) {
    $csv_id = $value['csv_id'];
    $filename = $value['filename'];
    $file_path = CSV_DATA_PATH . $filename;
    $start_time = date('Y-m-d H:i:s');

    // ▼ ステータスを「処理中（1）」に更新
    $stmt = $dbh->prepare('UPDATE csvs SET status = 1, modified = NOW() WHERE csv_id = ?');
    $stmt->execute([$csv_id]);

    // ▼ ファイル読み込み
    $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) continue;

    // ▼ ヘッダー検証
    $expected_headers = [
      '予約番号', '実施日', '提供会場枝番', '明細枝番', '予約宴会名称',
      '主会場ｺｰﾄﾞ', 'ﾊﾟｯｹｰｼﾞ科目ｺｰﾄﾞ', 'ﾊﾟｯｹｰｼﾞ科目名称', 'ﾊﾟｯｹｰｼﾞ商品ｺｰﾄﾞ', 'ﾊﾟｯｹｰｼﾞ商品名称',
      '科目ｺｰﾄﾞ', '科目名称', '商品ｺｰﾄﾞ', '商品名称', '単価', '数量',
      '請求金額(GROSS)', '請求金額(NET)', '請求金額(ｻｰﾋﾞｽ料)', '請求金額(消費税)',
      '割引名称', '割引率', '割引金額'
    ];
    $actual_headers = str_getcsv(mb_convert_encoding($lines[0], 'UTF-8', 'SJIS-win'), ",", '"');
    if ($actual_headers !== $expected_headers) {
      $stmt = $dbh->prepare('UPDATE csvs SET status = 90, modified = NOW() WHERE csv_id = ?');
      $stmt->execute([$csv_id]);
      continue;
    }

    // ▼ トランザクション開始
    $dbh->beginTransaction();

    // ▼ INSERT / UPDATE ステートメントを準備
    $update_sql = 'UPDATE banquet_charges SET 
      date = ?,
      reservation_name = ?,
      main_room_id = ?,
      package_category = ?,
      package_cat_name = ?,
      package_id = ?,
      package_name = ?,
      item_group_id = ?,
      item_group_name = ?,
      item_id = ?,
      item_name = ?,
      item_gene_id = ?,
      unit_price = ?,
      qty = ?,
      amount_gross = ?,
      amount_net = ?,
      service_fee = ?,
      tax = ?,
      discount_name = ?,
      discount_rate = ?,
      discount_amount = ?,
      modified=NOW(),
      modified_by="csvdata"
      WHERE reservation_id = ? AND branch = ? AND detail_number = ? AND item_group_id = ?';
    $update_stmt = $dbh->prepare($update_sql);

    $insert_sql = 'INSERT INTO banquet_charges (
      reservation_id,
      date,
      branch,
      detail_number,
      reservation_name,
      main_room_id,
      package_category,
      package_cat_name,
      package_id,
      package_name,
      item_group_id,
      item_group_name,
      item_id,
      item_name,
      item_gene_id,
      unit_price,
      qty,
      amount_gross,
      amount_net,
      service_fee,
      tax,
      discount_name,
      discount_rate,
      discount_amount,
      added,
      modified,
      modified_by
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)';
    $insert_stmt = $dbh->prepare($insert_sql);

    $reservation_date_key = [];

    // ▼ データ行の処理（1行目はヘッダーなのでスキップ）
    foreach (array_slice($lines, 1) as $line) {
      $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');
      $data = str_getcsv($line, ",", '"');

      // ▼ データを変数へ格納
      $reservation_id = intval($data[0]);
      $date = $data[1];
      $branch = intval($data[2]);
      $detail_number = intval($data[3]);
      $reservation_name = mb_convert_kana($data[4], "KVas");
      $main_room = $data[5];
      $package_category = $data[6];
      $package_cat_name = mb_convert_kana($data[7], "KVas");
      $package_id = mb_convert_kana($data[8], "KVas");
      $package_name = mb_convert_kana($data[9], "KVas");
      $item_group_id = mb_convert_kana($data[10], "KVas");
      $item_group_name = mb_convert_kana($data[11], "KVas");
      $item_id = mb_convert_kana($data[12], "KVas");
      $item_name = mb_convert_kana($data[13], "KVas");
      $item_gene_id = $item_group_id . "-" . $item_id;
      $unit_price = intval($data[14]);
      $qty = intval($data[15]);
      $amount_gross = intval($data[16]);
      $amount_net = intval($data[17]);
      $service_fee = intval($data[18]);
      $tax = intval($data[19]);
      $discount_name = mb_convert_kana($data[20], "KVas");
      $discount_rate = intval($data[21]);
      $discount_amount = intval($data[22]);

      // ▼ 重複確認キー
      $key = $reservation_id . '|' . $branch . '|' . $detail_number . '|' . $item_group_id;
      $check_sql = 'SELECT 1 FROM banquet_charges WHERE reservation_id = ? AND branch = ? AND detail_number = ? AND item_group_id = ?';
      $check_stmt = $dbh->prepare($check_sql);
      $check_stmt->execute([$reservation_id, $branch, $detail_number, $item_group_id]);
      $exists = $check_stmt->fetchColumn();

      // ▼ 削除対象追跡用キー
      $key2 = $reservation_id . '|' . $date;
      if (!isset($reservation_date_key[$key2])) $reservation_date_key[$key2] = [];
      $reservation_date_key[$key2][] = $detail_number;

      if ($exists) {
        // ▼ UPDATE実行
        $update_stmt->execute([
          $date ,
          $reservation_name,
          $main_room,
          $package_category,
          $package_cat_name,
          $package_id,
          $package_name,
          $item_group_id,
          $item_group_name,
          $item_id,
          $item_name,
          $item_gene_id,
          $unit_price,
          $qty,
          $amount_gross,
          $amount_net,
          $service_fee,
          $tax,
          $discount_name,
          $discount_rate,
          $discount_amount,
          $reservation_id,
          $branch,
          $detail_number,
          $item_group_id
        ]);
      } else {
        // ▼ INSERT実行
        $insert_stmt->execute([
          $reservation_id,
          $date,
          $branch,
          $detail_number,
          $reservation_name,
          $main_room,
          $package_category,
          $package_cat_name,
          $package_id,
          $package_name,
          $item_group_id,
          $item_group_name,
          $item_id,
          $item_name,
          $item_gene_id,
          $unit_price,
          $qty,
          $amount_gross,
          $amount_net,
          $service_fee,
          $tax,
          $discount_name,
          $discount_rate,
          $discount_amount,
          'csvdata'
        ]);
      }
    }

    // ▼ トランザクション確定
    $dbh->commit();

    // ▼ 不要明細の削除処理
    foreach ($reservation_date_key as $key => $detail_numbers) {
      list($reservation_id, $date) = explode('|', $key);
      $placeholders = implode(',', array_fill(0, count($detail_numbers), '?'));
      $params = array_merge([$reservation_id, $date], $detail_numbers);
      $sql = "DELETE FROM banquet_charges WHERE reservation_id = ? AND date = ? AND detail_number NOT IN ($placeholders)";
      $stmt = $dbh->prepare($sql);
      $stmt->execute($params);

      // ▼ 古いデータも削除
      $sql = "DELETE FROM banquet_charges WHERE reservation_id = ? AND date = ? AND modified < ?";
      $stmt = $dbh->prepare($sql);
      $stmt->execute([$reservation_id, $date, $start_time]);
    }

    // ▼ ステータスを「完了（0）」に戻す
    $stmt = $dbh->prepare('UPDATE csvs SET status = 0, modified = NOW() WHERE csv_id = ?');
    $stmt->execute([$csv_id]);
  }
}

// ▼ 古いCSVファイルの削除処理（1日以上前）
$keydate = (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s');
$stmt = $dbh->prepare('SELECT * FROM csvs WHERE status = 0 AND csv_kind = 3 AND modified < ?');
$stmt->execute([$keydate]);
foreach ($stmt as $value) {
  $file = CSV_DATA_PATH . $value['filename'];
  if (file_exists($file)) unlink($file);
  $stmt_del = $dbh->prepare('UPDATE csvs SET status = 80, modified = NOW() WHERE csv_id = ?');
  $stmt_del->execute([$value['csv_id']]);
}
?>
