<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
# ini_set('display_errors', 1);
# ini_set('display_startup_errors', 1);
# error_reporting(E_ALL);

require_once('../../../common/conf.php');
require_once('../functions/admin_banquet.php');
require_once('../../../phpspreadsheet/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

//カテゴリーの色設定
function applyCategoryColor($sheet, $cell, $banquet_category_id) {
  $colors = [
    1 => 'FF92D050',
    2 => 'FFFFFF00',
    3 => 'FFFFC000',
    9 => 'FFDDEBF7',
  ];
  if (isset($colors[$banquet_category_id])) {
    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($colors[$banquet_category_id]);
  }
}
// セルを中央揃え
function centerCell($sheet, $cell) {
  $sheet->getStyle($cell)->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);
}
// 桁区切り
function formatAmount($sheet, $cell) {
  $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
}
// 表のヘッダースタイル
function applyHeaderStyle($sheet, $cell) {
  $sheet->getStyle($cell)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFD9D9D9');
  centerCell($sheet, $cell);
  applyThinTopBorder($sheet, $cell);
}
// 横罫線（細線）をセルに適用
function applyThinBorder($sheet, $cell) {
  $sheet->getStyle($cell)
    ->getBorders()->getBottom()
    ->setBorderStyle(Border::BORDER_THIN);
  $sheet->getStyle($cell)
    ->getBorders()->getLeft()
    ->setBorderStyle(Border::BORDER_THIN);
  $sheet->getStyle($cell)
    ->getBorders()->getRight()
    ->setBorderStyle(Border::BORDER_THIN);
}
// 上罫線（細線）をセルに適用
function applyThinTopBorder($sheet, $cell) {
  $sheet->getStyle($cell)
    ->getBorders()->getTop()
    ->setBorderStyle(Border::BORDER_THIN);
}

// 横罫線（点線）を行全体に適用
function applyDottedBottomBorder($sheet, $cell) {
  // 点線の下罫線を設定
  $sheet->getStyle($cell)
    ->getBorders()->getBottom()
    ->setBorderStyle(Border::BORDER_DOTTED);

  // 左端と右端に実線の縦罫線を設定
  $sheet->getStyle($cell)
    ->getBorders()->getLeft()
    ->setBorderStyle(Border::BORDER_THIN);

  $sheet->getStyle($cell)
    ->getBorders()->getRight()
    ->setBorderStyle(Border::BORDER_THIN);
}


