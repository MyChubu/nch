<?php
require_once('../../../common/conf.php');

$ym = date('Y-m');
if(isset($_REQUEST['ym']) && $_REQUEST['ym'] != '') {
  $ym = $_REQUEST['ym'];
}


$first_day = date('Y-m-01', strtotime($ym));

$this_month = date('Y-m');

$before_month = date('Y-m', strtotime($first_day . '-1 month'));
$after_month = date('Y-m', strtotime($first_day . '+1 month'));

$defects = array();
$dbh = new PDO(DSN, DB_USER, DB_PASS);

//営業担当者が記入されていない
$error_name = "営業担当者未記入";
$sql = "SELECT * FROM `banquet_schedules`
        WHERE `date` >= :first_day
        AND `status` IN (1,2,3)
        AND `pic` = ''  ORDER BY `date`,`reservation_id`,`branch`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
if($count > 0) {
  foreach($results as $row) {
    $sche_id = $row['banquet_schedule_id'];
    $status = $row['status'];
    $status_name = $row['status_name'];
    $res_date = $row['date'];
    $reservation_id = $row['reservation_id'];
    $reservation_name = $row['reservation_name'];
    $reservation_name = cleanLanternName($reservation_name);
    $branch = $row['branch'];
    $purpose_id = $row['purpose_id'];
    $purpose_name = $row['purpose_name'];
    $room_id = $row['room_id'];
    $room_name = $row['room_name'];
    $pic= $row['pic'];

    $defects[] = array(
      'error_name' => $error_name,
      'status' => $status,
      'status_name' => $status_name,
      'res_date' => $res_date,
      'reservation_id' => $reservation_id,
      'branch' => $branch,
      'reservation_name' => $reservation_name,
      'purpose_id' => $purpose_id,
      'purpose_name' => $purpose_name,
      'room_id' => $room_id,
      'room_name' => $room_name,
      'pic' => $pic
    );
  }
}


//決定予約・仮予約で目的がおかしいもの
$error_name="決定・仮で目的が不正";
$sql = "SELECT * FROM `banquet_schedules`
        WHERE `date` >= :first_day
        AND `status` IN (1,2)
        AND `purpose_id` IN (0,3,88,93,94)  ORDER BY `date`,`reservation_id`,`branch`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
if($count > 0) {
  foreach($results as $row) {
    $sche_id = $row['banquet_schedule_id'];
    $status = $row['status'];
    $status_name = $row['status_name'];
    $res_date = $row['date'];
    $reservation_id = $row['reservation_id'];
    $reservation_name = $row['reservation_name'];
    $reservation_name = cleanLanternName($reservation_name);
    $branch = $row['branch'];
    $purpose_id = $row['purpose_id'];
    $purpose_name = $row['purpose_name'];
    $room_id = $row['room_id'];
    $room_name = $row['room_name'];
    $pic= $row['pic'];
    $pic= cleanLanternName($pic);

    $defects[] = array(
      'error_name' => $error_name,
      'status' => $status,
      'status_name' => $status_name,
      'res_date' => $res_date,
      'reservation_id' => $reservation_id,
      'branch' => $branch,
      'reservation_name' => $reservation_name,
      'purpose_id' => $purpose_id,
      'purpose_name' => $purpose_name,
      'room_id' => $room_id,
      'room_name' => $room_name,
      'pic' => $pic
    );
  }
} 
//営業押さえで目的がおかしいもの
$error_name="営業押さえで目的が不正";
$sql = "SELECT * FROM `banquet_schedules`
        WHERE `date` >= :first_day
        AND `status` IN (3)
        AND `purpose_id` NOT IN (0,3,88,93,94)  ORDER BY `date`,`reservation_id`,`branch`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
if($count > 0) {
  foreach($results as $row) {
    $sche_id = $row['banquet_schedule_id'];
    $status = $row['status'];
    $status_name = $row['status_name'];
    $res_date = $row['date'];
    $reservation_id = $row['reservation_id'];
    $reservation_name = $row['reservation_name'];
    $reservation_name = cleanLanternName($reservation_name);
    $branch = $row['branch'];
    $purpose_id = $row['purpose_id'];
    $purpose_name = $row['purpose_name'];
    $room_id = $row['room_id'];
    $room_name = $row['room_name'];
    $pic= $row['pic'];
    $pic= cleanLanternName($pic);

    $defects[] = array(
      'error_name' => $error_name,
      'status' => $status,
      'status_name' => $status_name,
      'res_date' => $res_date,
      'reservation_id' => $reservation_id,
      'branch' => $branch,
      'reservation_name' => $reservation_name,
      'purpose_id' => $purpose_id,
      'purpose_name' => $purpose_name,
      'room_id' => $room_id,
      'room_name' => $room_name,
      'pic' => $pic
    );
  }
}

//決定予約・仮予約で金額データがないもの
$error_name="決定・仮で明細なし";
$sql = "SELECT *  FROM `view_daily_subtotal` WHERE `status` IN (1,2) AND `date` >= :first_day AND `gross` IS NULL ORDER BY `date`,`reservation_id`,`branch`";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
if($count > 0) {
  foreach($results as $row) {
    $sche_id = $row['sche_id'];
    $status = $row['status'];
    $status_name = $row['status_name'];
    $res_date = $row['date'];
    $reservation_id = $row['reservation_id'];
    $reservation_name = $row['reservation_name'];
    $reservation_name = cleanLanternName($reservation_name);
    $branch = $row['branch'];
    $purpose_id = $row['purpose_id'];
    $purpose_name = $row['purpose_short'];
    $room_id = $row['room_id'];
    $room_name = $row['room_name'];
    $pic= $row['pic'];
    $pic= cleanLanternName($pic);

    $defects[] = array(
      'error_name' => $error_name,
      'status' => $status,
      'status_name' => $status_name,
      'res_date' => $res_date,
      'reservation_id' => $reservation_id,
      'branch' => $branch,
      'reservation_name' => $reservation_name,
      'purpose_id' => $purpose_id,
      'purpose_name' => $purpose_name,
      'room_id' => $room_id,
      'room_name' => $room_name,
      'pic' => $pic
    );
  }
}

try {
  $filename = 'defact-'.$ym.'-'.date('YmdHis').'.csv';
  header('Content-Type: text/csv; charset=Shift_JIS');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  $output = fopen('php://output', 'w');
 
  // CSVのヘッダ行
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
  fputcsv($output, array_map(function($v){ return mb_convert_encoding($v, 'SJIS-win', 'UTF-8'); }, $header));

  // データ行
  foreach($defects as $defect) {
    $encodedRow = array_map(function($v){
      return mb_convert_encoding($v, 'SJIS-win', 'UTF-8');
    }, $defect);
    fputcsv($output, $encodedRow);
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
  
  $name = preg_replace("/[0-9０-９]{2}年度/u", "", $name);

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