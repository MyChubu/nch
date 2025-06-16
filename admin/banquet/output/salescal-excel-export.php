<?php
// ▼ 開発中のみ有効なエラー出力（本番ではコメントアウト推奨）
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

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

function centerCell($sheet, $cell) {
  $sheet->getStyle($cell)->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);
}

function formatAmount($sheet, $cell) {
  $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
  $sheet->getStyle($cell)->getFont()->setSize(14);

}

function applyCategoryColor($sheet, $cell, $banquet_category_id) {
  $colors = [
    1 => 'FF92D050',
    2 => 'FFFFFF00',
    3 => 'FFFFC000',
    9 => 'FFDDEBF7',
  ];
  if (isset($colors[$banquet_category_id])) {
    $sheet->getStyle($cell)->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB($colors[$banquet_category_id]);
  }
}

function applyHeaderStyle($sheet, $cell) {
  $sheet->getStyle($cell)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFD9D9D9');
  $sheet ->getStyle($cell)->getFont()->setSize(14)->setBold(true);
  centerCell($sheet, $cell);
  applyThinTopBorder($sheet, $cell);
}

function applyEmptyHeaderStyle($sheet, $cell) {
  $sheet->getStyle($cell)->getFill()
    ->setFillType(Fill::FILL_SOLID);
  
  centerCell($sheet, $cell);
  applyThinTopBorder($sheet, $cell);
}

function applyThinBorder($sheet, $cell) {
  $borders = $sheet->getStyle($cell)->getBorders();
  #$borders->getTop()->setBorderStyle(Border::BORDER_THIN);
  $borders->getBottom()->setBorderStyle(Border::BORDER_THIN);
  $borders->getLeft()->setBorderStyle(Border::BORDER_THIN);
  $borders->getRight()->setBorderStyle(Border::BORDER_THIN);
}

function applyThinTopBorder($sheet, $cell) {
  $sheet->getStyle($cell)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
}

function applyDottedBottomBorderOnly($sheet, $cell) {
  $sheet->getStyle($cell)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOTTED);
}

function applyDottedBottomBorder($sheet, $cell) {
  applyDottedBottomBorderOnly($sheet, $cell);
  $sheet->getStyle($cell)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
  $sheet->getStyle($cell)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
}

function outputRoomRows($sheet, &$row, $room, $data, $ym_for_date, $dayStart, $dayEnd, $addCol = 0) {
  $room_id = $room['room_id'];
  $mergeStartRow = $row;

  $sheet->setCellValue("A{$row}", $room['floor']);
  $sheet->setCellValue("B{$row}", $room['room_name']);
  $sheet->mergeCells("A{$mergeStartRow}:A" . ($mergeStartRow + 2));
  $sheet->mergeCells("B{$mergeStartRow}:B" . ($mergeStartRow + 2));
  centerCell($sheet, "A{$row}");
  centerCell($sheet, "B{$row}");
  $sheet ->getStyle("A{$row}")->getFont()->setSize(14)->setBold(true);
  $sheet ->getStyle("B{$row}")->getFont()->setSize(14)->setBold(true);

  // フロア・会場列は最初の行でだけ適用（3行共通）
  // A列・B列の罫線は先頭行のみに適用（3行共通）
  if ($index === 0) {
    applyThinBorder($sheet, "A{$row}");
    applyThinBorder($sheet, "B{$row}");
  }
  
  $hasReservation = [];

  $rowTypes = [
    ['label' => '名称', 'getter' => fn($sale) => $sale['reservation_name'], 'border' => 'dotted'],
    ['label' => '時間（人数）', 'getter' => fn($sale) => (new DateTime($sale['start']))->format('H:i') . '-' . (new DateTime($sale['end']))->format('H:i') . " ({$sale['people']})", 'border' => 'dotted'],
    ['label' => '金額', 'getter' => fn($sale) => $sale['ex_ts'] ?? 0, 'border' => 'solid'],
  ];

  foreach ($rowTypes as $index => $type) {
    $sheet->getRowDimension($row)->setRowHeight(18);
    $sheet->setCellValue("C{$row}", $type['label']);
    centerCell($sheet, "C{$row}");
    if ($type['border'] === 'dotted') {
      applyDottedBottomBorder($sheet, "C{$row}");
    } else {
      applyThinBorder($sheet, "C{$row}");
    }

    $col = 'D';
    for ($i = $dayStart; $i <= $dayEnd; $i++) {
      $date = "$ym_for_date-" . sprintf('%02d', $i);
      $value = ''; $bc = 0; $hasSale = false;

      foreach ($data['sales'] as $sale) {
        if ($sale['room_id'] === $room_id && $sale['date'] === $date) {
          $value = $type['getter']($sale);
          $bc = $sale['banquet_category_id'];
          $hasSale = true;
          if ($type['label'] === '名称') $hasReservation[$i] = true;
          break;
        }
      }

      if ($type['label'] === '金額' && !$hasSale && isset($hasReservation[$i])) {
        $value = 0;
      }

      $cell = $col . $row;
      $sheet->setCellValue($cell, $value);
      centerCell($sheet, $cell);
      applyCategoryColor($sheet, $cell, $bc);

      if ($type['border'] === 'dotted') {
        applyDottedBottomBorder($sheet, $cell);
      } else {
        applyThinBorder($sheet, $cell);
        if ($value !== '') formatAmount($sheet, $cell);
      }
      $col++;
    }

    for ($i = 0; $i < $addCol; $i++) {
      $cell = $col++ . $row;
      $sheet->setCellValue($cell, '');
      centerCell($sheet, $cell);
      // ヘッダー空欄セルに白背景と実線罫線を適用
      $sheet->getStyle($cell)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFFFFFFF');
      applyThinBorder($sheet, $cell);
      if ($type['border'] === 'dotted') {
        applyDottedBottomBorder($sheet, $cell);
      } else {
        applyThinBorder($sheet, $cell);
      }
      if ($type['border'] === 'dotted') {
        applyDottedBottomBorder($sheet, $cell);
      } else {
        applyThinBorder($sheet, $cell);
      }
      if ($type['border'] === 'dotted') {
        applyDottedBottomBorder($sheet, $cell);
      } else {
        applyThinBorder($sheet, $cell);
      }
    }

    applyThinBorder($sheet, "A{$row}");
    applyThinBorder($sheet, "B{$row}");
    $row++;
  }
}

