<?php
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


//ダミーのデータプログラム
$range = 10;
$week = array('日', '月', '火', '水', '木', '金', '土');
if(date('H') >= 18){
  $date = date('Y-m-d', strtotime('1 day'));
}else{
  $date = date('Y-m-d');
}
$week_num = date('w', strtotime($date));
$wd= $week[$week_num];
$hidzuke = date('Y年m月d日', strtotime($date)).'('.$wd.')';
$contents = array();
$staff = array(
  "森",
  "吉村",
  "渡瀬",
  "石黒",
  "山村",
  "辻中",
  "伊藤",
  "竹市",
  "近藤",
  "松原",
  "岡田",
  "鈴村",
  "加藤",
  "柿木",
  "水谷"
);
$statuscode = array(
  11,
  12,
  13,
  11,
  12,
  13,
  11,
  12,
  13,
  11,
  12,
  13,
  11,
  12,
  13,
  11,
  12,
  13,
  21,
  22,
  23,
  24,
  31,
  32
);
$statusdata = array(
  11 => "出勤",
  12 => "中番",
  13 => "遅番", 
  21 => "公休",
  22 => "有休",
  23 => "特休",
  24 => "代休",
  31 => "出張",
  32 => "研修"
);
$statushorts = array(
  11 => "勤",
  12 => "中",
  13 => "遅", 
  21 => "休",
  22 => "休",
  23 => "特",
  24 => "代",
  31 => "張",
  32 => "研"
);
$start = array(
  11 => "9:00",
  12 => "11:00",
  13 => "12:15", 
  21 => "",
  22 => "",
  23 => "",
  24 => "",
  31 => "",
  32 => ""
);
$end = array(
  11 => "17:45",
  12 => "19:45",
  13 => "21:00", 
  21 => "",
  22 => "",
  23 => "",
  24 => "",
  31 => "",
  32 => ""
);

#$alart = "緊急アラートがある場合はここに表示されます";
$alart = "";
$notice = "このスケジュールはデモ用のダミーデータです。プログラムがテキトーに生成しています。";
$notice .= "<br>実際のデータとは異なりますのでご注意ください。";
$notice .= "<br>18時を過ぎると翌日のスケジュールが表示されます。";
$notice .= "<br><br>管理画面からシフトのCSVデータをアップロードすることで、スケジュールの追加を行うことを想定しています。";
$notice .= "<br>また、スケジュールの編集や削除も行えるようになる予定です。";

for($i=0; $i<count($staff); $i++){
  $scherdule = array();
  for($j=0; $j<$range; $j++){
    $exedate = date('Y-m-d', strtotime($date.' +'.$j.' day'));
    $exewd= $week[date('w', strtotime($exedate))];
    $rand= mt_rand(0, count($statuscode)-1);
    $status = $statuscode[$rand];
    $status_name = $statusdata[$status];
    $status_short = $statushorts[$status];
    $scherdule[] = array(
      'date' => $exedate,
      'week' => $exewd,
      "status" => $status,
      "status_name" => $status_name,
      "status_short" => $status_short,
      'start' => $start[$status],
      'end' => $end[$status]
    );
  }
  $contents[] = array(
    'name' => $staff[$i],
    'schedule' => $scherdule
  );
}
$data = array(
  'status' => "success",
  'date' => $date,
  'week' => $wd,
  'hizuke' => $hidzuke,
  'alart' => $alart,
  'notice' => $notice,
  'contents' => $contents
);
$jsonstr=json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo $jsonstr;
?>