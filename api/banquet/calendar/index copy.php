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
$events = array();

if($_REQUEST['date']){
  $vdate = $_REQUEST['date'];
} else {
  $vdate = date('Y-m-d');
}
$weeks = $_REQUEST['weeks'] ?? 3; // デフォルトは3週分
$add_days = $weeks * 7 -1;

$start_date =$vdate;
$w = date('w', strtotime($vdate));
if($w == 2) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
} elseif($w == 3) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -2 day'));
} elseif($w == 4) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -3 day'));
} elseif($w == 5) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -4 day'));
} elseif($w == 6) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -5 day'));
} elseif($w == 0) {
  $start_date = date('Y-m-d', strtotime($start_date . ' -6 day'));
}
$end_date = date('Y-m-d', strtotime($start_date . ' +'.$add_days.' day'));

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = "SELECT
        `reservation_id`,
        `reservation_name`,
        `date`,
        min(`start`) as `start`,
        max(`end`) as `end`,
        count(`reservation_id`) as `count`,
        `pic`
        FROM `banquet_schedules`
        WHERE `date` BETWEEN :start_date AND :end_date
        AND `status` <> 5
        AND `reservation_name` not like '朝食会場'
        AND `reservation_name` not like '倉庫'
        GROUP BY `reservation_id`, `date`
        ORDER BY `date`, `start`, `end`, `reservation_id`";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':start_date', $start_date, PDO::PARAM_STR);
$stmt->bindValue(':end_date', $end_date, PDO::PARAM_STR); 
$stmt->execute();
$values = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
$stmt->closeCursor();

$today_count =0;
foreach($values as $row) {
  $rn = $row['reservation_name'];
  $purpose = "";
  if (strpos($rn, '下見') !== false) {
    $purpose = '下見';
  }
  if($row['date'] == $vdate){
    $today_count += $row['count'];
  }
  $events[] = array(
    'date' => $row['date'],
    'title' => cleanLanternName($rn),
    'start' => $row['start'],
    'end' => $row['end'],
    'count' => $row['count'],
    'pic' => $row['pic'],
    'purpose' => $purpose
  );
}

$data=array(
  'status'=>200,
  'message'=>'OK',
  'date'=> date('Y-m-d H:i:s'),
  'day'=> date('Y-m-d'),
  'today_count'=> $today_count,
  'events'=> $events
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
      "学校法人", "(学)", "（学）",
      "医療法人", "(医)", "（医）",
      "財団法人", "(財)", "（財）",
      "合同会社", "(同)", "（同）",
      "(下見)","（下見）","【下見】","[下見]","〔下見〕","［下見］", "下見", "下見 ", "下見　"
  ];

  // 不要語句の削除
  foreach ($replaceWords as $word) {
      $name = str_replace($word, "", $name);
  }
  $name = str_replace("労働組合連合会", "労連", $name);
  $name = str_replace("労働組合", "労組", $name);

  //【かっこで囲まれた文字列】の削除
  $name = preg_replace("/【[^】]*】/u", "", $name); // 【】の中身を削除

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