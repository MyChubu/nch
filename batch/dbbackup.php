<?php
require_once('../common/conf.php');

// バックアップファイルの作成
$filePath = '../../nchbackup/db/'; // ファイルを保存するディレクトリ
$fileName = 'nch_'.date('ymd_His').'.sql';
$savePath = $filePath.$fileName;
$command = "mysqldump ".DB_NAME." --host=".DB_HOST." --user=".DB_USER." --password=".DB_PASS." --single-transaction > ".$savePath;
exec($command);

// 圧縮処理
$zipFileName = $fileName . '.zip';
$zipFilePath = $filePath . $zipFileName;

$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
  $zip->addFile($savePath, $fileName); // SQLファイルをZIPに追加
  $zip->close();

  // 元のSQLファイルを削除する場合（任意）
  unlink($savePath);
  
  echo "アーカイブ作成成功: {$zipFilePath}\n";
} else {
  echo "アーカイブ作成失敗\n";
}

// ★ ここから古いファイルの削除処理 ★

$now = time();
$expire = 30 * 24 * 60 * 60; // 30日（秒数換算）

$files = glob($filePath . '*');  // フォルダー内のすべてのファイルを取得

foreach ($files as $file) {
  if (is_file($file)) {
    $filemtime = filemtime($file);
    if ($now - $filemtime > $expire) {
      unlink($file);
      echo "削除しました: {$file}\n";
    }
  }
}
?>
