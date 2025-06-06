<?php
// ▼ デバッグ用エラー表示設定（開発中のみ使用） ▼
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

require_once('../../../common/conf.php');

// 初期値として今月（YYYY-MM）を設定
$ym = date('Y-m');
if (isset($_REQUEST['ym']) && $_REQUEST['ym'] != '') {
  $ym = $_REQUEST['ym'];
}

$dbh = new PDO(DSN, DB_USER, DB_PASS);

// DateTimeを使って、対象年月の月末日を取得
$dt = DateTime::createFromFormat('Y-m', $ym);
$last_day = $dt->format('t');  // 月の最終日
$year_month = $dt->format('Y年 m月');  // 表示用年月フォーマット

// データ取得SQL（目的IDが3以外を対象）
$sql = "SELECT * FROM `view_daily_subtotal` WHERE `ym` = :ym AND `purpose_id` <> 3";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':ym', $ym, PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$s_count = count($rows);

$sales = array();

if ($s_count > 0) {
  foreach ($rows as $row) {
    $status = $row['status_name'];
    $room_id = $row['room_id'];
    $room_name = $row['room_name'];
    $date = $row['date'];
    $reservation_id = $row['reservation_id'];
    $reservation_name = $row['reservation_name'];
    $branch = $row['branch'];
    $short_name = cleanLanternName($reservation_name);  // 予約名称の整形
    $people = $row['people'];
    $banquet_category_id = $row['banquet_category_id'];
    $banquet_category_name = $row['banquet_category_name'];

    // 日付と時間をDateTimeで整形
    $formatted_date = DateTime::createFromFormat('Y-m-d', $date)->format('Y/m/d');
    $start = DateTime::createFromFormat('Y-m-d H:i:s', $row['start'])->format('H:i');
    $end = DateTime::createFromFormat('Y-m-d H:i:s', $row['end'])->format('H:i');

    $gross = $row['gross'];
    $ex_ts = $row['ex-ts'] ?: 0;

    $sales[] = array(
      'status' => $status,
      'banquet_category_name' => $banquet_category_name,
      'reservation_id' => $reservation_id,
      'branch' => $branch,
      'date' => $formatted_date,
      'floor' => '',
      'room_name' => $room_name,
      'short_name' => $short_name,
      'people' => $people,
      'start' => $start,
      'end' => $end,
      'room_id' => $room_id,
      'banquet_category_id' => $banquet_category_id,
      'ex_ts' => $ex_ts
    );
  }
}

try {
  // ファイル名に現在の日時を付けて生成
  $filename = 'salescal2-' . $ym . '-' . (new DateTime())->format('YmdHis') . '.csv';

  header('Content-Type: text/csv; charset=Shift_JIS');
  header('Content-Disposition: attachment; filename="' . $filename . '"');

  $output = fopen('php://output', 'w');

  // ヘッダ行
  $header = array(
    '予約状態名称',
    '分類',
    '予約番号',
    '会場枝番',
    '会場使用日',
    'フロア',
    '会場名称',
    '予約名称',
    '会場予約人数',
    '宴会開始時間',
    '宴会終了時間',
    '会場コード',
    '分類コード',
    '金額'
  );
  // 文字コードをSJISに変換して出力
  fputcsv($output, array_map(function($v) {
    return mb_convert_encoding($v, 'SJIS-win', 'UTF-8');
  }, $header));

  // データ行を出力
  foreach ($sales as $sale) {
    $encodedRow = array_map(function($v) {
      return mb_convert_encoding($v, 'SJIS-win', 'UTF-8');
    }, $sale);
    fputcsv($output, $encodedRow);
  }

  fclose($output);
} catch (Exception $e) {
  echo "Error: " . $e->getMessage();
}

// 不要な文字列を取り除く予約名称整形関数
function cleanLanternName($name) {
  $replaceWords = [
    "株式会社", "㈱", "（株）", "(株)",
    "一般社団法人", "(一社)", "（一社）",
    "公益社団法人", "(公社)", "（公社）",
    "有限会社", "㈲", "（有）", "(有)",
    "一般財団法人", "(一財)", "（一財）",
    "公益財団法人", "(公財)", "（公財）",
    "財団法人", "(財)", "（財）",
    "合同会社", "(同)", "（同）",
    "学校法人", "(学)", "（学）",
    "医療法人", "(医)", "（医）",
    "社会福祉法人", "(社)", "（社）",
    "宗教法人", "(宗)", "（宗）",
    "特定非営利活動法人", "(特)", "（特）",
    "医療法人社団", "(医社)", "（医社）",
    "医療法人財団", "(医財)", "（医財）",
    "(下見)", "（下見）", "【下見】", "下見", "下見 ", "下見　"
  ];

  foreach ($replaceWords as $word) {
    $name = str_replace($word, "", $name);
  }

  // 「第〇回」削除（多様な数字に対応）
  $name = preg_replace("/第[0-9０-９一二三四五六七八九十百千万億兆]+回/u", "", $name);
  // 西暦年度・和暦年度の削除
  $name = preg_replace("/[0-9０-９]{4}年度/u", "", $name);
  $name = preg_replace("/(令和|平成|昭和)[0-9０-９一二三四五六七八九十百千万]+年度/u", "", $name);
  $name = preg_replace("/[0-9０-９]{2}年度/u", "", $name);

  // 学校や団体名略称化
  $name = str_replace("小学校", "小", $name);
  $name = str_replace("中学校", "中", $name);
  $name = str_replace("高等学校", "高", $name);
  $name = str_replace("高等専門学校", "高専", $name);
  $name = str_replace("大学校", "大", $name);
  $name = str_replace("専門学校", "専", $name);
  $name = str_replace("短期大学", "短", $name);
  $name = str_replace("労働組合連合会", "労連", $name);
  $name = str_replace("労働組合", "労組", $name);
  $name = str_replace("協同組合", "協組", $name);
  $name = str_replace("協同組合連合会", "協連", $name);
  $name = str_replace("連合会", "連", $name);
  $name = str_replace("倫理法人会", "倫理", $name);

  // 先頭スペース除去（全角・半角対応）
  $name = preg_replace("/^[ 　]+/u", "", $name);

  // 最初のスペースで分割して先頭のみ取得
  $parts = preg_split("/[ 　]/u", $name, 2);
  $name = $parts[0];

  // 10文字以内に切り詰め
  $name = mb_substr($name, 0, 10);

  return $name;
}
?>
