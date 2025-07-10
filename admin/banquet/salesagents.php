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
require_once('functions/admin_banquet.php');

$month = date('n');
if($month < 4) {
  $nendo = date('Y') - 1;
} else {
  $nendo = date('Y');
}

$this_nendo = $nendo;

if(isset($_REQUEST['nendo']) && $_REQUEST['nendo'] != '') {
  $nendo = $_REQUEST['nendo'];
}

$first_day = $nendo . '-04-01';
$last_day = $nendo + 1 . '-03-31';

$before_nendo = $nendo - 1;
$after_nendo = $nendo + 1;

$sales = array();

$sql = "SELECT
  `sales`.`ym`,
  `sales`.`agent_id`,
  `sales`.`agent_name`,
  `sales`.`agent_short`,
  COUNT(`sales`.`reservation_id`) AS `count`,
  SUM(`sales`.`additional_sales`) AS `additional_sales`,
  SUM(`sales`.`subtotal`) AS `subtotal`,
  SUM(`sales`.`gross`) AS `gross`,
  SUM(`sales`.`net`) AS `net`,
  SUM(`sales`.`service_fee`) AS `service_fee`,
  SUM(`sales`.`tax`) AS `tax`,
  SUM(`sales`.`discount`) AS `discount`,
  SUM(`sales`.`ex-ts`) AS `ex-ts`
