<?php
require_once('../../../common/conf.php');
/*
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
    */
$events = array();

if($_REQUEST['date']){
  $vdate = $_REQUEST['date'];
} else {
  $vdate = date('Y-m-d');
}
if($_REQUEST['weeks']){
  $weeks = $_REQUEST['weeks'];
} else {
  $weeks = 3; // デフォルトは3週分
}
if($_REQUEST['pic']){
  $pic = $_REQUEST['pic'];
} else {
  $pic = "伊藤　良行"; // デフォルトは伊藤さん
}

$add_days = $weeks * 7 -1;
$start_date =$vdate;
$end_date = date('Y-m-d', strtotime($start_date . ' +'.$add_days.' day'));

$sql="SELECT * FROM `view_banquet_pic` WHERE `pic` LIKE ? AND `date` BETWEEN ? AND ? ORDER BY `date`, `reservation_id`;";

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$stmt = $dbh->prepare($sql);
$stmt->execute([$pic, $start_date, $end_date]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if( sizeof($res) > 0 ){
  foreach ($res as $event) {
    $reservation_id = $event['reservation_id'];
    $reservation_name = cleanLanternName($event['reservation_name']);
    $date = $event['date'];
    $people = $event['people'];
    $rooms = $event['rooms'];
    $pic = $event['pic'];
    $status = $event['status'];
    $status_name = $event['status_name'];
    $events[] = array(
      'reservation_id' => $reservation_id,
      'reservation_name' => $reservation_name,
      'date' => $date,
      'people' => $people,
      'rooms' => $rooms,
      'pic' => $pic,
      'status' => $status,
      'status_name' => $status_name
    );  
    
  }
  var_dump($events);
}

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
      "(下見)","（下見）","【下見】", "下見", "下見 ", "下見　"
  ];

  // 不要語句の削除
  foreach ($replaceWords as $word) {
      $name = str_replace($word, "", $name);
  }
  $name = str_replace("労働組合連合会", "労連", $name);
  $name = str_replace("労働組合", "労組", $name);

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