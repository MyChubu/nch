<?php
require_once('../common/conf.php');

if(defined('CSV_DATA_PATH') == false) {
  #define('CSV_DATA_PATH', DATA_DIR . 'csv/');
  #define('CSV_DATA_PATH', '/home/LA06926062/nch.netmedia.works/data/csv/');
  define('CSV_DATA_PATH', '../data/csv/');
}
if(isset($dbh) == false){
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
}
$sql = 'select * from csvs where status = 2 and csv_kind = 3 order by csv_id asc';
$res = $dbh->query($sql);
$count = $res->rowCount();

if($count > 0){
  foreach ($res as $value) {
    $csv_id=$value['csv_id'];
    $filename = $value['filename'];
    $csv_kind = $value['csv_kind'];
    $sql = 'update csvs set status = 1, modified = now() where csv_id = ?';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$csv_id]);

    // **ファイルを読み込む**
    $file = file(CSV_DATA_PATH.$filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $i=0;

    foreach($file as $line){
      if($i > 0){
        
        #echo $i."行目\n";
        // **エンコーディング変換**
        $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');

        // **デバッグ用：元のデータを表示**
        #echo "RAW LINE: " . $line . "\n";

        // **STEP 4: `str_getcsv()` を適用**
        $data = str_getcsv($line, ",", '"');


        // **デバッグ用**
        #var_dump($data);

        // **カラム数チェック**
        /*
        if (count($data) !== 23) {
            echo "⚠️ カラム数不一致: " . count($data) . " 列 (期待値: 23)\n";
        }
        */
        $reservation_id=intval($data[0]);
        $date=$data[1];
        $branch=intval($data[2]);
        $detail_number=intval($data[3]);
        $reservation_name=mb_convert_kana($data[4], "KVas");
        $main_room=$data[5];
        $package_category=$data[6];
        $package_cat_name=mb_convert_kana($data[7], "KVas");
        $package_id = mb_convert_kana($data[8], "KVas");
        $package_name=mb_convert_kana($data[9], "KVas");
        $item_group_id = mb_convert_kana($data[10], "KVas");
        $item_group_name=mb_convert_kana($data[11], "KVas");
        $item_id = mb_convert_kana($data[12], "KVas");
        $item_name=mb_convert_kana($data[13], "KVas");
        $item_gene_id = $item_group_id."-".$item_id;
        $unit_price=intval($data[14]);
        $qty=intval($data[15]);
        $amount_gross=intval($data[16]);
        $amount_net=intval($data[17]);
        $service_fee=intval($data[18]);
        $tax=intval($data[19]);
        $discount_name=mb_convert_kana($data[20], "KVas");
        $discount_rate=intval($data[21]);
        $discount_amount=intval($data[22]);
        $check_sql = 'select * from banquet_charges where reservation_id = ? and branch = ? and detail_number = ? and item_group_id = ?';
        $check_d = $dbh->prepare($check_sql);
        $check_d->execute([$reservation_id, $branch, $detail_number, $item_group_id]);
        $check_count = $check_d->rowCount();

        // 処理済みdetail_numberを記録
        $key = $reservation_id . '|' . $date;
        if (!isset($reservation_date_key[$key])) {
          $reservation_date_key[$key] = [];
        }
        $reservation_date_key[$key][] = $detail_number;

        if($check_count > 0){
          try{
            $dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbh -> beginTransaction();
            $sql = 'update banquet_charges set 
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
              modified = now(),
              modified_by = "csvdata"
              where reservation_id = ? and branch = ? and detail_number = ? and item_group_id = ?';
            $stmt = $dbh->prepare($sql);
            $stmt->execute([
              $date,
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
            $dbh -> commit();
          }catch(PDOException $e){
            $dbh -> rollBack();
            echo 'Error: ' . $e->getMessage();
          }
        }else{
          try{
            $dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbh -> beginTransaction();
            $sql = 'insert into banquet_charges (
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
              modified_by) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,now(),now(),?)';
            $stmt = $dbh->prepare($sql);
            $stmt->execute([
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
            $dbh -> commit();
          }catch(PDOException $e){
            $dbh -> rollBack();
            echo 'Error: ' . $e->getMessage();
          }
        }
      }
      $i++;
    }

    // --- ここで削除処理を追加 ---
    foreach ($reservation_date_key as $key => $detail_numbers) {
      list($reservation_id, $date) = explode('|', $key);

      // IN句のplaceholders作成
      $placeholders = implode(',', array_fill(0, count($detail_numbers), '?'));

      // 処理されなかった detail_number を削除
      $sql = "DELETE FROM banquet_charges 
              WHERE reservation_id = ? AND date = ? AND detail_number NOT IN ($placeholders)";
      $params = array_merge([$reservation_id, $date], $detail_numbers);

      $stmt = $dbh->prepare($sql);
      $stmt->execute($params);
    }

    
    $sql = 'update csvs set status = ?, modified = now() where csv_id = ?';
    $stmt = $dbh->prepare($sql); 
    $stmt->execute([0,$csv_id]);
  }
}
// CSVファイルの削除
$keydate = date("Y-m-d H:i:s", strtotime("-1 day"));  // 1日前の日付を取得
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