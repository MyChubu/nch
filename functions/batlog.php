<?php
//途中です
function batlog(){
  $dir =  $_SERVER['DOCUMENT_ROOT'] . '/data/logs/';
  if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
  }
  $filename = $dir . 'bat_' . date('Ymd') . '.log';
  if (!file_exists($filename)) {
    touch($filename);
    chmod($filename, 0666);
  }
  //取得項目
  $date = date('Y-m-d H:i:s'); //アクセス日時
  $response = http_response_code(); //ステータスコード

  $page = $_SERVER['PHP_SELF']; //アクセスしたページ
  $ip = $_SERVER['REMOTE_ADDR']; //IPアドレス
  $user_agent = $_SERVER['HTTP_USER_AGENT']; //ブラウザ情報
  $referer = $_SERVER['HTTP_REFERER'] ?? '-'; //リファラ
  $uri = $_SERVER['REQUEST_URI']; //リクエストURI
  $requestMethod = $_SERVER['REQUEST_METHOD']; //リクエストメソッド
  //ログの書き込み
  $log = "{$date}\t{$response}\t{$user_name}\t{$page}\t{$ip}\t{$user_agent}\t{$referer}\t{$uri}\t{$requestMethod}\n";
  file_put_contents($filename, $log, FILE_APPEND | LOCK_EX);
}
?>