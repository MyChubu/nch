<?php
require_once('../common/conf.php');

if(defined('CSV_DATA_PATH') == false) {
  define('CSV_DATA_PATH', '../data/csv/');
}
if(isset($dbh) == false){
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
}

$sql = 'SELECT * FROM csvs WHERE status = 2 AND csv_kind = 2 ORDER BY csv_id ASC LIMIT 1'; 
// サーバ性能上、1回につき1件にした
#$sql = 'SELECT * FROM csvs WHERE status = 2 AND csv_kind = 2 ORDER BY csv_id ASC ';
$res = $dbh->query($sql);
$count = $res->rowCount();

if ($count > 0) {
  foreach ($res as $value) {
    $csv_id = $value['csv_id'];
    $filename = $value['filename'];
    $csv_kind = $value['csv_kind'];

    // CSV のステータスを更新（処理中）
    $sql = 'UPDATE csvs SET status = 1, modified = NOW() WHERE csv_id = ?';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$csv_id]);

    // **fopen() を使用してファイルを開く**
    $file_path = CSV_DATA_PATH . $filename;
    if (($handle = fopen($file_path, "r")) !== false) {

      //ファイルパターンをチェック
      $expected_header = [
        '予約番号','予約人数','予約件数','予約宴会名称','会場枝番','会場名称','会場使用日','宴会開始時間','宴会終了時間',
        '行灯名称','会場予約人数','営業担当名称','予約状態ｺｰﾄﾞ','予約状態名称','会場ｺｰﾄﾞ','会場使用目的ｺｰﾄﾞ',
        '会場使用目的名称','会場形式ｺｰﾄﾞ','会場形式名称','ｴｰｼﾞｪﾝﾄｺｰﾄﾞ','エージェン 名称','申込会社 名称','ｴｰｼﾞｪﾝﾄ名称',
        '予約状態備考','売上部門ｺｰﾄﾞ','売上部門名称','実施日','営業担当ｺｰﾄﾞ','追加売上区分','追加売上区分名称',
        'ｷｬﾝｾﾙ日','予約状態期限日','最終更新日','作成日時','更新日時'
      ];
      $first_line = fgets($handle);
      $first_line = mb_convert_encoding($first_line, 'UTF-8', 'SJIS-win');
      $first_line = str_replace(["\r\n", "\r"], "\n", $first_line);
      $header = str_getcsv(trim($first_line), ",");

      if ($header !== $expected_header) {

        // ファイルを閉じて次のファイルへスキップ
        fclose($handle);

        // ステータスをエラー(例: 99)に更新
        $sql = 'UPDATE csvs SET status = ?, modified = NOW() WHERE csv_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->execute([90, $csv_id]);

        continue;
      }


      $i = 0;
      $banq_min_date ="";
      $banq_max_date ="";
      $banq_modtime = date("Y-m-d H:i:s");
      while (($line = fgets($handle)) !== false) {
        if ($i > 0) {  // 1行目（ヘッダー）は無視
        
          // **エンコーディング変換**
          $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');

          // **改行コードを統一**
          $line = str_replace(["\r\n", "\r"], "\n", $line);

          // **STEP 4: `str_getcsv()` を適用**
          $data = str_getcsv($line, ",");

          // **データの処理（データベースへの挿入・更新）**
                      
          $reservation_id=intval($data[0]);
          $reservation_name=mb_convert_kana($data[3], "KVas");
          $branch=intval($data[4]);
          $room_name=$data[5];
          $date=$data[6];
          if($banq_min_date == ""){
            $banq_min_date = $date;
          }else{
            if($date < $banq_min_date){
              $banq_min_date = $date;
            }
          }
          if($banq_max_date == ""){
            $banq_max_date = $date;
          }else{
            if($date > $banq_max_date){
              $banq_max_date = $date;
            }
          }
          $start=$data[6]." ".$data[7];
          $end=$data[6]." ".$data[8];
          $event_name=mb_convert_kana($data[9], "KVas");
          $people=intval($data[10]);
          $pic=$data[11];
          $status_id=intval($data[12]);
          $status_name=mb_convert_kana($data[13], "KVas");
          $room_id=intval($data[14]);
          $purpose_id=intval($data[15]);
          $purpose_name=mb_convert_kana($data[16], "KVas");
          $layout_id=intval($data[17]);
          $layout_name=mb_convert_kana($data[18], "KVas");
          $agent_id=intval($data[19]);
          $agent_name=mb_convert_kana($data[20], "KVas");
          $reserver=mb_convert_kana($data[21], "KVas");
          $agent_group = mb_convert_kana($data[22], "KVas");
          $memo = $data[23];
          if(is_null($memo)){
            $memo = "";
          }else{
            $memo = mb_convert_kana($memo, "KVas");
          }
          $sales_dept_id = intval($data[24]);
          $sales_dept_name = mb_convert_kana($data[25], "KVas");
          $reservation_date = (new DateTime($data[26]))->format('Y-m-d');
          $cancel_date = null;
          if($data[30] !=''){
            $cancel_date = (new DateTime($data[30]))->format('Y-m-d');
          }
          $due_date = null;
          if($data[31] !=''){
            $due_date = (new DateTime($data[31]))->format('Y-m-d');
          }
          $nehops_mod_date =null;
          if($data[32] !=''){
            $nehops_mod_date = (new DateTime($data[32]))->format('Y-m-d');
          }
          $nehops_created = null;
          if($data[33] !=''){
            $nehops_created = (new DateTime($data[33]))->format('Y-m-d H:i:s');
          }
          $nehops_edited = null;
          if($data[34] !=''){
            $nehops_edited = (new DateTime($data[34]))->format('Y-m-d H:i:s');
          }

          $pic_id = $data[27];
          $a8l = $data[28];
          if($a8l == 'N' || $a8l == 'n' || $a8l == '0'  || $a8l == 'false' || $a8l == 'False' || $a8l == 'FALSE'){
            $additional_sales = 0;
          }else{
            $additional_sales = 1;
          }

          $check_sql = 'select * from banquet_schedules where reservation_id = ? and branch = ?';
          $check_d = $dbh->prepare($check_sql);
          $check_d->execute([$reservation_id, $branch]);
          $check_count = $check_d->rowCount();
          if($check_count > 0){
            try{
              $dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
              $dbh -> beginTransaction();
              $sql = 'update banquet_schedules set people = ?,
                reservation_name = ?,
                date = ?,
                start = ?,
                end = ?,
                room_id = ?,
                room_name = ?,
                event_name = ?,
                status = ?,
                status_name = ?,
                purpose_id = ?,
                purpose_name = ?,
                layout_id = ?,
                layout_name = ?,
                pic = ?,
                agent_id = ?,
                agent_name = ?,
                reserver = ?,
                memo = ?,
                sales_dept_id = ?,
                sales_dept_name = ?,
                reservation_date = ?,
                pic_id = ?,
                additional_sales = ?,
                cancel_date = ?,
                due_date = ?,
                nehops_mod_date = ?,
                nehops_created = ?,
                nehops_edited = ?,
                modified = now(),
                modified_by = "csvdata"
                where reservation_id = ? and branch = ?';
              $stmt = $dbh->prepare($sql);
              $stmt->execute([
                $people,
                $reservation_name,
                $date,
                $start,
                $end,
                $room_id,
                $room_name,
                $event_name,
                $status_id,
                $status_name,
                $purpose_id,
                $purpose_name,
                $layout_id,
                $layout_name,
                $pic,
                $agent_id,
                $agent_name,
                $reserver,
                $memo,
                $sales_dept_id,
                $sales_dept_name,
                $reservation_date,
                $pic_id,
                $additional_sales,
                $cancel_date,
                $due_date,
                $nehops_mod_date,
                $nehops_created,
                $nehops_edited,
                $reservation_id,
                $branch
              ]);
              $dbh -> commit();
            }catch(PDOException $e){
              $dbh -> rollBack();
              echo 'Error: ' . $e->getMessage();
            }
          }else{
            $enable = 0;
            if($status_id == 1){
              $enable = 1;
              if($layout_id == 20 || $event_name =='朝食会場' ){
                $enable = 0;
              }else if($additional_sales == 1){
                $enable = 0;
              }
            } 
            
            try{
              $dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
              $dbh -> beginTransaction();
              $sql = 'insert into banquet_schedules (
                reservation_id,
                people,
                branch,
                reservation_name,
                date,
                start,
                end,
                room_id,
                room_name,
                event_name,
                status,
                status_name,
                purpose_id,
                purpose_name,
                layout_id,
                layout_name,
                pic,
                agent_id,
                agent_name,
                reserver,
                memo,
                sales_dept_id,
                sales_dept_name,
                reservation_date,
                pic_id,
                additional_sales,
                cancel_date,
                due_date,
                nehops_mod_date,
                nehops_created,
                nehops_edited,
                enable,
                added,
                modified,
                modified_by) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,now(),now(),?)';
              $stmt = $dbh->prepare($sql);
              $stmt->execute([
                $reservation_id,
                $people,
                $branch,
                $reservation_name,
                $date,
                $start,
                $end,
                $room_id,
                $room_name,
                $event_name,
                $status_id,
                $status_name,
                $purpose_id,
                $purpose_name,
                $layout_id,
                $layout_name,
                $pic,
                $agent_id,
                $agent_name,
                $reserver,
                $memo,
                $sales_dept_id,
                $sales_dept_name,
                $reservation_date,
                $pic_id,
                $additional_sales,
                $cancel_date,
                $due_date,
                $nehops_mod_date,
                $nehops_created,
                $nehops_edited,
                $enable,
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
      
      // **ファイルを閉じる**
      fclose($handle);

      // **CSVで処理したレコードの範囲で漏れているものを抽出
      // **CSVの最小日付から最大日付までの間で、更新日時がCSVの更新日時よりも古いものを抽出
      // **範囲外のものは触らない
      $sql = 'select * from banquet_schedules where date >= ? and date <= ? and modified < ?';
      $stmt = $dbh->prepare($sql);
      $stmt->execute([$banq_min_date, $banq_max_date, $banq_modtime]);
      $count = $stmt->rowCount();

      // **対象データがある場合、キャンセル処理を行う**
      if($count > 0){
        $status_name = 'キャンセル';
        foreach($stmt as $value){
          $schedule_id = $value['banquet_schedule_id'];
          $status = $value['status'];
          // **ステータスが5以外の場合、ステータスを5(キャンセル)に変更**
          // **デジサイ表示なしに変更**
          if($status !=5){
            try{
              $dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
              $dbh -> beginTransaction();
              $sql = 'update banquet_schedules set status =?, status_name = ?, enable = ?, modified = now(), modified_by = "csvdata" where banquet_schedule_id = ?';
              $stmt = $dbh->prepare($sql);
              $stmt->execute([5, $status_name, 0,$schedule_id]);
              $dbh -> commit();
            }catch(PDOException $e){
              $dbh -> rollBack();
              echo 'Error: ' . $e->getMessage();
            }
          }
        }
      }

    } else {
      echo "⚠️ ファイルを開けませんでした: " . $file_path . "\n";
    }

    // **処理完了後、CSV のステータスを元に戻す**
    $sql = 'UPDATE csvs SET status = ?, modified = NOW() WHERE csv_id = ?';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([0, $csv_id]);
  }
  try{
    $dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh -> beginTransaction();
    // **追加売上のサイネージ非表示に**
    $sql = 'UPDATE banquet_schedules SET enable = 0 WHERE date >= ? AND date <= ? AND `additional_sales` = 1';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$banq_min_date, $banq_max_date]);
    $dbh -> commit();
  }catch(PDOException $e){
    $dbh -> rollBack();
    echo 'Error: ' . $e->getMessage();
  }
  
}

// **古い CSV ファイルの削除処理**
$keydate = (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s'); // 1日前の日付を取得
$sql = 'SELECT * FROM csvs WHERE status = ? AND csv_kind = ? AND modified < ?';
$stmt = $dbh->prepare($sql);
$stmt->execute([0, 2, $keydate]);
$count = $stmt->rowCount();

if ($count > 0) {
  foreach ($stmt as $value) {
    $csv_id = $value['csv_id'];
    $filename = $value['filename'];
    $file = CSV_DATA_PATH . $filename;
    $n_file = CSV_DATA_PATH . 'utf8_' . $filename;

    if (file_exists($file)) {
        unlink($file);
    }
    if (file_exists($n_file)) {
        unlink($n_file);
    }

    $sql = 'UPDATE csvs SET status = ?, modified = NOW() WHERE csv_id = ?';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([80, $csv_id]);
  }
}
?>