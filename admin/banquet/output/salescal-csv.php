<?php
require_once('../../../common/conf.php');

$ym = date('Y-m');
if(isset($_REQUEST['ym']) && $_REQUEST['ym'] != '') {
  $ym = $_REQUEST['ym'];
}

$dt = new DateTime($ym . '-01');  // 「年月」だけではエラーになる可能性があるため、日を付ける
$last_day = $dt->format('t');
$year_month = $dt->format('Y年 m月');
#$last_day = date('t', strtotime($ym));
#$year_month = date('Y年 m月', strtotime($ym));

$dbh = new PDO(DSN, DB_USER, DB_PASS);
$sql = "SELECT * FROM `view_daily_subtotal` where `ym` = :ym and `purpose_id` <> 3";
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
    $room_name = $row['room_name'];
    $date = $row['date'];
    $reservation_name = $row['reservation_name'];
    $short_name = cleanLanternName($reservation_name);
    $people = $row['people'];
    $banquet_category_id = $row['banquet_category_id'];
    $banquet_category_name = $row['banquet_category_name'];
    $start= $row['start'];
    $end = $row['end'];
    $gross = $row['gross'];
    $ex_ts = $row['ex-ts'];
    $sales[] = array(
      'date' => $date,
      'room_id' => $room_id,
      'room_name' => $room_name,
      'reservation_name' => $reservation_name,
      'short_name' => $short_name,
      'banquet_category_id' => $banquet_category_id,
      'banquet_category_name' => $banquet_category_name,
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


try {
  $filename = 'salescal-'.$ym.'-'.date('YmdHis').'.csv';
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  $output = fopen('php://output', 'w');
  // BOMを出力
  fwrite($output, "\xEF\xBB\xBF");
  // CSVのヘッダ行
  fputcsv($output, array(
    '日付',
    '部屋ID',
    '部屋名',
    '予約名',
    '予約名（短縮）',
    'カテゴリーID',
    'カテゴリー名',
    '人数',
    '開始時間',
    '終了時間',
    '売上GROSS',
    '売上税サ抜'
  ));
  foreach($sales as $sale) {
    fputcsv($output, $sale);
  }

  // 合計行
  fputcsv($output, array(
    $year_month,
    '税・サ抜抜計'
  ));

  fputcsv($output, array(
    '宴会',
    $total_enkai
  ));
  fputcsv($output, array(
    '会議',
    $total_kaigi
  ));
  fputcsv($output, array(
    '食事',
    $total_shokuji
  ));
  fputcsv($output, array(
    'その他',
    $total_others
  ));
  fputcsv($output, array(
    '合計',
    $total
  ));
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