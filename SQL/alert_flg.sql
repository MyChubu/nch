SELECT
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  MIN(`status`) AS `status`,
  `sales_category_id`,
  `sales_category_name`,
  `reservation_type_code`,
  `reservation_type`,
  `reservation_type_name`,
  `agent_id`,
  `agent_name`,
  `agent_short`,
  `agent_name2`,
  MAX(`people`) AS `people`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`,
  `pic_id`,
  `pic`,
  `d_created`,
  `d_decided`,
  `d_tentative`,
  `due_date`,
  `cancel_date`,
  MAX(`reservation_sales_diff`) AS `reservation_sales_diff`,
  MAX(`due_over_flg`) AS `due_over_flg`

FROM `view_monthly_new_reservation3`
WHERE `reservation_date` >= CURDATE()
GROUP BY
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  `sales_category_id`,
  `sales_category_name`,
  `reservation_type_code`,
  `reservation_type`,
  `reservation_type_name`,
  `agent_id`,
  `agent_name`,
  `agent_short`,
  `agent_name2`,
  `pic_id`,
  `pic`,
  `d_created`,
  `d_decided`,
  `d_tentative`,
  `due_date`,
  `cancel_date`,
  `reservation_sales_diff`,
  `due_over_flg`
HAVING
     SUM(`net`) = 0
  OR MAX(`people`) = 0
  OR MAX(`reservation_sales_diff`) = 1
  OR MAX(`due_over_flg`) = 1
ORDER BY
  `reservation_date`,
  `reservation_id`