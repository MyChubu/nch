<?php
require_once('../common/conf.php');
define('CSV_DATA_PATH', DATA_DIR . 'csv/');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = 'SELECT * FROM csvs WHERE status = 2 AND csv_kind = 2 ORDER BY csv_id ASC';
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
            $i = 0;

            while (($line = fgets($handle)) !== false) {
                if ($i > 0) {  // 1行目（ヘッダー）は無視
                    echo $i . "行目\n";
                    // **エンコーディング変換**
                    $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');

                    // **デバッグ用：元のデータを表示**
                    #echo "RAW LINE: " . $line . "\n";

                    // **STEP 1: 余計な `""` を `"（シングルクォーテーション）` に変換**
                    $line = str_replace('""', '"', $line);

                    // **STEP 2: CSV の各カラムが `","` の形式になっているかチェック**
                    $line = preg_replace('/",([^"])/', '","$1', $line);
                    $line = preg_replace('/([^"])",/', '$1","', $line);

                    // **STEP 3: 先頭と末尾の `"` を削除**
                    $line = preg_replace('/^"(.*)"$/', '$1', $line);

                    // **デバッグ用**
                    #echo "修正後 LINE: " . $line . "\n";

                    // **STEP 4: `str_getcsv()` を適用**
                    $data = str_getcsv($line, ",", '"');

                    // **デバッグ用：パース後のデータを確認**
                    #echo "str_getcsv() の出力:\n";
                    #var_dump($data);

                    // **STEP 5: 各データの整形**
                    foreach ($data as &$value) {
                        $value = trim($value, '"');  // `"` を削除
                    }

                    // **カラム数チェック**
                    if (count($data) !== 23) {
                        echo "⚠️ カラム数不一致: " . count($data) . " 列 (期待値: 23)\n";
                    }

                    var_dump($data);

                    // **データの処理（データベースへの挿入・更新）**
                                
                    $reservation_id=intval($data[0]);
                    $reservation_name=mb_convert_kana($data[3], "KVas");
                    $branch=intval($data[4]);
                    $room_name=$data[5];
                    $date=$data[6];
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
                        }
                      } 
                      
                      try{
                        $dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $dbh -> beginTransaction();
                        $sql = 'insert into banquet_schedules (reservation_id,
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
                          enable,
                          added,
                          modified,
                          modified_by) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,now(),now(),?)';
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
        } else {
            echo "⚠️ ファイルを開けませんでした: " . $file_path . "\n";
        }

        // **処理完了後、CSV のステータスを元に戻す**
        $sql = 'UPDATE csvs SET status = ?, modified = NOW() WHERE csv_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->execute([0, $csv_id]);
    }
}

// **古い CSV ファイルの削除処理**
$keydate = date("Y-m-d H:i:s", strtotime("-1 day"));
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
