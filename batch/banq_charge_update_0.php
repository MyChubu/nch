<?php
require_once('../common/conf.php');

if(defined('CSV_DATA_PATH') == false) {
  define('CSV_DATA_PATH', '../data/csv/');
}

// ===== SQLログ出力 =====
define('SQL_CHG_LOG_PATH','../data/logs/chg_update_insert_'.date('Ymd').'.log'); // 好きな場所に変更OK

function sql_chg_log(string $sql, array $params = [], string $tag = ''): void {
  // 個人情報/長文が出るならここでマスクするのが安全
  $masked = array_map(function($v){
    if ($v === null) return 'NULL';
    if ($v === true) return 'TRUE';
    if ($v === false) return 'FALSE';
    if (is_numeric($v)) return (string)$v;
    $s = (string)$v;
    $s = str_replace(["\r","\n","\t"], ['\\r','\\n','\\t'], $s);
    if (mb_strlen($s) > 300) $s = mb_substr($s, 0, 300) . '...';
    return "'" . addslashes($s) . "'";
  }, $params);

  $line = sprintf(
    "[%s] %s SQL=%s | params=[%s]\n",
    date('Y-m-d H:i:s'),
    $tag,
    $sql,
    implode(", ", $masked)
  );
  file_put_contents(SQL_CHG_LOG_PATH, $line, FILE_APPEND);
}
// ===== SQLログ出力ここまで =====

$dbh = new PDO(DSN, DB_USER, DB_PASS, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
]);

$row = $dbh->query("SELECT * FROM csvs WHERE status=2 AND csv_kind=3 ORDER BY csv_id ASC LIMIT 1")->fetch();
if (!$row) exit;

$csv_id = (int)$row['csv_id'];
$filename = $row['filename'];

// status=1 に
$stmt = $dbh->prepare("UPDATE csvs SET status=1, modified=NOW() WHERE csv_id=?");
$stmt->execute([$csv_id]);

$start_time = date('Y-m-d H:i:s');

$upsertSql = "INSERT INTO banquet_charges (
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
              modified_by)
              VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,now(),now(),'csvdata')
              ON DUPLICATE KEY UPDATE
                date=VALUES(date),
                reservation_name=VALUES(reservation_name),
                main_room_id=VALUES(main_room_id),
                package_category=VALUES(package_category),
                package_cat_name=VALUES(package_cat_name),
                package_id=VALUES(package_id),
                package_name=VALUES(package_name),
                item_group_name=VALUES(item_group_name),
                item_id=VALUES(item_id),
                item_name=VALUES(item_name),
                item_gene_id=VALUES(item_gene_id),
                unit_price=VALUES(unit_price),
                qty=VALUES(qty),
                amount_gross=VALUES(amount_gross),
                amount_net=VALUES(amount_net),
                service_fee=VALUES(service_fee),
                tax=VALUES(tax),
                discount_name=VALUES(discount_name),
                discount_rate=VALUES(discount_rate),
                discount_amount=VALUES(discount_amount),
                modified=NOW(),
                modified_by='csvdata';
              ";

$upsert = $dbh->prepare($upsertSql);

$reservation_date_key = []; // 処理済みの reservation_id と date の組み合わせを記録

