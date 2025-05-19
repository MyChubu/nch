with `ranked_reservations` as (
select 
  `pre_daily_subtotal`.`sche_id` AS `sche_id`,
  `pre_daily_subtotal`.`status` AS `status`,
  `pre_daily_subtotal`.`status` AS `status_name`,
  `pre_daily_subtotal`.`reservation_id` AS `reservation_id`,
  `pre_daily_subtotal`.`reservation_name` AS `reservation_name`,
  `pre_daily_subtotal`.`branch` AS `branch`,
  `pre_daily_subtotal`.`count` AS `count`,
  `pre_daily_subtotal`.`ym` AS `ym`,
  `pre_daily_subtotal`.`date` AS `date`,
  `pre_daily_subtotal`.`room_id` AS `room_id`,
  `pre_daily_subtotal`.`room_name` AS `room_name`,
  `pre_daily_subtotal`.`start` AS `start`,
  `pre_daily_subtotal`.`end` AS `end`,
  `pre_daily_subtotal`.`people` AS `people`,
  `pre_daily_subtotal`.`gross` AS `gross`,
  `pre_daily_subtotal`.`net` AS `net`,
  `pre_daily_subtotal`.`service_fee` AS `service_fee`,
  `pre_daily_subtotal`.`tax` AS `tax`,
  `pre_daily_subtotal`.`discount` AS `discount`,
  `pre_daily_subtotal`.`ex-ts` AS `ex-ts`,
  `pre_daily_subtotal`.`purpose_id` AS `purpose_id`,
  `pre_daily_subtotal`.`purpose_name` AS `purpose_name`,
  `pre_daily_subtotal`.`purpose_short` AS `purpose_short`,
  `pre_daily_subtotal`.`banquet_category_id` AS `banquet_category_id`,
  `pre_daily_subtotal`.`banquet_category_name` AS `banquet_category_name`,
  `pre_daily_subtotal`.`sales_dept_id` AS `sales_dept_id`,
  `pre_daily_subtotal`.`sales_dept_name2` AS `sales_dept_name`,
  `pre_daily_subtotal`.`sales_dept_short` AS `sales_dept_short`,
  `pre_daily_subtotal`.`sales_category_id` AS `sales_category_id`,
  `pre_daily_subtotal`.`sales_category_name` AS `sales_category_name`,
  `pre_daily_subtotal`.`pic` AS `pic`,
  row_number() OVER (
    PARTITION BY 
      `pre_daily_subtotal`.`ym`,
      `pre_daily_subtotal`.`date`,
      `pre_daily_subtotal`.`room_id` 
    ORDER BY `pre_daily_subtotal`.`gross` desc 
  )  AS `rn` 
  from `pre_daily_subtotal`
) select 
  `t1`.`sche_id` AS `sche_id`,
  `t1`.`status` AS `status`,
  `t1`.`status_name` AS `status_name`,
  `t1`.`ym` AS `ym`,
  `t1`.`date` AS `date`,
  `t1`.`room_id` AS `room_id`,
  `t2`.`reservation_id` AS `reservation_id`,
  `t2`.`branch` AS `branch`,
  `t2`.`reservation_name` AS `reservation_name`,
  `t2`.`room_name` AS `room_name`,
  `t2`.`start` AS `start`,
  `t2`.`end` AS `end`,
  `t2`.`banquet_category_id` AS `banquet_category_id`,
  `t2`.`banquet_category_name` AS `banquet_category_name`,
  `t2`.`sales_dept_id` AS `sales_dept_id`,
  `t2`.`sales_dept_name` AS `sales_dept_name`,
  `t2`.`sales_dept_short` AS `sales_dept_short`,
  `t2`.`sales_category_id` AS `sales_category_id`,
  `t2`.`sales_category_name` AS `sales_category_name`,
  `t2`.`people` AS `people`,
  `t2`.`purpose_id` AS `purpose_id`,
  `t2`.`purpose_name` AS `purpose_name`,
  `t2`.`purpose_short` AS `purpose_short`,
  sum(`t1`.`gross`) AS `gross`,
  sum(`t1`.`net`) AS `net`,
  sum(`t1`.`service_fee`) AS `service_fee`,
  sum(`t1`.`tax`) AS `tax`,
  sum(`t1`.`discount`) AS `discount`,
  sum(`t1`.`ex-ts`) AS `ex-ts`,
  `t2`.`pic` AS `pic` 