$ym = $_GET['ym'] ?? date('Y-m');
$data = getMonthlySales($ym);
$week = ['日', '月', '火', '水', '木', '金', '土'];
$add_col = 32 - $data['last_day'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
// シート名を「2025年08月」形式に
$dt = DateTime::createFromFormat('Y-m', $ym);
$sheetName = $dt->format('Y年m月');
$sheet->setTitle($sheetName);
// シートのタイトルを設定
$spreadsheet->getDefaultStyle()->getFont()->setSize(9);
$sheet->getColumnDimension('A')->setWidth(9);
$sheet->getColumnDimension('B')->setWidth(10);
$sheet->getColumnDimension('C')->setWidth(12);
foreach (range('D', 'S') as $col) {
  $sheet->getColumnDimension($col)->setWidth(19);
}
$row = 1;

// 年月出力
$sheet->setCellValue("A{$row}", $data['year_month']);
$sheet->getStyle("A{$row}")->getFont()
  ->setSize(20)
  ->setBold(true)
  ->setItalic(true);
$row += 2;

// ▼ 表のヘッダー出力（前半 1-16日）
$sheet->setCellValue("A{$row}", '階');
$sheet->setCellValue("B{$row}", '会場名');
$sheet->setCellValue("C{$row}", '項目');
applyHeaderStyle($sheet, "A{$row}");
applyHeaderStyle($sheet, "B{$row}");
applyHeaderStyle($sheet, "C{$row}");
centerCell($sheet, "A{$row}");
centerCell($sheet, "B{$row}");
centerCell($sheet, "C{$row}");
applyThinBorder($sheet, "A{$row}");
applyThinBorder($sheet, "B{$row}");
applyThinBorder($sheet, "C{$row}");
// 日付ヘッダーの出力
$col = 'D';
for ($i = 1; $i <= 16; $i++) {
  $date = "$ym-" . sprintf('%02d', $i);
  $dateObj = new DateTime($date);
  $day = (int)$dateObj->format('j');   // 日
  $month = (int)$dateObj->format('n'); // 月
  $w = (int)$dateObj->format('w');     // 曜日番号（0=日）
  $header = ($i === 1 ? "{$month}/{$day}" : $day) . ' (' . $week[$w] . ')';
  $cell = $col . $row;
  $sheet->setCellValue($cell, $header);
  applyHeaderStyle($sheet, $cell);
  centerCell($sheet, $cell);
  applyThinBorder($sheet, $cell);
  $col++;
}
$row++;

foreach ($data['rooms'] as $room) {
  $mergeStartRow = $row;
  $room_id = $room['room_id'];
  $sheet->setCellValue("A{$row}", $room['floor']);
  $sheet->mergeCells("A{$mergeStartRow}:A" . ($mergeStartRow + 2));
  $sheet->mergeCells("A{$mergeStartRow}:A" . ($mergeStartRow + 2));
  $sheet->setCellValue("B{$row}", $room['room_name']);
  $sheet->mergeCells("B{$mergeStartRow}:B" . ($mergeStartRow + 2));
  $sheet->mergeCells("B{$mergeStartRow}:B" . ($mergeStartRow + 2));
  $sheet->setCellValue("C{$row}", '名称');
  centerCell($sheet, "A{$row}"); centerCell($sheet, "B{$row}"); centerCell($sheet, "C{$row}");
  applyThinBorder($sheet, "A{$row}");
  applyThinBorder($sheet, "B{$row}");
  applyDottedBottomBorder($sheet, "C{$row}");
  $col = 'D';
  $hasReservation = [];
  for ($i = 1; $i <= 16; $i++) {
    $value = '';
    $bc = 0;
    $date = "$ym-" . sprintf('%02d', $i);
    foreach ($data['sales'] as $sale) {
      if ($sale['room_id'] === $room_id && $sale['date'] === $date) {
        $value = $sale['reservation_name'];
        $bc = $sale['banquet_category_id'];
        $hasReservation[$i] = true;
        break;
      }
    }
    $cell = $col . $row;
    $sheet->setCellValue($cell, $value);
    centerCell($sheet, $cell);
    applyCategoryColor($sheet, $cell, $bc);
    applyDottedBottomBorder($sheet, $cell);
    $col++;
  }
  $row++;
  
  $sheet->setCellValue("C{$row}", '時間（人数）');
  centerCell($sheet, "C{$row}");
  applyDottedBottomBorder($sheet, "C{$row}");
  $col = 'D';
  for ($i = 1; $i <= 16; $i++) {
    $value = '';
    $bc = 0;
    $date = "$ym-" . sprintf('%02d', $i);
    foreach ($data['sales'] as $sale) {
      if ($sale['room_id'] === $room_id && $sale['date'] === $date) {
        $value = (new DateTime($sale['start']))->format('H:i') . '-' . (new DateTime($sale['end']))->format('H:i') . " ({$sale['people']})";
        $bc = $sale['banquet_category_id'];
        break;
      }
    }
    $cell = $col . $row;
    $sheet->setCellValue($cell, $value);
    centerCell($sheet, $cell);
    applyCategoryColor($sheet, $cell, $bc);
    applyDottedBottomBorder($sheet, $cell);
    $col++;
  }
  applyThinBorder($sheet, "A{$row}");
  applyThinBorder($sheet, "B{$row}");
  $row++;
  
  $sheet->setCellValue("C{$row}", '金額');
  centerCell($sheet, "C{$row}");
  applyThinBorder($sheet, "C{$row}");
  $col = 'D';
  for ($i = 1; $i <= 16; $i++) {
    $value = '';
    $bc = 0;
    $date = "$ym-" . sprintf('%02d', $i);
    $hasSale = false;
    foreach ($data['sales'] as $sale) {
      if ($sale['room_id'] === $room_id && $sale['date'] === $date) {
        $value = ($sale['ex_ts'] !== null) ? $sale['ex_ts'] : 0;
        $bc = $sale['banquet_category_id'];
        $hasSale = true;
        break;
      }
    }
    if (!$hasSale && isset($hasReservation[$i])) {
      $value = 0;
    }
    $cell = $col . $row;
    $sheet->setCellValue($cell, $value);
    centerCell($sheet, $cell);
    applyCategoryColor($sheet, $cell, $bc);
    applyThinBorder($sheet, $cell);
    if ($value !== '') formatAmount($sheet, $cell);
    $col++;
  }
  applyThinBorder($sheet, "A{$row}");
  applyThinBorder($sheet, "B{$row}");
  $row++;
}

// ▼ 後半の見出し出力
$row++; // 前半と後半の間に1行空ける
$sheet->setCellValue("A{$row}", '階');
$sheet->setCellValue("B{$row}", '会場名');
$sheet->setCellValue("C{$row}", '項目');
applyHeaderStyle($sheet, "A{$row}");
applyHeaderStyle($sheet, "B{$row}");
applyHeaderStyle($sheet, "C{$row}");
centerCell($sheet, "A{$row}");
centerCell($sheet, "B{$row}");
centerCell($sheet, "C{$row}");
applyThinBorder($sheet, "A{$row}");
applyThinBorder($sheet, "B{$row}");
applyThinBorder($sheet, "C{$row}");
$col = 'D';
for ($i = 17; $i <= $data['last_day']; $i++) {
  $date = "$ym-" . sprintf('%02d', $i);
  $dateObj = new DateTime($date);
  $day = (int)$dateObj->format('j');   // 日
  $month = (int)$dateObj->format('n'); // 月
  $w = (int)$dateObj->format('w');     // 曜日番号（0=日）
  $header = ($i === 17 ? "{$month}/{$day}" : $day) . ' (' . $week[$w] . ')';
  $cell = $col . $row;
  $sheet->setCellValue($cell, $header);
  applyHeaderStyle($sheet, $cell);
  centerCell($sheet, $cell);
  applyThinBorder($sheet, $cell);
  $col++;
}
for ($i = 0; $i < $add_col; $i++) {
  $cell = $col++ . $row;
  $sheet->setCellValue($cell, '');
  centerCell($sheet, $cell);
}
$row++;

foreach ($data['rooms'] as $room) {
  $room_id = $room['room_id'];
  $mergeStartRow = $row;
  $sheet->setCellValue("A{$row}", $room['floor']);
  $sheet->setCellValue("B{$row}", $room['room_name']);
  $mergeStartRow = $row;
  $sheet->mergeCells("A{$mergeStartRow}:A" . ($mergeStartRow + 2));
  $sheet->mergeCells("B{$mergeStartRow}:B" . ($mergeStartRow + 2));

  $sheet->setCellValue("C{$row}", '名称');
  centerCell($sheet, "A{$row}"); centerCell($sheet, "B{$row}"); centerCell($sheet, "C{$row}");
  applyThinBorder($sheet, "A{$row}");
  applyThinBorder($sheet, "B{$row}");
  applyDottedBottomBorder($sheet, "C{$row}");
  $col = 'D';
  $hasReservation = [];
  for ($i = 17; $i <= $data['last_day']; $i++) {
    $value = '';
    $bc = 0;
    $date = "$ym-" . sprintf('%02d', $i);
    foreach ($data['sales'] as $sale) {
      if ($sale['room_id'] === $room_id && $sale['date'] === $date) {
        $value = $sale['reservation_name'];
        $bc = $sale['banquet_category_id'];
        $hasReservation[$i] = true;
        break;
      }
    }
    $cell = $col . $row;
    $sheet->setCellValue($cell, $value);
    centerCell($sheet, $cell);
    applyCategoryColor($sheet, $cell, $bc);
    applyDottedBottomBorder($sheet, $cell);
    $col++;
  }
  for ($i = 0; $i < $add_col; $i++) {
    $cell = $col++ . $row;
    $sheet->setCellValue($cell, '');
    centerCell($sheet, $cell);
  }
  $row++;

  $sheet->setCellValue("C{$row}", '時間（人数）');
  centerCell($sheet, "C{$row}");
  applyDottedBottomBorder($sheet, "C{$row}");
  $col = 'D';
  for ($i = 17; $i <= $data['last_day']; $i++) {
    $value = '';
    $bc = 0;
    $date = "$ym-" . sprintf('%02d', $i);
    foreach ($data['sales'] as $sale) {
      if ($sale['room_id'] === $room_id && $sale['date'] === $date) {
        $value = (new DateTime($sale['start']))->format('H:i') . '-' . (new DateTime($sale['end']))->format('H:i') . " ({$sale['people']})";
        $bc = $sale['banquet_category_id'];
        break;
      }
    }
    $cell = $col . $row;
    $sheet->setCellValue($cell, $value);
    centerCell($sheet, $cell);
    applyCategoryColor($sheet, $cell, $bc);
    applyDottedBottomBorder($sheet, $cell);
    $col++;
  }
  for ($i = 0; $i < $add_col; $i++) {
    $cell = $col++ . $row;
    $sheet->setCellValue($cell, '');
    centerCell($sheet, $cell);
  }
  applyThinBorder($sheet, "A{$row}");
  applyThinBorder($sheet, "B{$row}");
  $row++;

  $sheet->setCellValue("C{$row}", '金額');
  centerCell($sheet, "C{$row}");
  applyThinBorder($sheet, "C{$row}");
  $col = 'D';
  for ($i = 17; $i <= $data['last_day']; $i++) {
    $value = '';
    $bc = 0;
    $date = "$ym-" . sprintf('%02d', $i);
    $hasSale = false;
    foreach ($data['sales'] as $sale) {
      if ($sale['room_id'] === $room_id && $sale['date'] === $date) {
        $value = ($sale['ex_ts'] !== null) ? $sale['ex_ts'] : 0;
        $bc = $sale['banquet_category_id'];
        $hasSale = true;
        break;
      }
    }
    if (!$hasSale && isset($hasReservation[$i])) {
      $value = 0;
    }
    $cell = $col . $row;
    $sheet->setCellValue($cell, $value);
    centerCell($sheet, $cell);
    applyCategoryColor($sheet, $cell, $bc);
    applyThinBorder($sheet, $cell);
    if ($value !== '') formatAmount($sheet, $cell);
    $col++;
  }
  for ($i = 0; $i < $add_col; $i++) {
    $cell = $col++ . $row;
    $sheet->setCellValue($cell, '');
    centerCell($sheet, $cell);
  }
  applyThinBorder($sheet, "A{$row}");
  applyThinBorder($sheet, "B{$row}");
  $row++;
}

// 合計表出力
// 合計表出力（ヘッダー）
$row += 2;
$sheet->setCellValue("A{$row}", '項目');
$sheet->setCellValue("C{$row}", '金額');
$sheet->mergeCells("A{$row}:B{$row}"); // ← ★ここで結合！
$sheet->mergeCells("C{$row}:D{$row}"); // ← ★ここでも結合！

centerCell($sheet, "A{$row}");
centerCell($sheet, "C{$row}");
applyHeaderStyle($sheet, "A{$row}");
applyHeaderStyle($sheet, "B{$row}"); 
applyHeaderStyle($sheet, "C{$row}");
applyHeaderStyle($sheet, "D{$row}");
applyThinBorder($sheet, "A{$row}");
applyThinBorder($sheet, "B{$row}");
applyThinBorder($sheet, "C{$row}");
applyThinBorder($sheet, "D{$row}");
$sheet->getStyle("D{$row}")
  ->getBorders()->getRight()
  ->setBorderStyle(Border::BORDER_THIN);
$row++;

$totals = [
  ['会議', $data['total_kaigi']],
  ['宴会', $data['total_enkai']],
  ['食事', $data['total_shokuji']],
  ['その他', $data['total_others']],
  ['合計', $data['total']],
];

$category_ids = [
  '会議' => 1,
  '宴会' => 2,
  '食事' => 3,
  'その他' => 9,
  '合計' => 0, // 色を付けたくない場合はIDを 0 にする or 条件でスキップ
];

// 合計データ行出力
foreach ($totals as [$label, $amount]) {
  $sheet->setCellValue("A{$row}", $label);
  $sheet->setCellValue("C{$row}", $amount);
  $sheet->mergeCells("A{$row}:B{$row}"); // ← ★ここでも結合！
  $sheet->mergeCells("C{$row}:D{$row}"); // ← ★ここでも結合！
  centerCell($sheet, "A{$row}");
  centerCell($sheet, "C{$row}");
  applyThinBorder($sheet, "A{$row}");
  applyThinBorder($sheet, "B{$row}");
  applyThinBorder($sheet, "C{$row}");
  applyThinBorder($sheet, "D{$row}");
  $sheet->getStyle("D{$row}")
  ->getBorders()->getRight()
  ->setBorderStyle(Border::BORDER_THIN);
  formatAmount($sheet, "C{$row}");

  // 背景色
  $cat_id = $category_ids[$label] ?? 0;
  if ($cat_id !== 0) {
    applyCategoryColor($sheet, "A{$row}", $cat_id);
    applyCategoryColor($sheet, "C{$row}", $cat_id);
  }
  $sheet->getStyle("A{$row}")->getFont()
    ->setSize(15);
  $sheet->getStyle("C{$row}")->getFont()
    ->setSize(15)
    ->setBold(true);
  $row++;
}

// 用紙サイズ A3 に設定
$sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A3);
// 向き：縦（ポートレート）
$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);

// 余白を「狭く」（単位はインチ：1インチ＝2.54cm）
$sheet->getPageMargins()->setTop(0.25);
$sheet->getPageMargins()->setRight(0.25);
$sheet->getPageMargins()->setLeft(0.25);
$sheet->getPageMargins()->setBottom(0.25);

// 全体を1ページに収める（横：1ページ、高さ：自動または1）
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(1); // 高さはページ分割OKの場合

// 印刷範囲も必要に応じて設定（例：A1 ～ 最終列/行）
$lastCol = 'S'; // または $col の値を使って算出
$lastRow = $row - 1;
$sheet->getPageSetup()->setPrintArea("A1:{$lastCol}{$lastRow}");

$now = date('YmdHis');
$filename = "sales-{$ym}-{$now}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename={$filename}");
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>