<?php
require_once('../../../common/conf.php');

// 初期年月（今月）を設定
$ym = date('Y-m');

// リクエストで年月が指定されていればそれを使用
if (isset($_REQUEST['ym']) && $_REQUEST['ym'] != '') {
  $ym = $_REQUEST['ym'];
}

// 年月からDateTimeオブジェクトを作成
$dt = DateTime::createFromFormat('Y-m', $ym);

// 指定月の最終日を取得（例：2025-06 → 30）
$last_day = (int)$dt->format('t');

// 年月を「2025年 06月」のような形式に変換
$year_month = $dt->format('Y年 m月');

// データベース接続
$dbh = new PDO(DSN, DB_USER, DB_PASS);

// SQL実行：対象の年月の予約情報を取得（目的IDが3以外）
$sql = "SELECT * FROM `view_daily_subtotal` WHERE `ym` = :ym AND `purpose_id` <> 3";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':ym', $ym, PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 結果行数
$s_count = count($rows);

// 加工後のデータを格納する配列
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
    $short_name = cleanLanternName($reservation_name); // 名称の整形
    $people = $row['people'];
    $banquet_category_id = $row['banquet_category_id'];
    $banquet_category_name = $row['banquet_category_name'];
    $start = $row['start'];
    $end = $row['end'];
    $gross = $row['gross'];
    $ex_ts = isset($row['ex-ts']) ? $row['ex-ts'] : 0; // NULLのときは0にする

   // 日付・時間の安全なフォーマット処理
  $formatted_date = '';
  $dateObj = DateTime::createFromFormat('Y-m-d', $date);
  if ($dateObj !== false) {
    $formatted_date = $dateObj->format('Y/m/d');
  }

  $formatted_start = '';
  $startObj = DateTime::createFromFormat('H:i:s', $start);
  if ($startObj !== false) {
    $formatted_start = $startObj->format('H:i');
  }

  $formatted_end = '';
  $endObj = DateTime::createFromFormat('H:i:s', $end);
  if ($endObj !== false) {
    $formatted_end = $endObj->format('H:i');
  }

    // 出力配列にまとめる
    $sales[] = array(
      'status' => $status,
      'banquet_category_name' => $banquet_category_name,
      'reservation_id' => $reservation_id,
      'branch' => $branch,
      'date' => $formatted_date,
      'floor' => '', // フロアは未使用（空欄）
      'room_name' => $room_name,
      'short_name' => $short_name,
      'people' => $people,
      'start' => $formatted_start,
      'end' => $formatted_end,
      'room_id' => $room_id,
      'banquet_category_id' => $banquet_category_id,
      'ex_ts' => $ex_ts
    );
  }
}

try {
  // 出力ファイル名に年月＋タイムスタンプを付ける
  $filename = 'salescal2-' . $ym . '-' . date('YmdHis') . '.csv';

  // ヘッダー情報：CSV＋Shift_JIS＋ダウンロード指示
  header('Content-Type: text/csv; charset=Shift_JIS');
  header('Content-Disposition: attachment; filename="' . $filename . '"');

  $output = fopen('php://output', 'w');

  // CSVヘッダー行（列名）
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

  // SJISに変換して書き込み
  fputcsv($output, array_map(function ($v) {
    return mb_convert_encoding($v, 'SJIS-win', 'UTF-8');
  }, $header));

  // 各データ行の書き込み
  foreach ($sales as $sale) {
    $encodedRow = array_map(function ($v) {
      return mb_convert_encoding($v, 'SJIS-win', 'UTF-8');
    }, $sale);
    fputcsv($output, $encodedRow);
  }

  fclose($output);
} catch (Exception $e) {
  echo "Error: " . $e->getMessage();
}

// 予約名の整形処理
function cleanLanternName($name) {
  // 不要語句リスト（法人名や接尾辞など）
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

  // 文字列置換
  foreach ($replaceWords as $word) {
    $name = str_replace($word, "", $name);
  }

  // 「第〇回」の除去（数字バリエーション対応）
  $name = preg_replace("/第[0-9０-９一二三四五六七八九十百千万億兆]+回/u", "", $name);

  // 西暦年度や和暦年度の除去
  $name = preg_replace("/[0-9０-９]{4}年度/u", "", $name);
  $name = preg_replace("/(令和|平成|昭和)[0-9０-９一二三四五六七八九十百千万]+年度/u", "", $name);
  $name = preg_replace("/[0-9０-９]{2}年度/u", "", $name);

  // 学校や団体名の短縮
  $name = str_replace("小学校", "小", $name);
  $name = str_replace("中学校", "中", $name);
  $name = str_replace("高等学校", "高", $name);
  $name = str_replace("高等専門学校", "高専", $name);
  $name = str_replace("大学校", "大", $name);
  $name = str_replace("専門学校", "専", $name);
  $name = str_replace("短期大学", "短", $name);
  $name = str_replace("労働組合連合会", "労連", $name);
  $name = str_replace("労働組合", "労組", $name);
  $name = str_replace("協同組合連合会", "協連", $name);
  $name = str_replace("協同組合", "協組", $name);
  $name = str_replace("連合会", "連", $name);
  $name = str_replace("倫理法人会", "倫理", $name);

  // 先頭のスペース除去（全角・半角）
  $name = preg_replace("/^[ 　]+/u", "", $name);

  // 最初のスペースで分割し、前半だけ使用
  $parts = preg_split("/[ 　]/u", $name, 2);
  $name = $parts[0];

  // 最大10文字に切り詰めて返す
  return mb_substr($name, 0, 10);
}
?>
