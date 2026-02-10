<?php
require_once('../../../common/conf.php');

// CORSヘッダーを設定
header("Access-Control-Allow-Origin: *"); // すべてのオリジンを許可
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // 許可するHTTPメソッド
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // 許可するリクエストヘッダー
header("Content-Type: application/json; charset=UTF-8"); // JSONのコンテンツタイプを設定

// プリフライトリクエスト(OPTIONS)への対応
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit;
}

if($_REQUEST['ym']){
  $ym = $_REQUEST['ym'];
} else {
  $ym = date('Y-m');
}

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$last_day = date('t', strtotime($ym));
$year_month = date('Y年 m月', strtotime($ym));

$sql = "SELECT * FROM `view_daily_subtotal` where `ym` = :ym";
$stmt = $dbh->prepare($sql); 
$stmt->bindValue(':ym', $ym, PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$s_count = count($rows);

$sales = array();
$total_enkai = 0;
$total_kaigi = 0;
$total_shokuji = 0;
$total_others = 0;
$total = 0;

if($s_count > 0) {
  foreach($rows as $row) {
    $room_id = $row['room_id'];
    $date = $row['date'];
    $reservation_name = cleanLanternName($row['reservation_name']);
    $people = $row['people'];
    $banquet_category_id = $row['banquet_category_id'];
    $start= $row['start'];
    $end = $row['end'];
    $gross = $row['gross'];
    $ex_ts = $row['ex-ts'];
    $sales[] = array(
      'room_id' => $room_id,
      'date' => $date,
      'reservation_name' => $reservation_name,
      'banquet_category_id' => $banquet_category_id,
      'people' => $people,
      'start' => $start,
      'end' => $end,
      'gross' => $gross,
      'ex_ts' => $ex_ts
    );
    $total += $ex_ts;
    if($banquet_category_id == 1) {
      $total_kaigi += $ex_ts;
    } elseif($banquet_category_id == 2) {
      $total_enkai += $ex_ts;
    } elseif($banquet_category_id == 3) {
      $total_shokuji += $ex_ts;
    } elseif($banquet_category_id == 9) {
      $total_others += $ex_ts;
    }
  }
}

$rooms = array();
$sql = "SELECT * FROM `banquet_rooms` WHERE `cal` = 1 ORDER BY `order` DESC, `banquet_room_id` ASC";
$stmt = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$count = count($stmt);
if($count == 0) {
  echo "会場が登録されていません。<br>";
  exit;
}

foreach($stmt as $row) {
  $room_id = $row['banquet_room_id'];
  $room_name = $row['name'];
  $floor = $row['floor'];

  $rooms[]= array(
    'room_id' => $room_id,
    'room_name' => $room_name,
    'floor' => $floor,
  );
}

$data =array(
  'status'=>200,
  'message'=>'OK',
  'ym' => $ym,
  'year_month' => $year_month,
  'last_day' => $last_day,
  'sales' => $sales,
  'rooms' => $rooms,
  'total_enkai' => $total_enkai,
  'total_kaigi' => $total_kaigi,
  'total_shokuji' => $total_shokuji,
  'total_others' => $total_others,
  'total' => $total
);

$jsonstr=json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo $jsonstr;

function cleanLanternName($name) {
  // 不要な法人名接尾辞のリスト
  $replaceWords = [
      "株式会社", "㈱", "（株）", "(株)",
      "一般社団法人", "(一社)", "（一社）",
      "公益社団法人", "(公社)", "（公社）",
      "有限会社", "㈲", "（有）", "(有)",
      "一般財団法人", "(一財)", "（一財）",
      "公益財団法人", "(公財)", "（公財）",
      "財団法人", "(財)", "（財）",
      "合同会社", "(同)", "（同）",
      "宗教法人", "(宗)", "（宗）",
      "社会福祉法人", "(社福)", "（社福）","(社)", "（社）",
      "(下見)","（下見）","【下見】", "下見", "下見 ", "下見　"
  ];

  // 不要語句の削除
  foreach ($replaceWords as $word) {
      $name = str_replace($word, "", $name);
  }

  // 「第〇回」の削除（半角数字／全角数字／漢数字に対応）
  $name = preg_replace("/第[0-9０-９一二三四五六七八九十百千万億兆]+回/u", "", $name);

  // 西暦年度（例: 2025年度）の削除
  $name = preg_replace("/[0-9０-９]{4}年度/u", "", $name);

  // 和暦年度（例: 令和7年度、平成31年度）の削除
  $name = preg_replace("/(令和|平成|昭和)[0-9０-９一二三四五六七八九十百千万]+年度/u", "", $name);

  // 先頭の半角・全角スペースを削除
  $name = preg_replace("/^[ 　]+/u", "", $name);

  // 最初に出てくるスペース（半角・全角）で前半だけに分ける
  $parts = preg_split("/[ 　]/u", $name, 2);  // 2つに分割（前後）
  $name = $parts[0];  // 前半部分だけ使用

  // 最初の10文字に切り詰め
  $name = mb_substr($name, 0, 10);

  return $name;
}
?>