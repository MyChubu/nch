CREATE VIEW `pre_monthly_reservation_subtotal3` AS
select
  `S`.`banquet_schedule_id` AS `sche_id`,
  `S`.`status` AS `status`,
  `S`.`status_name` AS `status_name`,
  `S`.`reservation_id` AS `reservation_id`,
  `S`.`reservation_name` AS `reservation_name`,
  `S`.`reservation_date` AS `reservation_date`,
  `S`.`branch` AS `branch`,
  count(`S`.`reservation_id`) AS `count`,
  max(`S`.`additional_sales`) AS `additional_sales`,
  date_format(`S`.`reservation_date`,'%Y-%m') AS `ym`,
  `S`.`date` AS `date`,
  `S`.`room_id` AS `room_id`,
  `R`.`name` AS `room_name`,
  `S`.`start` AS `start`,
  `S`.`end` AS `end`,
  `S`.`due_date` AS `due_date`,
  `S`.`cancel_date` AS `cancel_date`,
  `S`.`nehops_d_created` AS `d_created`,
  `S`.`nehops_d_decided` AS `d_decided`,
  `S`.`nehops_d_tentative` AS `d_tentative`,
  max(`S`.`people`) AS `people`,
  coalesce(sum((`C`.`unit_price` * `C`.`qty`)),0) AS `subtotal`,
  coalesce(sum(`C`.`amount_gross`),0) AS `gross`,
  coalesce(sum(`C`.`amount_net`),0) AS `net`,
  coalesce(sum(`C`.`service_fee`),0) AS `service_fee`,
  coalesce(sum(`C`.`tax`),0) AS `tax`,
  coalesce(sum(`C`.`discount_amount`),0) AS `discount`,
  coalesce(sum(((`C`.`amount_gross` - `C`.`service_fee`) - `C`.`tax`)),0) AS `ex_ts`,
  `S`.`purpose_id` AS `purpose_id`,
  `S`.`purpose_name` AS `purpose_name`,
  `P`.`banquet_purpose_short` AS `purpose_short`,
  `P`.`banquet_category_id` AS `banquet_category_id`,
  `BC`.`banquet_category_name` AS `banquet_category_name`,
  `S`.`pic` AS `pic`,
  `S`.`pic_id` AS `pic_id`,
  `S`.`sales_dept_id` AS `sales_dept_id`,
  `S`.`sales_dept_name` AS `sales_dept_name`,
  `SD`.`sales_dept_name` AS `sales_dept_name2`,
  `SD`.`sales_dept_short` AS `sales_dept_short`,
  `SD`.`category_id` AS `sales_category_id`,
  `BC2`.`banquet_category_name` AS `sales_category_name`,
  `S`.`reservation_type_code` AS `reservation_type_code`,
  `BC3`.`banquet_category_id` AS `reservation_type`,
  `BC3`.`banquet_category_name` AS `reservation_type_name`,
  `S`.`agent_id` AS `agent_id`,
  `AG`.`agent_group` AS `agent_name`,
  `AG`.`agent_group_short` AS `agent_short`,
  `S`.`agent_name` AS `agent_name2`,
  `S`.`reserver` AS `reserver`,
  `S`.`memo` AS `memo`
from
(
  (
    (
      (
        (
          (
            (
              (`banquet_schedules` `S` left join `banquet_charges` `C` on(
                (
                  (`C`.`reservation_id` = `S`.`reservation_id`)
                  and (`C`.`branch` = `S`.`branch`)
                  and (`C`.`branch` <> 9999)
                  and (not((`C`.`item_group_id` like 'X%')))
                )
              ))left join `banquet_purposes` `P` on(
                (`S`.`purpose_id` = `P`.`banquet_purpose_id`)
              )
            ) left join `banquet_categories` `BC` on(
              (`P`.`banquet_category_id` = `BC`.`banquet_category_id`)
            )
          ) left join `banquet_rooms` `R` on(
            (`S`.`room_id` = `R`.`banquet_room_id`)
          )
        ) left join `banquet_sales_dept` `SD` on(
          (`S`.`sales_dept_id` = `SD`.`sales_dept_id`)
        )
      ) left join `banquet_categories` `BC2` on(
        (`SD`.`category_id` = `BC2`.`banquet_category_id`)
      )
    ) left join `banquet_categories` `BC3` on(
      (`S`.`reservation_type_code` = `BC3`.`reservation_type_code`)
    )
  ) left join `banquet_agents` `AG` on(
    (`S`.`agent_id` = `AG`.`agent_id`)
  )
)
where 
  `S`.`banquet_schedule_id` is not null
  and `S`.`additional_sales` = 0
  and `S`.`status` not in (3,4,5)
  and `S`.`purpose_id` not in (88,93,94)
  and `S`.`reservation_name` <> '朝食会場'
  AND `S`.`reservation_name` NOT LIKE '%名古屋クラウンホテル%'
  AND `S`.`reserver` NOT LIKE '%名古屋クラウンホテル%'
  AND `S`.`reserver` NOT LIKE '%堀場産業%'
group by `S`.`banquet_schedule_id`