try {
  $dbh->beginTransaction();

  $fp = new SplFileObject(CSV_DATA_PATH.$filename, 'rb');

  // ヘッダー
  $header_line = $fp->fgets();
  $header_line = mb_convert_encoding($header_line, 'UTF-8', 'SJIS-win');
  $actual_headers = str_getcsv($header_line);

  $expected_headers = [
      '予約番号', '実施日', '提供会場枝番', '明細枝番', '予約宴会名称',
      '主会場ｺｰﾄﾞ', 'ﾊﾟｯｹｰｼﾞ科目ｺｰﾄﾞ', 'ﾊﾟｯｹｰｼﾞ科目名称', 'ﾊﾟｯｹｰｼﾞ商品ｺｰﾄﾞ', 'ﾊﾟｯｹｰｼﾞ商品名称',
      '科目ｺｰﾄﾞ', '科目名称', '商品ｺｰﾄﾞ', '商品名称', '単価', '数量',
      '請求金額(GROSS)', '請求金額(NET)', '請求金額(ｻｰﾋﾞｽ料)', '請求金額(消費税)',
      '割引名称', '割引率', '割引金額'
    ];
  if ($actual_headers !== $expected_headers) {
    $dbh->rollBack();
    $stmt = $dbh->prepare("UPDATE csvs SET status=90, modified=NOW() WHERE csv_id=?");
    $stmt->execute([$csv_id]);
    exit;
  }

  while (!$fp->eof()) {
    $line = $fp->fgets();
    if ($line === false) break;
    $line = trim($line);
    if ($line === '') continue;

    $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');
    $data = str_getcsv($line);

    $reservation_id = (int)$data[0];
    $date = $data[1];
    $branch = (int)$data[2];
    $detail_number = (int)$data[3];

    // ↓必要な変換はここ（mb_convert_kana多用は地味に重いので、必要項目だけに絞るのも効果あり）
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
    $unit_price = (int)$data[14];
    $qty = (int)$data[15];
    $amount_gross = (int)$data[16];
    $amount_net = (int)$data[17];
    $service_fee = (int)$data[18];
    $tax = (int)$data[19];
    $discount_name = mb_convert_kana($data[20], "KVas");
    $discount_rate = (int)$data[21];
    $discount_amount = (int)$data[22];

    // 処理済みdetail_number記録
    $key = $reservation_id . '|' . $date;
    $reservation_date_key[$key][] = $detail_number;

    // UPSERT（※ここで SELECT はしない）
    $params = [
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
      $discount_amount
    ];
    $upsert->execute($params);

    // ログが重ければ「全行ログ」ではなく「件数/エラーのみ」にするのが速いです
    // sql_chg_log(...); を毎行やるとI/Oで遅くなります
  }
  // 削除処理（今のままでもOKだが、ここもまとめるとさらに速い）
  foreach ($reservation_date_key as $key => $detail_numbers) {
    [$reservation_id, $date] = explode('|', $key);

    $placeholders = implode(',', array_fill(0, count($detail_numbers), '?'));
    $sql = "DELETE FROM banquet_charges
            WHERE reservation_id=? AND date=? AND detail_number NOT IN ($placeholders)";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array_merge([(int)$reservation_id, $date], $detail_numbers));

    $sql = "DELETE FROM banquet_charges
            WHERE reservation_id=? AND date=? AND modified < ?";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([(int)$reservation_id, $date, $start_time]);
  }
  $dbh->commit();

  } catch (Throwable $e) {
  if ($dbh->inTransaction()) $dbh->rollBack();
  // エラーstatusにするなど
  $stmt = $dbh->prepare("UPDATE csvs SET status=90, modified=NOW() WHERE csv_id=?");
  $stmt->execute([$csv_id]);
  throw $e;
}


// CSVファイルの削除
$keydate = (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s'); // 1日前の日付を取得
$sql = 'select * from csvs where status = ? and csv_kind = ? and modified < ?';
$stmt = $dbh->prepare($sql);  // SQL文を実行する準備
$stmt->execute([0,3,$keydate]);  // SQL文を実行
$count = $stmt->rowCount();  // 行数を取得
if($count > 0){
  foreach ($stmt as $value) { // 結果セットから1行ずつ取り出す 
    $csv_id=$value['csv_id'];
    $filename = $value['filename']; // ファイル名を取得
    $file = CSV_DATA_PATH.$filename;  // ファイルのパスを取得
    if(file_exists($file)){ unlink($file);}  // ファイルが存在する場合、ファイルを削除
    $sql = 'update csvs set status = ?, modified = now() where csv_id = ?';  // SQL文を作成
    $stmt = $dbh->prepare($sql);  // SQL文を実行する準備
    $stmt->execute([80,$csv_id]);  // SQL文を実行
  }
}
?>