<?php
require_once('../common/conf.php');
define('CSV_DATA_PATH', DATA_DIR . 'csv/');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = 'SELECT * FROM csvs WHERE status = 2 AND csv_kind = 3 ORDER BY csv_id ASC';
$res = $dbh->query($sql);
$count = $res->rowCount();

if ($count > 0) {
    foreach ($res as $value) {
        $csv_id = $value['csv_id'];
        $filename = $value['filename'];

        // **ファイルを読み込む**
        $file = file(CSV_DATA_PATH . $filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $i = 0;
        foreach ($file as $line) {
            if ($i > 0) {  // 1行目（ヘッダー）は無視

                // **エンコーディング変換**
                $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');

                // **デバッグ用：元のデータを表示**
                echo "RAW LINE: " . $line . "\n";

                // **STEP 1: 余計な `""` を `"（シングルクォーテーション）` に変換**
                $line = str_replace('""', '"', $line);

                // **STEP 2: CSV の各カラムが `","` の形式になっているかチェック**
                $line = preg_replace('/",([^"])/', '","$1', $line);
                $line = preg_replace('/([^"])",/', '$1","', $line);

                // **STEP 3: 先頭と末尾の `"` を削除**
                $line = preg_replace('/^"(.*)"$/', '$1', $line);

                // **デバッグ用**
                echo "修正後 LINE: " . $line . "\n";

                // **STEP 4: `str_getcsv()` を適用**
                $data = str_getcsv($line, ",", '"');

                // **デバッグ用：パース後のデータを確認**
                echo "str_getcsv() の出力:\n";
                var_dump($data);

                // **STEP 5: 各データの整形**
                foreach ($data as &$value) {
                    $value = trim($value, '"');  // `"` を削除
                }

                // **STEP 6: カラム数を 23 に統一**
                while (count($data) < 23) {
 #                   $data[] = "";  // 不足カラムを補完
                }

                // **STEP 7: `implode()` でデータを確認**
                echo "修正後データ: " . implode("|", $data) . "\n";

                // **デバッグ用**
                var_dump($data);

                // **カラム数チェック**
                if (count($data) !== 23) {
                    echo "⚠️ カラム数不一致: " . count($data) . " 列 (期待値: 23)\n";
                }
            }
            $i++;
        }
    }
}
?>
