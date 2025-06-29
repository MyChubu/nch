<?php
function getChartData($nendo){
  $dbh = new PDO(DSN, DB_USER, DB_PASS);
  $month_array = array('4月','5月','6月', '7月','8月','9月','10月','11月','12月','1月','2月','3月');

  $first_day = $nendo . '-04-01';
  $last_day = $nendo + 1 . '-03-31';

  $last_nendo = $nendo - 1;
  $last_first_day = $last_nendo . '-04-01';
  $last_last_day = $last_nendo + 1 . '-03-31';

  $sales = array();
  $sql = "SELECT 
  `sales`.`ym`,
  COUNT(`sales`.`reservation_id`) AS `count`,
  SUM(`sales`.`subtotal`) AS `subtotal`,
  SUM(`sales`.`gross`) AS `gross`,
  SUM(`sales`.`net`) AS `net`
  FROM (
    SELECT 
    `ym`,
    `reservation_id`,
    SUM(`subtotal`) AS `subtotal`,
    SUM(`gross`) AS `gross`,
    SUM(`net`) AS `net`
    FROM `view_daily_subtotal`
    WHERE `date` BETWEEN :first_day AND :last_day
    GROUP BY `ym`,`reservation_id`
    ORDER BY `ym`
  ) AS `sales`
  GROUP BY `ym`
  ORDER BY `ym`";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
  $stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $count = $stmt->rowCount();
  if($count > 0) {
    foreach($results as $result) {
      $tuki = (new DateTime($result['ym'] . '-01'))->format('n月');
      $sales[] = array(
        'ym' => $result['ym'],
        'tuki' => $tuki,
        'count' => $result['count'],
        'subtotal' => $result['subtotal'],
        'gross' => $result['gross'],
        'net' => $result['net']
      );
    }
  }
  $sales_array = array();
  $sales_subtotal_array = array();
  $sales_subtotal=0;
  for($i = 0; $i < 12; $i++) {
    $tuki = $month_array[$i];
    foreach($sales as $sale) {
      if($sale['tuki'] == $tuki) {
        if(is_null($sale['net'])) {
          $sales_array[$i] = 0;
          $sales_subtotal += $sales_array[$i];
          $sales_subtotal_array[$i] = $sales_subtotal;
        }else{
          $sales_array[$i] = round($sale['net']/1000,0);
          $sales_subtotal += $sales_array[$i];
          $sales_subtotal_array[$i] = $sales_subtotal;
        }
        break;
      } else {
        $sales_array[$i] = 0;
        $sales_subtotal += $sales_array[$i];
        $sales_subtotal_array[$i] = $sales_subtotal;
      }

    }
  }

  $last_year_sales = array();
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':first_day', $last_first_day, PDO::PARAM_STR);
  $stmt->bindValue(':last_day', $last_last_day, PDO::PARAM_STR);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $count = $stmt->rowCount();

  if($count > 0) {
    foreach($results as $result) {
      $tuki = (new DateTime($result['ym'] . '-01'))->format('n月');
      $last_year_sales[] = array(
        'ym' => $result['ym'],
        'tuki' => $tuki,
        'count' => $result['count'],
        'subtotal' => $result['subtotal'],
        'gross' => $result['gross'],
        'net' => $result['net']
      );
    }
  }
  $last_year_sales_array = array();
  $last_year_sales_subtotal_array = array();
  $sales_subtotal=0;
  for($i = 0; $i < 12; $i++) {  
    $tuki = $month_array[$i];
    foreach($last_year_sales as $sale) {
      if($sale['tuki'] == $tuki) {
        if(is_null($sale['net'])) {
          $last_year_sales_array[$i] = 0;
          $sales_subtotal += $last_year_sales_array[$i];
          $last_year_sales_subtotal_array[$i] = $sales_subtotal;
        }else{
          $last_year_sales_array[$i] = round($sale['net']/1000,0);
          $sales_subtotal += $last_year_sales_array[$i];
          $last_year_sales_subtotal_array[$i] = $sales_subtotal;
        }
        break;
      } else {
        $last_year_sales_array[$i] = 0;
        $sales_subtotal += $last_year_sales_array[$i];
        $last_year_sales_subtotal_array[$i] = $sales_subtotal;
      }
    }
  }
  $this_nendo_sales =array();
  $this_determined_sales = array();
  $this_tentative_sales = array();
  $this_other_sales = array();
  $subtotal= 0;
  $determ=0;
  $this_nendo_subtotal = array();
  $this_nendo_determined_subtotal = array();
  $last_nendo_sales = array();
  $last_determined_sales = array();
  $last_nendo_subtotal = array();
  $lastsub=0;

  $sql = "SELECT 
    `sales`.`ym`,
    `sales`.`status`,
    COUNT(`sales`.`reservation_id`) AS `count`,
    SUM(`sales`.`gross`) AS `gross`,
    SUM(`sales`.`net`) AS `net`
    FROM (
      SELECT 
      `ym`,
      `reservation_id`,
      `status`,
      SUM(`gross`) AS `gross`,
      SUM(`net`) AS `net`
      FROM `view_daily_subtotal`
      WHERE `date` BETWEEN :first_day AND :last_day
      GROUP BY `ym`,`reservation_id`,  `status`
      ORDER BY `ym`
    ) AS `sales`
    GROUP BY `ym`, `status`
    ORDER BY `ym`";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
  $stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach($results as $result) {
    $tuki = (new DateTime($result['ym'] . '-01'))->format('n月');
    $this_nendo_sales[] = array(
      'ym' => $result['ym'],
      'tuki' => $tuki,
      'status' => $result['status'],
      'count' => $result['count'],
      'gross' => $result['gross'],
      'net' => $result['net']
    );
  }

  $sql = "SELECT 
    `sales`.`ym`,
    COUNT(`sales`.`reservation_id`) AS `count`,
    SUM(`sales`.`gross`) AS `gross`,
    SUM(`sales`.`net`) AS `net`
    FROM (
      SELECT 
      `ym`,
      `reservation_id`,
      SUM(`gross`) AS `gross`,
      SUM(`net`) AS `net`
      FROM `view_daily_subtotal`
      WHERE `date` BETWEEN :first_day AND :last_day
      AND `status` IN (1,2,3) 
      GROUP BY `ym`,`reservation_id`
      ORDER BY `ym`
    ) AS `sales`
    GROUP BY `ym`
    ORDER BY `ym`";

  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':first_day', $last_first_day, PDO::PARAM_STR);
  $stmt->bindValue(':last_day', $last_last_day, PDO::PARAM_STR);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach($results as $result) {
    $tuki = (new DateTime($result['ym'] . '-01'))->format('n月');
    $last_nendo_sales[] = array(
      'ym' => $result['ym'],
      'tuki' => $tuki,
      'count' => $result['count'],
      'gross' => $result['gross'],
      'net' => $result['net']
    );
  }

  for($i = 0; $i < 12; $i++) {
    $tuki = $month_array[$i];
    $this_determined_sales[$i] = 0;
    $this_tentative_sales[$i] = 0;
    $this_other_sales[$i] = 0;
    foreach($this_nendo_sales as $sale) {
      if($sale['tuki'] == $tuki) {
        $ym = explode('-', $sale['ym']);
        $cyear = $ym[0];
        $cmonth = (int)$ym[1];
        if($cmonth < 4) {
          $c_nendo = $cyear - 1;
        } else {
          $c_nendo = $cyear;
        }
        if($c_nendo != $nendo) {
          continue; // Skip if not the current fiscal year
        }
        if($sale['status'] == 1) {
          if(isset($sale['net'])) {
            $this_determined_sales[$i] = round($sale['net']/1000,0);
          }
          $subtotal += $this_determined_sales[$i];
          $determ += $this_determined_sales[$i];
          $this_nendo_subtotal[$i] = $subtotal;
          $this_nendo_determined_subtotal[$i] = $determ;
        }elseif($sale['status'] == 2) {
          if(isset($sale['net'])) {
            $this_tentative_sales[$i] = round($sale['net']/1000,0);
          }
          $subtotal += $this_tentative_sales[$i];
          $this_nendo_subtotal[$i] = $subtotal;
        }elseif($sale['status'] == 3) {
          if(isset($sale['net'])) {
            $this_other_sales[$i] = round($sale['net']/1000,0);
          }
          $subtotal += $this_other_sales[$i];
          $this_nendo_subtotal[$i] = $subtotal;
        }
      }
    }
    if(!isset($this_nendo_subtotal[$i])) {
      $this_nendo_subtotal[$i] = $subtotal;
    }
    if(!isset($this_nendo_determined_subtotal[$i])) {
      $this_nendo_determined_subtotal[$i] = $determ;
    }
  }
  for($i = 0; $i < 12; $i++) {
    $tuki = $month_array[$i];
    $last_determined_sales[$i] = 0;
    foreach($last_nendo_sales as $sale) {
      if($sale['tuki'] == $tuki) {
        $ym = explode('-', $sale['ym']);
        $cyear = $ym[0];
        $cmonth = (int)$ym[1];
        if($cmonth < 4) {
          $c_nendo = $cyear - 1;
        } else {
          $c_nendo = $cyear;
        }
        if($c_nendo != $last_nendo) {
          continue; // Skip if not the last fiscal year
        }
        if(isset($sale['net'])) {
          $last_determined_sales[$i] = round($sale['net']/1000,0);
        }
        $lastsub += $last_determined_sales[$i];
        $last_nendo_subtotal[$i] = $lastsub;
      }
    }
    if(!isset($last_nendo_subtotal[$i])) {
      $last_nendo_subtotal[$i] = $lastsub;
    }
  }

  //エージェント・直販比率
  $direct = 0;
  $agent = 0;
  $d_count = 0;
  $a_count = 0;
  $da_total=0;
  $agents= array();
  $agent_sales = array();
  $agent_count = array();
  $sql="SELECT
    `S`.`agent_id`,
    `S`.`agent_name`,
    COUNT(`S`.`reservation_id`) AS `count`,
    SUM(`S`.`gross`) AS `gross`,
    SUM(`S`.`net`) AS `net`
    FROM(
      SELECT 
      `agent_id`,
      `agent_name`,
      `reservation_id`,
      SUM(`gross`) AS `gross`,
      SUM(`net`) AS `net`
      FROM `view_daily_subtotal`
      WHERE `date` BETWEEN :firsr_day AND :last_day
      GROUP BY `reservation_id`
      ORDER BY `gross` DESC) AS `S`
    GROUP BY `agent_id`
    ORDER BY `gross` DESC";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':firsr_day', $first_day, PDO::PARAM_STR);
  $stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach($results as $result) {
    if($result['agent_id'] == 0 ) {
      $direct += $result['net'];
      $d_count += $result['count'];
    } else {
      $agent += $result['net'];
      $a_count += $result['count'];
      array_push($agents, $result['agent_name']);
      array_push($agent_sales, $result['net']);
      array_push($agent_count, $result['count']);
    }
    $da_total += $result['net'];
  }
  $d_a=array($direct, $agent);
  $d_a_count = array($d_count, $a_count);

  //売上カテゴリー別
  $category_sales = array();
  $category_sales_counts = array();
  $sql = "SELECT 
    `sales`.`ym`,
    `sales`.`sales_category_id`,
    `sales`.`sales_category_name`,
    COUNT(`reservation_id`) AS `count`,
    SUM(`sales`.`additional_sales`) AS `additional_sales`,
    SUM(`subtotal`) AS `subtotal`,
    SUM(`gross`) AS `gross`,
    SUM(`net`) AS `net`
  FROM(
    SELECT 
      `ym`,
      `sales_category_id`,
      `sales_category_name`,
      `reservation_id`,
      `additional_sales`,
      SUM(`subtotal`) AS `subtotal`,
      SUM(`gross`) AS `gross`,
      SUM(`net`) AS `net`
    FROM `view_daily_subtotal`
    WHERE `date` BETWEEN :first_day AND :last_day
    AND `sales_category_id` IS NOT NULL
    GROUP BY `ym`,`sales_category_id`,`sales_category_name`,`reservation_id`
    ORDER BY `sales_category_id`,`ym`
  ) AS `sales`
  GROUP BY `ym`,`sales_category_id`,`sales_category_name`
  ORDER BY `sales_category_id`,`ym`";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
  $stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $count = $stmt->rowCount();
  if($count > 0) {
    foreach($results as $result) {
      $tuki = (new DateTime($result['ym'] . '-01'))->format('n月');
      $category_sales[] = array(
        'ym' => $result['ym'],
        'tuki' => $tuki,
        'sales_category_id' => $result['sales_category_id'],
        'sales_category_name' => $result['sales_category_name'],
        'count' => $result['count'] - $result['additional_sales'],
        'original_count' => $result['count'],
        'additional_sales' => $result['additional_sales'],
        'subtotal' => $result['subtotal'],
        'gross' => $result['gross'],
        'net' => $result['net']
      );
    }
  }
  for($i = 0; $i < 12; $i++) {
  }







  $data=array(
    'nendo' => $nendo,
    'last_nendo' => $last_nendo,
    'months' => $month_array,
    'sales' => $sales_array,
    'last_year_sales' => $last_year_sales_array,
    'sales_subtotal' => $sales_subtotal_array,
    'last_year_sales_subtotal' => $last_year_sales_subtotal_array,
    'this_determined_sales' => $this_determined_sales,
    'this_tentative_sales' => $this_tentative_sales,
    'this_other_sales' => $this_other_sales,
    'this_nendo_subtotal' => $this_nendo_subtotal,
    'this_nendo_determined_subtotal' => $this_nendo_determined_subtotal,
    'last_determined_sales' => $last_determined_sales,
    'last_nendo_subtotal' => $last_nendo_subtotal,
    'agents' => $agents,
    'agent_sales' => $agent_sales,
    'agent_count' => $agent_count,
    'd_a' => $d_a,
    'd_a_count' => $d_a_count,
    'category_sales' => $category_sales,
  );
  return $data;
}
?>