// ▼ メイン処理開始
$ym = $_GET['ym'] ?? date('Y-m');
$data = getMonthlySales($ym);
$ym_for_date = $ym; // 日付処理に使うY-m形式
$week = ['日', '月', '火', '水', '木', '金', '土'];
$add_col = 32 - $data['last_day'];

$spreadsheet = new Spreadsheet();
$spreadsheet->getDefaultStyle()->getFont()->setName('ＭＳ Ｐゴシック');
$sheet = $spreadsheet->getActiveSheet();
$sheetName = DateTime::createFromFormat('Y-m', $ym)->format('Y年m月');
$sheet->setTitle($sheetName);
$spreadsheet->getDefaultStyle()->getFont()->setSize(10);

// 列幅の初期設定
$sheet->getColumnDimension('A')->setWidth(9);
$sheet->getColumnDimension('B')->setWidth(11);
$sheet->getColumnDimension('C')->setWidth(14);
foreach (range('D', 'S') as $col) {
  $sheet->getColumnDimension($col)->setWidth(21);
}

$row = 1;
$sheet->setCellValue("A{$row}", $data['year_month']);
$sheet->getStyle("A{$row}")->getFont()->setSize(20)->setBold(true)->setItalic(true);

$sheet->setCellValue("D{$row}", '会場予約状況');
$sheet->getStyle("D{$row}")->getFont()->setSize(20)->setBold(true);

$sheet->setCellValue("G{$row}", '会議');
$sheet->setCellValue("H{$row}", '宴会');
$sheet->setCellValue("I{$row}", '食事');
$sheet->getStyle("G{$row}")->getFont()->setSize(15);
$sheet->getStyle("H{$row}")->getFont()->setSize(15);
$sheet->getStyle("I{$row}")->getFont()->setSize(15);
centerCell($sheet, "G{$row}");
centerCell($sheet, "H{$row}");
centerCell($sheet, "I{$row}");
applyCategoryColor($sheet, "G{$row}", 1);
applyCategoryColor($sheet, "H{$row}", 2);
applyCategoryColor($sheet, "I{$row}", 3);

$row ++;

// ▼ 見出し出力（前半）
$sheet->setCellValue("A{$row}", '階');
$sheet->setCellValue("B{$row}", '会場名');
$sheet->setCellValue("C{$row}", '項目');
foreach (['A','B','C'] as $col) {
  applyHeaderStyle($sheet, "{$col}{$row}");
  centerCell($sheet, "{$col}{$row}");
  applyThinBorder($sheet, "{$col}{$row}");
}
$col = 'D';
for ($i = 1; $i <= 16; $i++) {
  $date = "$ym-" . sprintf('%02d', $i);
  $dateObj = new DateTime($date);
  $header = ($i === 1 ? $dateObj->format('n/j') : $dateObj->format('j')) . ' (' . $week[$dateObj->format('w')] . ')';
  $sheet->setCellValue("{$col}{$row}", $header);
  applyHeaderStyle($sheet, "{$col}{$row}");
  centerCell($sheet, "{$col}{$row}");
  applyThinBorder($sheet, "{$col}{$row}");
  $col++;
}
$row++;

