<?php
require_once('../../../common/conf.php');

$ym = date('Y-m');
if(isset($_REQUEST['ym']) && $_REQUEST['ym'] != '') {
  $ym = $_REQUEST['ym'];
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
if($s_count > 0) {
  foreach($rows as $row) {
    $room_id = $row['room_id'];
    $room_name = $row['room_name'];
    $date = $row['date'];
    $reservation_id = $row['reservation_id'];
    $reservation_name = $row['reservation_name'];
    $branch = $row['branch'];
    $short_name = cleanLanternName($reservation_name);
    $people = $row['people'];
    $banquet_category_id = $row['banquet_category_id'];
    $banquet_category_name = $row['banquet_category_name'];
    $start= $row['start'];
    $end = $row['end'];
    $gross = $row['gross'];
    $ex_ts = $row['ex-ts'];
    if(!$ex_ts) {
      $ex_ts = 0;
    }
    $sales[] = array(
      'status'=>'',
      'banquet_category_name' => $banquet_category_name,
      'reservation_id' => $reservation_id,
      'branch' => $branch,
      'date' => date('Y/m/d',strtotime($date)),
      'floor' => '',
      'room_name' => $room_name,
      'short_name' => $short_name,
      'people' => $people,
      'start' => date('H:i',strtotime($start)),
      'end' => date('H:i',strtotime($end)),
      'room_id' => $room_id,
      'banquet_category_id' => $banquet_category_id,
      'ex_ts' => $ex_ts
    );
  }
}

try {
  $filename = 'salescal2-'.$ym.'-'.date('YmdHis').'.csv';
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  $output = fopen('php://output', 'w');
  // BOMを出力
  fwrite($output, "\xEF\xBB\xBF");
  // CSVのヘッダ行
  fputcsv($output, array(
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
  ));
  foreach($sales as $sale) {
    fputcsv($output, $sale);
  }


  fclose($output);
} catch (Exception $e) {
  echo "Error: " . $e->getMessage();
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
      "財団法人", "(財)", "（財）",
      "合同会社", "(同)", "（同）",
      "学校法人", "(学)", "（学）",
      "医療法人", "(医)", "（医）",
      "社会福祉法人", "(社)", "（社）",
      "宗教法人", "(宗)", "（宗）",
      "特定非営利活動法人", "(特)", "（特）",
      "医療法人社団", "(医社)", "（医社）",
      "医療法人財団", "(医財)", "（医財）",
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