<?php

// **ファイルを読み込む**
$file = file(CSV_DATA_PATH.$filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$i=0;

foreach($file as $line){
  if($i > 0){
    
      // **エンコーディング変換**
    $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');

    $data = str_getcsv($line, ",", '"');

    $reservation_id=intval($data[0]);
    $date=$data[1];
    $branch=intval($data[2]);
    $detail_number=intval($data[3]);
    $reservation_name=mb_convert_kana($data[4], "KVas");
    $main_room=$data[5];
    $package_category=$data[6];
    $package_cat_name=mb_convert_kana($data[7], "KVas");
    $package_id = mb_convert_kana($data[8], "KVas");
    $package_name=mb_convert_kana($data[9], "KVas");
    $item_group_id = mb_convert_kana($data[10], "KVas");
    $item_group_name=mb_convert_kana($data[11], "KVas");
    $item_id = mb_convert_kana($data[12], "KVas");
    $item_name=mb_convert_kana($data[13], "KVas");
    $item_gene_id = $item_group_id."-".$item_id;
    $unit_price=intval($data[14]);
    $qty=intval($data[15]);
    $amount_gross=intval($data[16]);
    $amount_net=intval($data[17]);
    $service_fee=intval($data[18]);
    $tax=intval($data[19]);
    $discount_name=mb_convert_kana($data[20], "KVas");
    $discount_rate=intval($data[21]);
    $discount_amount=intval($data[22]);
    $check_sql = 'select * from banquet_charges where reservation_id = ? and branch = ? and detail_number = ? and item_group_id = ?';
    $check_d = $dbh->prepare($check_sql);
    $check_d->execute([$reservation_id, $branch, $detail_number, $item_group_id]);
    $check_count = $check_d->rowCount();

    

    if($check_count > 0){
      try{
        $dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh -> beginTransaction();
        $sql = 'update banquet_charges set 
          date = ?,
          reservation_name = ?,
          main_room_id = ?,
          package_category = ?,
          package_cat_name = ?,
          package_id = ?,
          package_name = ?,
          item_group_id = ?,
          item_group_name = ?,
          item_id = ?,
          item_name = ?,
          item_gene_id = ?,
          unit_price = ?,
          qty = ?,
          amount_gross = ?,
          amount_net = ?,
          service_fee = ?,
          tax = ?,
          discount_name = ?,
          discount_rate = ?,
          discount_amount = ?,
          modified = now(),
          modified_by = "csvdata"
          where reservation_id = ? and branch = ? and detail_number = ? and item_group_id = ?';
        $stmt = $dbh->prepare($sql);
        $stmt->execute([
          $date,
          $reservation_name,
          $main_room,
          $package_category,
          $package_cat_name,
          $package_id,
          $package_name,
          $item_group_id,
          $item_group_name,
          $item_id,
          $item_name,
          $item_gene_id,
          $unit_price,
          $qty,
          $amount_gross,
          $amount_net,
          $service_fee,
          $tax,
          $discount_name,
          $discount_rate,
          $discount_amount,
          $reservation_id,
          $branch,
          $detail_number,
          $item_group_id
        ]);
        $dbh -> commit();
      }catch(PDOException $e){
        $dbh -> rollBack();
        echo 'Error: ' . $e->getMessage();
      }
    }else{
      try{
        $dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh -> beginTransaction();
        $sql = 'insert into banquet_charges (
          reservation_id,
          date,
          branch,
          detail_number,
          reservation_name,
          main_room_id,
          package_category,
          package_cat_name,
          package_id,
          package_name,
          item_group_id,
          item_group_name,
          item_id,
          item_name,
          item_gene_id,
          unit_price,
          qty,
          amount_gross,
          amount_net,
          service_fee,
          tax,
          discount_name,
          discount_rate,
          discount_amount,
          added,
          modified,
          modified_by) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,now(),now(),?)';
        $stmt = $dbh->prepare($sql);
        $stmt->execute([
          $reservation_id,
          $date,
          $branch,
          $detail_number,
          $reservation_name,
          $main_room,
          $package_category,
          $package_cat_name,
          $package_id,
          $package_name,
          $item_group_id,
          $item_group_name,
          $item_id,
          $item_name,
          $item_gene_id,
          $unit_price,
          $qty,
          $amount_gross,
          $amount_net,
          $service_fee,
          $tax,
          $discount_name,
          $discount_rate,
          $discount_amount,
          'csvdata'
        ]);
        $dbh -> commit();
      }catch(PDOException $e){
        $dbh -> rollBack();
        echo 'Error: ' . $e->getMessage();
      }
    }
  }
  $i++;
}

?>