// ▼ 前半の会場出力
foreach ($data['rooms'] as $room) {
  outputRoomRows($sheet, $row, $room, $data, $ym_for_date, 1, 16);
}

$row++; // 区切り行

// ▼ 見出し出力（後半）
$sheet->setCellValue("A{$row}", '階');
$sheet->setCellValue("B{$row}", '会場名');
$sheet->setCellValue("C{$row}", '項目');
foreach (['A','B','C'] as $col) {
  applyHeaderStyle($sheet, "{$col}{$row}");
  centerCell($sheet, "{$col}{$row}");
  applyThinBorder($sheet, "{$col}{$row}");
}
$col = 'D';
for ($i = 17; $i <= 32; $i++) {
  if($i <= $data['last_day']){
    $date = "$ym-" . sprintf('%02d', $i);
    $dateObj = new DateTime($date);
    $header = ($i === 17 ? $dateObj->format('n/j') : $dateObj->format('j')) . ' (' . $week[$dateObj->format('w')] . ')';
    $sheet->setCellValue("{$col}{$row}", $header);
    applyHeaderStyle($sheet, "{$col}{$row}");
  }else {
    $sheet->setCellValue("{$col}{$row}", '');
    applyEmptyHeaderStyle($sheet, "{$col}{$row}");
  }
  
  centerCell($sheet, "{$col}{$row}");
  applyThinBorder($sheet, "{$col}{$row}");
  $col++;
}
for ($i = 0; $i < $add_col; $i++) {
  $sheet->setCellValue("{$col}{$row}", '');
  centerCell($sheet, "{$col}{$row}");
  $col++;
}
$row++;

// ▼ 後半の会場出力
foreach ($data['rooms'] as $room) {
  outputRoomRows($sheet, $row, $room, $data, $ym_for_date, 17, $data['last_day'], $add_col);
}

// ▼ 合計欄出力
$row += 2;
$sheet->setCellValue("A{$row}", '項目');
$sheet->setCellValue("C{$row}", '金額・税サ別（円）');
$sheet->mergeCells("A{$row}:B{$row}");
$sheet->mergeCells("C{$row}:D{$row}");
foreach (['A','B','C','D'] as $col) {
  applyHeaderStyle($sheet, "{$col}{$row}");
  applyThinBorder($sheet, "{$col}{$row}");
}
$row++;

$totals = [
  ['会議', $data['total_kaigi'], 1],
  ['宴会', $data['total_enkai'], 2],
  ['食事', $data['total_shokuji'], 3],
  ['その他', $data['total_others'], 9],
  ['合計', $data['total'], 0],
];

foreach ($totals as [$label, $amount, $cat_id]) {
  $sheet->setCellValue("A{$row}", $label);
  $sheet->setCellValue("C{$row}", $amount);
  $sheet->mergeCells("A{$row}:B{$row}");
  $sheet->mergeCells("C{$row}:D{$row}");
  centerCell($sheet, "A{$row}");
  centerCell($sheet, "C{$row}");
  foreach (['A','B','C','D'] as $col) applyThinBorder($sheet, "{$col}{$row}");
  if ($cat_id !== 0) {
    applyCategoryColor($sheet, "A{$row}", $cat_id);
    applyCategoryColor($sheet, "C{$row}", $cat_id);
  }
  $sheet->getStyle("A{$row}")->getFont()->setSize(15);
  formatAmount($sheet, "C{$row}");
  $sheet->getStyle("C{$row}")->getFont()->setSize(18)->setBold(true);
  $row++;
}
$row+2; // 空行
// ▼ 備考欄
$sheet->setCellValue("A{$row}", '【備考】');
$row++;
$sheet->setCellValue("A{$row}", '※金額は税・サービス料抜きです。');
$row++;
$sheet->setCellValue("A{$row}", '※このカレンダーは、各会場の予約状況を月単位で表示しています。');
$row++;
$sheet->setCellValue("A{$row}", '※同日同会場で複数の利用がある場合、金額が高い・利用人数が多い予約が表示されますが、表示金額は同会場の合計値を表示します。');
$row++;
$sheet->setCellValue("A{$row}", '※月を跨ぐ案件がある場合は、他の集計と合計値が異なることがあります。');


// アクティブセルをA1に設定
$sheet->setSelectedCell('A1');

// ▼ 印刷設定
$sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A3);
$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
$sheet->getPageMargins()->setTop(0.25)->setBottom(0.25)->setLeft(0.25)->setRight(0.25);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(1);
$sheet->getPageSetup()->setPrintArea("A1:S" . ($row - 1));

// ▼ 出力
$now = date('YmdHis');
$filename = "sales-{$ym}-{$now}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename={$filename}");
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
