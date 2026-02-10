<?php
// ▼ 開発中のエラー出力を有効にする（本番環境では無効化すること）
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

// ▼ 設定ファイルの読み込み（DSN、DB_USER、DB_PASSなど）
require_once('../../../common/conf.php');

// ▼ ym（年-月）を取得（未指定の場合は今月）
$ym = date('Y-m');
if (isset($_REQUEST['ym']) && $_REQUEST['ym'] != '') {
  $ym = $_REQUEST['ym'];
}

// ▼ ymからDateTimeオブジェクトを作成
$dt = DateTime::createFromFormat('Y-m', $ym);

// ▼ 月初日
$first_day = $dt->format('Y-m-01');

// ▼ 今月（"YYYY-MM" 形式）
$this_month = $dt->format('Y-m');

// ▼ 前月と翌月
$before_dt = clone $dt;
$before_dt->modify('-1 month');
$before_month = $before_dt->format('Y-m');

$after_dt = clone $dt;
$after_dt->modify('+1 month');
$after_month = $after_dt->format('Y-m');

// ▼ 不備データを格納する配列
$defects = array();

// ▼ DB接続
$dbh = new PDO(DSN, DB_USER, DB_PASS);

// ===== 1. 営業担当者が記入されていない予約 =====
$error_name = "営業担当者未記入";
$sql = "SELECT * FROM `banquet_schedules`
        WHERE `date` >= :first_day
        AND `status` IN (1,2,3)
        AND `pic` = ''
        ORDER BY `date`, `reservation_id`, `branch`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ▼ 不備情報を配列に追加
foreach ($results as $row) {
  $defects[] = formatDefectRow($row, $error_name);
}

// ===== 2. 決定・仮予約で目的が不正 =====
$error_name = "決定・仮で目的が不正";
$sql = "SELECT * FROM `banquet_schedules`
        WHERE `date` >= :first_day
        AND `status` IN (1,2)
        AND `purpose_id` IN (0,3,88,93,94)
        ORDER BY `date`, `reservation_id`, `branch`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
  $defects[] = formatDefectRow($row, $error_name);
}

// ===== 3. 営業押さえで目的が不正 =====
$error_name = "営業押さえで目的が不正";
$sql = "SELECT * FROM `banquet_schedules`
        WHERE `date` >= :first_day
        AND `status` = 3
        AND `purpose_id` NOT IN (0,3,88,93,94)
        ORDER BY `date`, `reservation_id`, `branch`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
  $defects[] = formatDefectRow($row, $error_name);
}

// ===== 4. 決定・仮予約で明細データがない予約 =====
$error_name = "決定・仮で明細なし";
$sql = "SELECT * FROM `view_daily_subtotal`
        WHERE `status` IN (1,2)
        AND `date` >= :first_day
        AND `gross` IS NULL
        ORDER BY `date`, `reservation_id`, `branch`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
  $defects[] = formatDefectRow($row, $error_name, true); // 第3引数でサブトータル用と指定
}

// ===== CSV出力処理 =====
try {
  $filename = 'defact-' . $ym . '-' . date('YmdHis') . '.csv';
  header('Content-Type: text/csv; charset=Shift_JIS');
  header('Content-Disposition: attachment; filename="' . $filename . '"');

  $output = fopen('php://output', 'w');

  // ▼ ヘッダ行
  $header = array(
    'エラー種類',
    '予約状況コード',
    '予約状況名称',
    '会場使用日',
    '予約番号',
    '会場枝番',
    '予約名称',
    '使用目的コード',
    '使用目的名称',
    '会場コード',
    '会場名称',
    '担当者'
  );
  fputcsv($output, array_map(fn($v) => mb_convert_encoding($v, 'SJIS-win', 'UTF-8'), $header));

  // ▼ 各データ行
  foreach ($defects as $defect) {
    fputcsv($output, array_map(fn($v) => mb_convert_encoding($v, 'SJIS-win', 'UTF-8'), $defect));
  }

  fclose($output);
} catch (Exception $e) {
  echo "エラー: " . $e->getMessage();
}

// ===== 不備情報の共通整形関数 =====
function formatDefectRow($row, $error_name, $isSubtotal = false) {
  return array(
    'error_name' => $error_name,
    'status' => $row['status'],
    'status_name' => $row['status_name'],
    'res_date' => $row['date'],
    'reservation_id' => $row['reservation_id'],
    'branch' => $row['branch'],
    'reservation_name' => cleanLanternName($row['reservation_name']),
    'purpose_id' => $row['purpose_id'],
    'purpose_name' => $isSubtotal ? $row['purpose_short'] : $row['purpose_name'],
    'room_id' => $row['room_id'],
    'room_name' => $row['room_name'],
    'pic' => cleanLanternName($row['pic']),
  );
}

// ===== 予約名称・担当者名の整形関数（既存のまま） =====
function cleanLanternName($name) {
  $replaceWords = [
    "株式会社", "㈱", "（株）", "(株)", "一般社団法人", "(一社)", "（一社）", "公益社団法人", "(公社)", "（公社）",
    "有限会社", "㈲", "（有）", "(有)", "一般財団法人", "(一財)", "（一財）", "公益財団法人", "(公財)", "（公財）",
    "財団法人", "(財)", "（財）", "合同会社", "(同)", "（同）", "学校法人", "(学)", "（学）", "医療法人", "(医)", "（医）",
    "社会福祉法人", "(社)", "（社）", "(社福)", "（社福）", "宗教法人", "(宗)", "（宗）", "特定非営利活動法人", "(特)", "（特）",
    "医療法人社団", "(医社)", "（医社）", "医療法人財団", "(医財)", "（医財）", "(下見)", "（下見）", "【下見】", "下見", "下見 ", "下見　"
  ];
  foreach ($replaceWords as $word) {
    $name = str_replace($word, "", $name);
  }

  $name = preg_replace("/第[0-9０-９一二三四五六七八九十百千万億兆]+回/u", "", $name);
  $name = preg_replace("/[0-9０-９]{4}年度/u", "", $name);
  $name = preg_replace("/(令和|平成|昭和)[0-9０-９一二三四五六七八九十百千万]+年度/u", "", $name);
  $name = preg_replace("/[0-9０-９]{2}年度/u", "", $name);

  $replacePairs = [
    "小学校" => "小", "中学校" => "中", "高等学校" => "高", "高等専門学校" => "高専", "大学校" => "大", "専門学校" => "専",
    "短期大学" => "短", "労働組合連合会" => "労連", "労働組合" => "労組", "協同組合連合会" => "協連", "協同組合" => "協組",
    "連合会" => "連", "倫理法人会" => "倫理"
  ];
  foreach ($replacePairs as $k => $v) {
    $name = str_replace($k, $v, $name);
  }

  $name = preg_replace("/^[ 　]+/u", "", $name); // 先頭の空白除去
  $parts = preg_split("/[ 　]/u", $name, 2);
  $name = $parts[0] ?? $name;
  return mb_substr($name, 0, 10); // 最初の10文字まで
}
?>
