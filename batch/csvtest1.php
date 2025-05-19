<?php
require_once('../common/conf.php');
define('CSV_DATA_PATH', DATA_DIR.'csv/');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = 'SELECT * FROM csvs WHERE status = 2 AND csv_kind = 2 ORDER BY csv_id ASC';
$res = $dbh->query($sql);
$count = $res->rowCount();

if ($count > 0) {
  foreach ($res as $value) {
    $csv_id = $value['csv_id'];
    $filename = $value['filename'];

    // ファイルを読み込む
    $file = file(CSV_DATA_PATH . $filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $i=0;
    foreach ($file as $line) {
      if ($i > 0) {
        // **エンコーディング変換を `str_getcsv()` の前に適用**
        $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');

        // **改行コードを統一**
        $line = str_replace(["\r\n", "\r"], "\n", $line);

        // **デバッグ用：元のデータを表示**
        echo "RAW LINE: " . $line . "\n";

        // **str_getcsv() を適用**
        $data = str_getcsv($line, ",");

        // **不要な `"` を削除**
        #foreach ($data as &$value) {
        #  $value = trim($value, '"');
        #}

        // **カラム数が23未満の場合、手動で修正**
        while (count($data) < 24) {
          foreach ($data as $index => &$field) {
            if (strpos($field, '",') !== false) {
              $split = explode('",', $field, 2);
              $data[$index] = trim($split[0], '"'); // `"二次会"`
              array_splice($data, $index + 1, 0, trim($split[1], '"')); // `99`
              break; // 修正したらループを抜ける
            }
          }

          // **カラム数が23になったら修正を終了**
          if (count($data) == 24) {
            break;
         }

          // **カラム数が足りない場合は、空カラム (`""`) を追加**
          $data[] = "";
        }

        // **不要なスペースを削除**
        foreach ($data as $key => $value) {
          if (trim($data[$key]) === '') {
            $data[$key] = '';
          }
        }

        // **カラム数チェック**
        if (count($data) !== 24) {
            echo "⚠️ カラム数不一致: " . count($data) . " 列 (期待値: 24)\n";
            echo "修正後データ: " . implode("|", $data) . "\n";
        }

        var_dump($data);
      }
      $i++;
    }
  }
}
?>