from (
  `pre_daily_subtotal` `t1` join (
    select 
      `ranked_reservations`.`sche_id` AS `sche_id`,
      `ranked_reservations`.`status` AS `status`,
      `ranked_reservations`.`status_name` AS `status_name`,
      `ranked_reservations`.`reservation_id` AS `reservation_id`,
      `ranked_reservations`.`reservation_name` AS `reservation_name`,
      `ranked_reservations`.`branch` AS `branch`,
      `ranked_reservations`.`count` AS `count`,
      `ranked_reservations`.`ym` AS `ym`,
      `ranked_reservations`.`date` AS `date`,
      `ranked_reservations`.`room_id` AS `room_id`,
      `ranked_reservations`.`room_name` AS `room_name`,
      `ranked_reservations`.`start` AS `start`,
      `ranked_reservations`.`end` AS `end`,
      `ranked_reservations`.`purpose_id` AS `purpose_id`,
      `ranked_reservations`.`purpose_name` AS `purpose_name`,
      `ranked_reservations`.`purpose_short` AS `purpose_short`,
      `ranked_reservations`.`people` AS `people`,
      `ranked_reservations`.`gross` AS `gross`,
      `ranked_reservations`.`net` AS `net`,
      `ranked_reservations`.`service_fee` AS `service_fee`,
      `ranked_reservations`.`tax` AS `tax`,
      `ranked_reservations`.`discount` AS `discount`,
      `ranked_reservations`.`ex-ts` AS `ex-ts`,
      `ranked_reservations`.`banquet_category_id` AS `banquet_category_id`,
      `ranked_reservations`.`banquet_category_name` AS `banquet_category_name`,
      `ranked_reservations`.`sales_dept_id` AS `sales_dept_id`,
      `ranked_reservations`.`sales_dept_name` AS `sales_dept_name`,
      `ranked_reservations`.`sales_dept_short` AS `sales_dept_short`,
      `ranked_reservations`.`sales_category_id` AS `sales_category_id`,
      `ranked_reservations`.`sales_category_name` AS `sales_category_name`,
      `ranked_reservations`.`pic` AS `pic`,
      `ranked_reservations`.`rn` AS `rn` 
    from `ranked_reservations` 
    where (`ranked_reservations`.`rn` = 1 )
  ) `t2` on(
    (
      (`t1`.`ym` = `t2`.`ym`) 
      and (`t1`.`date` = `t2`.`date`) 
      and (`t1`.`room_id` = `t2`.`room_id`)
    )
  )
) group by `t1`.`ym`,`t1`.`status`,`t1`.`date`,`t1`.`room_id`



