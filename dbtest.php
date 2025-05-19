<?php
require_once('common/conf.php');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
  <?php
  #$dsn = 'mysql:dbname=nagoyacrown;host=localhost;charset=utf8';
  #$user = 'root';
  #$password = ''; 

  try{
    #$dbh = new PDO($dsn, $user, $password);
    $dbh = new PDO(DSN, DB_USER, DB_PASS);
    $sql = 'select * from banquet_rooms';
    foreach ($dbh->query($sql) as $row) {
        print($row['banquet_room_id'].',');
        print($row['name']);
        print('<br>');
    }
  }catch (PDOException $e){
      print('Error:'.$e->getMessage());
      die();
  }

  $dbh = null;
  ?>

</body>
</html>