<?php
require_once('../../phpqrcode/qrlib.php');
$contents = $_REQUEST['data'];
$size=$_REQUEST['size'];
if($size == ''){
  $size = 10;
}
$margin=$_REQUEST['margin'];
if($margin == ''){
  $margin = 2;
}
$filename = '../../data/images/qrcode/tmp/qrcode.png';
QRcode::png($contents, $filename, QR_ECLEVEL_L, $size, $margin);
header('Content-Type: image/png');
readfile($filename);
?>