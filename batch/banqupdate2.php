
<?php
// ▼ 設定ファイルの読み込み
require_once('../common/conf.php');

// ▼ CSVデータ保存先が未定義の場合に定義
if (!defined('CSV_DATA_PATH')) {
  define('CSV_DATA_PATH', '../data/csv/');
}

// ▼ データベース接続確認（未接続なら接続）
if (!isset($dbh)) {
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
}

// ▼ 処理対象のCSVファイルを取得（status = 2かつcsv_kind = 2）
$sql = 'SELECT * FROM csvs WHERE status = 2 AND csv_kind = 2 ORDER BY csv_id ASC';
$res = $dbh->query($sql);
$count = $res->rowCount();

if ($count > 0) {
  foreach ($res as $value) {
    $csv_id = $value['csv_id'];
    $filename = $value['filename'];
    $file_path = CSV_DATA_PATH . $filename;

    // ▼ CSVステータスを「処理中（1）」に更新
    $sql = 'UPDATE csvs SET status = 1, modified = NOW() WHERE csv_id = ?';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$csv_id]);

    if (($handle = fopen($file_path, "r")) !== false) {

      // ▼ ヘッダー定義と比較
      $expected_header = [
        '予約番号','予約人数','予約件数','予約宴会名称','会場枝番','会場名称','会場使用日','宴会開始時間','宴会終了時間',
        '行灯名称','会場予約人数','営業担当名称','予約状態ｺｰﾄﾞ','予約状態名称','会場ｺｰﾄﾞ','会場使用目的ｺｰﾄﾞ',
        '会場使用目的名称','会場形式ｺｰﾄﾞ','会場形式名称','ｴｰｼﾞｪﾝﾄｺｰﾄﾞ','エージェン 名称','申込会社 名称','ｴｰｼﾞｪﾝﾄ名称',
        '予約状態備考','売上部門ｺｰﾄﾞ','売上部門名称','実施日','営業担当ｺｰﾄﾞ','追加売上区分','追加売上区分名称'
      ];
      $first_line = mb_convert_encoding(fgets($handle), 'UTF-8', 'SJIS-win');
      $first_line = str_replace(["\r\n", "\r"], "\n", $first_line);
      $header = str_getcsv(trim($first_line), ",");

      if ($header !== $expected_header) {
        fclose($handle);
        $stmt = $dbh->prepare('UPDATE csvs SET status = ?, modified = NOW() WHERE csv_id = ?');
        $stmt->execute([90, $csv_id]);
        continue;
      }

      $banq_min_date = "";
      $banq_max_date = "";
      $banq_modtime = date("Y-m-d H:i:s");
      $data_rows = [];

      while (($line = fgets($handle)) !== false) {
        $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');
        $line = str_replace(["\r\n", "\r"], "\n", $line);
        $data = str_getcsv(trim($line), ",");

        if (count($data) < 29) continue;
        $data_rows[] = $data;

        $date = $data[6];
        if ($banq_min_date === "" || $date < $banq_min_date) $banq_min_date = $date;
        if ($banq_max_date === "" || $date > $banq_max_date) $banq_max_date = $date;
      }
      fclose($handle);

      $dbh->beginTransaction();

      $stmt = $dbh->prepare('SELECT reservation_id, branch FROM banquet_schedules WHERE date BETWEEN ? AND ?');
      $stmt->execute([$banq_min_date, $banq_max_date]);
      $existing_keys = [];
      foreach ($stmt as $row) {
        $existing_keys[$row['reservation_id'] . '-' . $row['branch']] = true;
      }

      $update_stmt = $dbh->prepare('UPDATE banquet_schedules SET 
        people=?, reservation_name=?, date=?, start=?, end=?,
        room_id=?, room_name=?, event_name=?, status=?, status_name=?,
        purpose_id=?, purpose_name=?, layout_id=?, layout_name=?, pic=?,
        agent_id=?, agent_name=?, reserver=?, memo=?, sales_dept_id=?,
        sales_dept_name=?, reservation_date=?, pic_id=?, additional_sales=?,
        modified=now(), modified_by="csvdata"
        WHERE reservation_id=? AND branch=?');

      $insert_stmt = $dbh->prepare('INSERT INTO banquet_schedules (
        reservation_id, people, branch, reservation_name, date, start, end,
        room_id, room_name, event_name, status, status_name, purpose_id, purpose_name,
        layout_id, layout_name, pic, agent_id, agent_name, reserver, memo,
        sales_dept_id, sales_dept_name, reservation_date, pic_id, additional_sales,
        enable, added, modified, modified_by
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now(), ?)');

      foreach ($data_rows as $data) {
        $reservation_id = intval($data[0]);
        $people = intval($data[1]);
        $reservation_name = mb_convert_kana($data[3], "KVas");
        $branch = intval($data[4]);
        $room_name = $data[5];
        $date = $data[6];
        $start = $data[6] . " " . $data[7];
        $end = $data[6] . " " . $data[8];
        $event_name = mb_convert_kana($data[9], "KVas");
        $pic = $data[11];
        $status_id = intval($data[12]);
        $status_name = mb_convert_kana($data[13], "KVas");
        $room_id = intval($data[14]);
        $purpose_id = intval($data[15]);
        $purpose_name = mb_convert_kana($data[16], "KVas");
        $layout_id = intval($data[17]);
        $layout_name = mb_convert_kana($data[18], "KVas");
        $agent_id = intval($data[19]);
        $agent_name = mb_convert_kana($data[20], "KVas");
        $reserver = mb_convert_kana($data[21], "KVas");
        $agent_group = mb_convert_kana($data[22], "KVas");
        $memo = $data[23] ? mb_convert_kana($data[23], "KVas") : "";
        $sales_dept_id = intval($data[24]);
        $sales_dept_name = mb_convert_kana($data[25], "KVas");
        $reservation_date = (new DateTime($data[26]))->format('Y-m-d');
        $pic_id = $data[27];
        $additional_sales = in_array(strtolower($data[28]), ['n','0','false']) ? 0 : 1;

        $key = $reservation_id . '-' . $branch;

        if (isset($existing_keys[$key])) {
          $update_stmt->execute([
            $people, $reservation_name, $date, $start, $end,
            $room_id, $room_name, $event_name, $status_id, $status_name,
            $purpose_id, $purpose_name, $layout_id, $layout_name, $pic,
            $agent_id, $agent_name, $reserver, $memo, $sales_dept_id,
            $sales_dept_name, $reservation_date, $pic_id, $additional_sales,
            $reservation_id, $branch
          ]);
        } else {
          $enable = ($status_id == 1 && $layout_id != 20 && $event_name != '朝食会場' && $additional_sales == 0) ? 1 : 0;
          $insert_stmt->execute([
            $reservation_id, $people, $branch, $reservation_name, $date, $start, $end,
            $room_id, $room_name, $event_name, $status_id, $status_name, $purpose_id, $purpose_name,
            $layout_id, $layout_name, $pic, $agent_id, $agent_name, $reserver, $memo,
            $sales_dept_id, $sales_dept_name, $reservation_date, $pic_id, $additional_sales,
            $enable, 'csvdata'
          ]);
        }
      }

      $dbh->commit();

      // ▼ キャンセル処理
      $stmt = $dbh->prepare('SELECT * FROM banquet_schedules WHERE date >= ? AND date <= ? AND modified < ?');
      $stmt->execute([$banq_min_date, $banq_max_date, $banq_modtime]);

      $cancel_stmt = $dbh->prepare('UPDATE banquet_schedules SET status = 5, status_name = "キャンセル", enable = 0, modified = now(), modified_by = "csvdata" WHERE banquet_schedule_id = ?');

      foreach ($stmt as $row) {
        if ($row['status'] != 5) {
          $cancel_stmt->execute([$row['banquet_schedule_id']]);
        }
      }

      // ▼ 処理完了フラグ
      $stmt = $dbh->prepare('UPDATE csvs SET status = ?, modified = NOW() WHERE csv_id = ?');
      $stmt->execute([0, $csv_id]);
    }
  }

  // ▼ 追加売上非表示
  $stmt = $dbh->prepare('UPDATE banquet_schedules SET enable = 0 WHERE date >= ? AND date <= ? AND additional_sales = 1');
  $stmt->execute([$banq_min_date, $banq_max_date]);
}

// ▼ 古いファイル削除処理
$keydate = (new DateTime())->modify('-1 day')->format('Y-m-d H:i:s');
$stmt = $dbh->prepare('SELECT * FROM csvs WHERE status = 0 AND csv_kind = 2 AND modified < ?');
$stmt->execute([$keydate]);

foreach ($stmt as $value) {
  $file = CSV_DATA_PATH . $value['filename'];
  $utf_file = CSV_DATA_PATH . 'utf8_' . $value['filename'];
  if (file_exists($file)) unlink($file);
  if (file_exists($utf_file)) unlink($utf_file);

  $stmt_upd = $dbh->prepare('UPDATE csvs SET status = 80, modified = NOW() WHERE csv_id = ?');
  $stmt_upd->execute([$value['csv_id']]);
}
?>