with `ranked_reservations` as (select `LA06926062-nch`.`pre_daily_subtotal`.`sche_id` AS `sche_id`,`LA06926062-nch`.`pre_daily_subtotal`.`status` AS `status`,`LA06926062-nch`.`pre_daily_subtotal`.`status` AS `status_name`,`LA06926062-nch`.`pre_daily_subtotal`.`reservation_id` AS `reservation_id`,`LA06926062-nch`.`pre_daily_subtotal`.`reservation_name` AS `reservation_name`,`LA06926062-nch`.`pre_daily_subtotal`.`branch` AS `branch`,`LA06926062-nch`.`pre_daily_subtotal`.`count` AS `count`,`LA06926062-nch`.`pre_daily_subtotal`.`ym` AS `ym`,`LA06926062-nch`.`pre_daily_subtotal`.`date` AS `date`,`LA06926062-nch`.`pre_daily_subtotal`.`room_id` AS `room_id`,`LA06926062-nch`.`pre_daily_subtotal`.`room_name` AS `room_name`,`LA06926062-nch`.`pre_daily_subtotal`.`start` AS `start`,`LA06926062-nch`.`pre_daily_subtotal`.`end` AS `end`,`LA06926062-nch`.`pre_daily_subtotal`.`people` AS `people`,`LA06926062-nch`.`pre_daily_subtotal`.`gross` AS `gross`,`LA06926062-nch`.`pre_daily_subtotal`.`net` AS `net`,`LA06926062-nch`.`pre_daily_subtotal`.`service_fee` AS `service_fee`,`LA06926062-nch`.`pre_daily_subtotal`.`tax` AS `tax`,`LA06926062-nch`.`pre_daily_subtotal`.`discount` AS `discount`,`LA06926062-nch`.`pre_daily_subtotal`.`ex-ts` AS `ex-ts`,`LA06926062-nch`.`pre_daily_subtotal`.`purpose_id` AS `purpose_id`,`LA06926062-nch`.`pre_daily_subtotal`.`purpose_name` AS `purpose_name`,`LA06926062-nch`.`pre_daily_subtotal`.`purpose_short` AS `purpose_short`,`LA06926062-nch`.`pre_daily_subtotal`.`banquet_category_id` AS `banquet_category_id`,`LA06926062-nch`.`pre_daily_subtotal`.`banquet_category_name` AS `banquet_category_name`,`LA06926062-nch`.`pre_daily_subtotal`.`pic` AS `pic`,row_number() OVER (PARTITION BY `LA06926062-nch`.`pre_daily_subtotal`.`ym`,`LA06926062-nch`.`pre_daily_subtotal`.`date`,`LA06926062-nch`.`pre_daily_subtotal`.`room_id` ORDER BY `LA06926062-nch`.`pre_daily_subtotal`.`gross` desc )  AS `rn` from `LA06926062-nch`.`pre_daily_subtotal`) select `LA06926062-nch`.`t1`.`sche_id` AS `sche_id`,`LA06926062-nch`.`t1`.`status` AS `status`,`LA06926062-nch`.`t1`.`status_name` AS `status_name`,`LA06926062-nch`.`t1`.`ym` AS `ym`,`LA06926062-nch`.`t1`.`date` AS `date`,`LA06926062-nch`.`t1`.`room_id` AS `room_id`,`t2`.`reservation_id` AS `reservation_id`,`t2`.`branch` AS `branch`,`t2`.`reservation_name` AS `reservation_name`,`t2`.`room_name` AS `room_name`,`t2`.`start` AS `start`,`t2`.`end` AS `end`,`t2`.`banquet_category_id` AS `banquet_category_id`,`t2`.`banquet_category_name` AS `banquet_category_name`,`t2`.`people` AS `people`,`t2`.`purpose_id` AS `purpose_id`,`t2`.`purpose_name` AS `purpose_name`,`t2`.`purpose_short` AS `purpose_short`,sum(`LA06926062-nch`.`t1`.`gross`) AS `gross`,sum(`LA06926062-nch`.`t1`.`net`) AS `net`,sum(`LA06926062-nch`.`t1`.`service_fee`) AS `service_fee`,sum(`LA06926062-nch`.`t1`.`tax`) AS `tax`,sum(`LA06926062-nch`.`t1`.`discount`) AS `discount`,sum(`LA06926062-nch`.`t1`.`ex-ts`) AS `ex-ts`,`t2`.`pic` AS `pic` from (`LA06926062-nch`.`pre_daily_subtotal` `t1` join (select `ranked_reservations`.`sche_id` AS `sche_id`,`ranked_reservations`.`status` AS `status`,`ranked_reservations`.`status` AS `status_name`,`ranked_reservations`.`reservation_id` AS `reservation_id`,`ranked_reservations`.`reservation_name` AS `reservation_name`,`ranked_reservations`.`branch` AS `branch`,`ranked_reservations`.`count` AS `count`,`ranked_reservations`.`ym` AS `ym`,`ranked_reservations`.`date` AS `date`,`ranked_reservations`.`room_id` AS `room_id`,`ranked_reservations`.`room_name` AS `room_name`,`ranked_reservations`.`start` AS `start`,`ranked_reservations`.`end` AS `end`,`ranked_reservations`.`purpose_id` AS `purpose_id`,`ranked_reservations`.`purpose_name` AS `purpose_name`,`ranked_reservations`.`purpose_short` AS `purpose_short`,`ranked_reservations`.`people` AS `people`,`ranked_reservations`.`gross` AS `gross`,`ranked_reservations`.`net` AS `net`,`ranked_reservations`.`service_fee` AS `service_fee`,`ranked_reservations`.`tax` AS `tax`,`ranked_reservations`.`discount` AS `discount`,`ranked_reservations`.`ex-ts` AS `ex-ts`,`ranked_reservations`.`banquet_category_id` AS `banquet_category_id`,`ranked_reservations`.`banquet_category_name` AS `banquet_category_name`,`ranked_reservations`.`pic` AS `pic`,`ranked_reservations`.`rn` AS `rn` from `ranked_reservations` where (`ranked_reservations`.`rn` = 1)) `t2` on(((`LA06926062-nch`.`t1`.`ym` = `t2`.`ym`) and (`LA06926062-nch`.`t1`.`date` = `t2`.`date`) and (`LA06926062-nch`.`t1`.`room_id` = `t2`.`room_id`)))) group by `LA06926062-nch`.`t1`.`ym`,`LA06926062-nch`.`t1`.`status`,`LA06926062-nch`.`t1`.`date`,`LA06926062-nch`.`t1`.`room_id`