FROM (
  SELECT
    `ym`,
    `agent_id`,
    `agent_name`,
    `agent_short`,
    `reservation_id`,
    `additional_sales`,
    SUM(`subtotal`) AS `subtotal`,
    SUM(`gross`) AS `gross`,
    SUM(`net`) AS `net`,
    SUM(`service_fee`) AS `service_fee`,
    SUM(`tax`) AS `tax`,
    SUM(`discount`) AS `discount`,
    SUM(`ex-ts`) AS `ex-ts`
  FROM `view_daily_subtotal`
  WHERE `date` BETWEEN :first_day AND :last_day
    AND `agent_id` > 0
  GROUP BY `ym`,`agent_id`,`agent_name`,`agent_short`,`reservation_id`
  ORDER BY `agent_id`,`ym`
) AS `sales`
GROUP BY `ym`,`agent_id`,`agent_name`,`agent_short`
ORDER BY `agent_id`,`ym`";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':first_day', $first_day, PDO::PARAM_STR);
$stmt->bindValue(':last_day', $last_day, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();
if($count > 0) {
  foreach($results as $result) {
    $sales[] = array(
      'ym' => $result['ym'],
      'agent_id' => $result['agent_id'],
      'agent_name' => $result['agent_name'],
      'agent_short' => $result['agent_short'],
      'count' => $result['count'],
      'additional_sales' => $result['additional_sales'],
      'subtotal' => $result['subtotal'],
      'gross' => $result['gross'],
      'net' => $result['net'],
      'service_fee' => $result['service_fee'],
      'tax' => $result['tax'],
      'discount' => $result['discount'],
      'ex-ts' => $result['ex-ts']
    );
  }
}

$stmt->closeCursor();
$dbh = null;





?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>代理店売上（<?=$nendo ?>年度）</title>
  <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/form.css">
  <link rel="stylesheet" href="css/defect_list.css">
  <script src="https://cdn.skypack.dev/@oddbird/css-toggles@1.1.0"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
  
</head>
<body>
<?php include("header.php"); ?>
<main>
<div class="wrapper">
  <div>
    <form  enctype="multipart/form-data" id="schedate_change">
      <select name="nendo" id="nendo">
        <option value=""></option>
        <?php for($i = 2024; $i<= date('Y') + 1; $i++): ?>
          <option value="<?=$i ?>" <?=($nendo == $i)?"selected":"" ?>><?=$i ?></option>
        <?php endfor; ?>
      </select>年度
      
      <button type="submit">変更</button>
    </form>
    <div id="controller_year">
      <div id="before_year"><a href="?nendo=<?= $before_nendo ?>"><i class="fa-solid fa-arrow-left"></i>前年度</a></div>
      <div id="this_year"><a href="?nendo=<?= $this_nendo ?>">今年度</a></div>
      <div id="after_year"><a href="?nendo=<?= $after_nendo ?>">翌年度<i class="fa-solid fa-arrow-right"></i></a></div>
    
    </div>
  </div>
  <h1><?=$nendo ?>年度 代理店売上</h1>
  <div>


    <div>
      <div>
      <h2>代理店別</h2>
      <div><a href="salesagents-cat.php?nendo=<?=$nendo ?>">カテゴリー別</a></div>
      </div>
      <?php if(sizeof($sales) > 0): ?>
        <?php
          $agnt = "";
          $counter = 0;
          $total_count = 0;
          $total_additional_sales = 0;
          $total_sales_count = 0;
          $total_subtotal = 0;
          $total_gross = 0;
          $total_net = 0;
          $total_service_fee = 0;
          $total_tax = 0;
          $total_discount = 0;
          $total_ex_ts = 0;

          $c_count = 0;
          $c_additional_sales = 0;
          $c_sales_count = 0;
          $c_subtotal = 0;
          $c_gross = 0;
          $c_net = 0;
          $c_service = 0;
          $c_tax = 0;
          $c_discount =0;
          $c_ex_ts =0;
        ?>
        <?php foreach($sales as $row): ?>
          <?php
            $agn = $row['agent_id'];
            $sales_count = $row['count']- $row['additional_sales'];
            
        if($agnt != $agn){
              $agnt = $agn;

              if($counter > 0) {
                ?>
                <tr>
                  <td colspan="4">合計</td>
                  <td><?=number_format($c_sales_count) ?></td>
                  <!--<td><?=number_format($c_additional_sales) ?></td>-->
                  <td><?=number_format($c_subtotal) ?></td>
                  <td><?=number_format($c_gross) ?></td>
                  <td><?=number_format($c_net) ?></td>
                  <td><?=number_format($c_service) ?></td>
                  <td><?=number_format($c_tax) ?></td>
                  <td><?=number_format($c_discount) ?></td>
                  <!--<td><?=number_format($c_ex_ts) ?></td>-->
                </tr>
              </tbody>
              </table>
                </div>
            <?php
                  $c_count = 0;
                  $c_additional_sales = 0;
                  $c_sales_count = 0;
                  $c_subtotal = 0;
                  $c_gross = 0;
                  $c_net = 0;
                  $c_service = 0;
                  $c_tax = 0;
                  $c_discount =0;
                  $c_ex_ts =0;
                }
  
                echo "<div><h4>";
                echo "<a href='salesagent-single.php?agent=".$agnt."&amp;nendo=".$nendo."' title='".$row['agent_name']."'>";
                echo $agnt==0 ?"直接予約など":$row['agent_name'];
                echo "</a>";
                echo "</h4>";
                echo "<table>
                  <thead>
            <tr>
              <th>年月</th>
              <th>代理店ID</th>
              <th>代理店名</th>
              <th>代理店略</th>
              <th>件数</th>
              <!-- <th>追加</th> -->
              <th>&#9312;&nbsp;金額</th>
              <th>&#9313;&nbsp;売上（&#9312; - &#9317;）</th>
              <th>&#9314;&nbsp;純売上（&#9313; - &#9315; - &#9316;）</th>
              <th>&#9315;&nbsp;サービス料</th>
              <th>&#9316;&nbsp;消費税</th>
              <th>&#9317;&nbsp;割引</th>
              <!--<th>税・サ抜</th>-->
            </tr>
          </thead>
          <tbody>";
              }
          ?>
              <tr>
                <td><?=$row['ym'] ?></td>
                <td><?=$row['agent_id'] ?></td>
                <td><?=$row['agent_name'] ?></td>
                <td><?=$row['agent_short'] ?></td>
                <td><?=$sales_count ?></td>
                <!--<td><?=number_format($row['additional_sales']) ?></td>-->
                <td><?=number_format($row['gross']) ?></td>
                <td><?=number_format($row['net']) ?></td>
                <td><?=number_format($row['service_fee']) ?></td>
                <td><?=number_format($row['tax']) ?></td>
                <td><?=number_format($row['discount']) ?></td>
                <td><?=number_format($row['ex-ts']) ?></td>
              </tr>
              <?php
                $total_count += $row['count'];
                $total_additional_sales += $row['additional_sales'];
                $total_sales_count += $sales_count;
                $total_subtotal += $row['subtotal'];
                $total_gross += $row['gross'];
                $total_net += $row['net'];
                $total_service_fee += $row['service_fee'];
                $total_tax += $row['tax'];
                $total_discount += $row['discount'];
                $total_ex_ts += $row['ex-ts'];

                $c_count += $row['count'];
                $c_additional_sales += $row['additional_sales'];
                $c_sales_count += $sales_count;
                $c_subtotal += $row['subtotal'];
                $c_gross += $row['gross'];
                $c_net += $row['net'];
                $c_service += $row['service_fee'];
                $c_tax += $row['tax'];
                $c_discount += $row['discount'];
                $c_ex_ts += $row['ex-ts'];
                $counter++;
              ?>
            <?php endforeach; ?>
            <tr>
              <td colspan="4">合計</td>
              <td><?=number_format($c_sales_count) ?></td>
              <!--<td><?=number_format($c_additional_sales) ?></td>-->
              <td><?=number_format($c_subtotal) ?></td>
              <td><?=number_format($c_gross) ?></td>
              <td><?=number_format($c_net) ?></td>
              <td><?=number_format($c_service) ?></td>
              <td><?=number_format($c_tax) ?></td>
              <td><?=number_format($c_discount) ?></td>
              <!--<td><?=number_format($c_ex_ts) ?></td>-->
            </tr>
          </tbody>
        </table>
        </div>
        
        <div>
            <h4>合計</h4>
            <table>
              <thead>
                <tr>
                <th>年月</th>
                <th>代理店ID</th>
                <th>代理店名</th>
                <th>代理店略</th>
                <th>件数</th>
                <!--<th>追加</th>-->
                <th>&#9312;&nbsp;金額</th>
                <th>&#9313;&nbsp;売上（&#9312; - &#9317;）</th>
                <th>&#9314;&nbsp;純売上（&#9313; - &#9315; - &#9316;）</th>
                <th>&#9315;&nbsp;サービス料</th>
                <th>&#9316;&nbsp;消費税</th>
                <th>&#9317;&nbsp;割引</th>
                <!--<th>税・サ抜</th>-->
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="4">合計</td>
                  <td><?=number_format($total_sales_count) ?></td>
                  <!--<td><?=number_format($total_additional_sales) ?></td>-->
                  <td><?=number_format($total_subtotal) ?></td>
                  <td><?=number_format($total_gross) ?></td>
                  <td><?=number_format($total_net) ?></td>
                  <td><?=number_format($total_service_fee) ?></td>
                  <td><?=number_format($total_tax) ?></td>
                  <td><?=number_format($total_discount) ?></td>
                  <!--<td><?=number_format($total_ex_ts) ?></td>-->
                </tr>
              </tbody>
            </table>
          </div>

      <?php else: ?>
        <p>売上データはありません。</p>
      <?php endif; ?>
    </div>




  </div>
  
</div>
<?php include("aside.php"); ?>
</main>
<?php include("footer.php"); ?>

</body>
</html>