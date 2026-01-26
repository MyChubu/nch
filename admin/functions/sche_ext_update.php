<?php
require_once('../../common/conf.php');
$dbh = new PDO(DSN, DB_USER, DB_PASS);
session_name('_NCH_ADMIN');
session_start();
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : '';
$user_name = $_SESSION['name'];

if (empty($user_id) || empty($user_name)) {
  header('Location: ../login.php?error=2');
  exit;
}else{
  $sql = "SELECT * FROM users WHERE user_id = :user_id AND status = 1";
  $stmt = $dbh->prepare($sql);
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $u_count = $stmt->rowCount();
  if ($u_count < 1) {
    header('Location: ../login.php?error=3');
    exit;
  }
}
$user_mail = $_SESSION['mail'];
$admin = $_SESSION['admin'];

$cat=isset($_POST['cat']) ? intval($_POST['cat']) : 0;
$scheid=isset($_POST['scheid']) ? intval($_POST['scheid']) : 0;
if($scheid == 0){
  //不正アクセス
  header('Location: ../banquet/signage.php');
  exit;
}

if($cat == 1){
  //例外表示変更
  //var_dump($_POST['v']);
  $v=$_POST['v'];
  for($i=0; $i < sizeof($v); $i++){
    $item = $v[$i];
    $er=array();
    $err=0;
    $items=  array('sche_id','date','start','end','event_name','subtitle','enable','id','memo');
    $requireds = array('sche_id','date','start','end','event_name','id');
    foreach($items as $it){
      $item[$it] = isset($item[$it]) ? $item[$it] : '';
      $er[$it] = 0;
    }
    foreach($requireds as $req){
      if($item[$req] == ''){
        $er[$req] = 1;
        $err++;
      }
    }
    // var_dump($er);
    // var_dump($err);
    if($err == 0){
      $start=$item['date'].' '.$item['start'].':00';
      $end=$item['date'].' '.$item['end'].':00';
      $item['enable'] = ($item['enable'] == 'on') ? 1 : 0;
      $item['event_name'] = mb_convert_kana($item['event_name'],'KVas');
      $item['event_name'] = trim($item['event_name']);
      $item['event_name'] = str_replace('///','',$item['event_name']);
      $item['subtitle'] = mb_convert_kana($item['subtitle'],'KVas');
      $item['subtitle'] = trim($item['subtitle']);
      $item['subtitle'] = str_replace('///','',$item['subtitle']);
      $subtitle_f= str_replace([' ','　'], '', $item['subtitle']);
      if($subtitle_f == ''){
        $item['subtitle'] = '';
      }
      $sqla = 'update banquet_ext_sign set
      start = :start,
      end = :end,
      event_name = :event_name,
      subtitle = :subtitle,
      memo = :memo,
      enable = :enable,
      modified = now()
      where banquet_ext_sign_id = :id
      ';
      $stmta = $dbh->prepare($sqla);
      $stmta->bindParam(':start', $start, PDO::PARAM_STR);
      $stmta->bindParam(':end', $end, PDO::PARAM_STR);
      $stmta->bindParam(':event_name', $item['event_name'], PDO::PARAM_STR);
      $stmta->bindParam(':subtitle', $item['subtitle'], PDO::PARAM_STR);
      $stmta->bindParam(':memo', $item['memo'], PDO::PARAM_STR);
      $stmta->bindParam(':enable', $item['enable'], PDO::PARAM_INT);
      $stmta->bindParam(':id', $item['id'], PDO::PARAM_INT);
      $stmta->execute(); 
    }
  }
  header('Location: ../banquet/signage_ext.php?scheid='.$scheid);
  exit;

}elseif($cat == 2){
  //例外表示新規追加
  // var_dump($_POST['n']);
  $n=array();
  $er=array();
  $err=0;
  $items=  array('sche_id','date','start','end','event_name','subtitle','enable','memo');
  $requireds = array('sche_id','date','start','end','event_name');
  foreach($items as $item){
    $n[$item] = isset($_POST['n'][$item]) ? $_POST['n'][$item] : '';
    $er[$item] = 0;
  }
  foreach($requireds as $req){
    if($n[$req] == ''){
      $er[$req] = 1;
      $err++;
    }
  }
  // var_dump($er);
  // var_dump($err);
  if($err == 0){
    $start=$n['date'].' '.$n['start'].':00';
    $end=$n['date'].' '.$n['end'].':00';
  }
  $n['enable'] = ($n['enable'] == '1') ? 1 : 0;
  $n['event_name'] = mb_convert_kana($n['event_name'],'KVas');
  $n['event_name'] = trim($n['event_name']);
  $n['event_name'] = str_replace('///','',$n['event_name']);
  $n['subtitle'] = mb_convert_kana($n['subtitle'],'KVas');
  $n['subtitle'] = trim($n['subtitle']);
  $n['subtitle'] = str_replace('///','',$n['subtitle']);
  $subtitle_f= str_replace([' ','　'], '', $n['subtitle']);
  if($subtitle_f == ''){
    $n['subtitle'] = '';
  }
  $sqln = 'insert into banquet_ext_sign (
  sche_id,
  date,
  start,
  end,
  event_name,
  subtitle,
  memo,
  enable,
  added,
  modified
  ) values (
  :sche_id,
  :date,
  :start,
  :end,
  :event_name,
  :subtitle,
  :memo,
  :enable,
  now(),
  now()
  )';
  $stmtn = $dbh->prepare($sqln);
  $stmtn->bindParam(':sche_id', $n['sche_id'], PDO::PARAM_INT);
  $stmtn->bindParam(':date', $n['date'], PDO::PARAM_STR);
  $stmtn->bindParam(':start', $start, PDO::PARAM_STR);
  $stmtn->bindParam(':end', $end, PDO::PARAM_STR);
  $stmtn->bindParam(':event_name', $n['event_name'], PDO::PARAM_STR);
  $stmtn->bindParam(':subtitle', $n['subtitle'], PDO::PARAM_STR);
  $stmtn->bindParam(':memo', $n['memo'], PDO::PARAM_STR);
  $stmtn->bindParam(':enable', $n['enable'], PDO::PARAM_INT);
  $stmtn->execute(); 

  header('Location: ../banquet/signage_ext.php?scheid='.$scheid.'&sccess=1');
  exit;
}else{
  //不正アクセス
  header('Location: ../banquet/signage.php');
  exit;
}



?>