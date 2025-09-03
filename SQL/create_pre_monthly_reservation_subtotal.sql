CREATE VIEW `pre_monthly_reservation_subtotal` AS
select `S`.`banquet_schedule_id` AS `sche_id`,
`S`.`status` AS `status`,
`S`.`status_name` AS `status_name`,
`S`.`reservation_id` AS `reservation_id`,
`S`.`reservation_name` AS `reservation_name`,
`S`.`reservation_date` AS `reservation_date`,
`S`.`branch` AS `branch`,
count(`S`.`reservation_id`) AS `count`,
`S`.`date` AS `date`,
`S`.`room_id` AS `room_id`,
`R`.`name` AS `room_name`,
`S`.`due_date` AS `due_date`,
`S`.`nehops_created` AS `nehops_created`,
`S`.`cancel_date` AS `cancel_date`,
max(`S`.`people`) AS `people`,
sum((`C`.`unit_price` * `C`.`qty`)) AS `subtotal`,
sum(`C`.`amount_gross`) AS `gross`,
sum(`C`.`amount_net`) AS `net`,
sum(`C`.`service_fee`) AS `service_fee`,
sum(`C`.`tax`) AS `tax`,
sum(`C`.`discount_amount`) AS `discount`,
sum(((`C`.`amount_gross` - `C`.`service_fee`) - `C`.`tax`)) AS `ex-ts`,
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
`S`.`agent_id` AS `agent_id`,
`AG`.`agent_group` AS `agent_name`,
`AG`.`agent_group_short` AS `agent_short`,
`S`.`agent_name` AS `agent_name2`,
`S`.`reserver` AS `reserver` from (
  (
    (
      (
        (
          (
            (
              `banquet_schedules` `S` left join `banquet_charges` `C` on(
                (
                  (`C`.`reservation_id` = `S`.`reservation_id`) 
                  and (`C`.`branch` = `S`.`branch`) 
                  and (`C`.`branch` <> 9999) 
                  and (not((`C`.`item_group_id` like 'X%')))
                )
              )
            ) left join `banquet_purposes` `P` on(
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
  ) left join `banquet_agents` `AG` on(
    (`S`.`agent_id` = `AG`.`agent_id`)
  )
)
where (
  (`S`.`banquet_schedule_id` is not null) 
  and (`S`.`status` not in (3,4)) 
  and (`S`.`purpose_id` not in (0,88,94)) 
  and `S`.`additional_sales` = 0
  and (`S`.`reservation_name` <> '朝食会場')
) 
group by `S`.`date`,
`P`.`banquet_category_id`,
`S`.`room_id` order by `S`.`date`,
`P`.`banquet_category_id`