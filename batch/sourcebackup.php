<?php
// 圧縮したいディレクトリのパス
$filePath = '../../nchbackup/source/'; // ファイルを保存するディレクトリ
$fileName = 'nch_'.date('ymd_His').'.zip';
$sourcePath = '../../nch.nagoyacrown/'; // 圧縮したい元のディレクトリ
$zipFilePath = $filePath.$fileName; // 出力するZIPファイル名

// ZipArchive クラスのインスタンス作成
$zip = new ZipArchive();

// ZIPファイルを作成（または上書き）
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
  exit("ZIPファイルの作成に失敗しました: $zipFilePath\n");
}

// ディレクトリを再帰的に処理する関数
function addFolderToZip($folder, $zip, $baseFolderLength) {
  $files = scandir($folder);
  foreach ($files as $file) {
    if ($file == '.' || $file == '..') {
      continue;
    }

    $fullPath = $folder . DIRECTORY_SEPARATOR . $file;
    $localPath = substr($fullPath, $baseFolderLength + 1); // ZIP内のパス

    if (is_dir($fullPath)) {
      $zip->addEmptyDir($localPath); // ディレクトリを作成
      addFolderToZip($fullPath, $zip, $baseFolderLength); // 再帰呼び出し
    } else if (is_file($fullPath)) {
      $zip->addFile($fullPath, $localPath); // ファイルを追加
    }
  }
}

// 実際に追加処理を実行
addFolderToZip($sourcePath, $zip, strlen($sourcePath));

// ZIPファイルを閉じる
$zip->close();

echo "アーカイブが作成されました: $zipFilePath\n";

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
