select
`C`.`banquet_charge_id` AS `charge_id`,
`S`.`banquet_schedule_id` AS `sche_id`,
`S`.`status` AS `status`,
`S`.`status_name` AS `status_name`,
`S`.`reservation_id` AS `reservation_id`,
`S`.`branch` AS `branch`,
`C`.`detail_number` AS `detail_number`,
`S`.`reservation_name` AS `reservation_name`,
`S`.`reservation_date` AS `reservation_date`,
`S`.`date` AS `date`,
`S`.`additional_sales` AS `additional_sales`,
`S`.`room_id` AS `room_id`,
`R`.`name` AS `room_name`,
`S`.`purpose_id` AS `purpose_id`,
`S`.`purpose_name` AS `purpose_name`,
`P`.`banquet_purpose_short` AS `purpose_short`,
`P`.`banquet_category_id` AS `banquet_category_id`,
`BC`.`banquet_category_name` AS `banquet_category_name`,
`C`.`package_category` AS `package_category`,
`C`.`package_cat_name` AS `package_cat_name`,
`C`.`package_id` AS `package_id`,
`C`.`package_name` AS `package_name`,
`C`.`item_group_id` AS `item_group_id`,
`C`.`item_group_name` AS `item_group_name`,
`C`.`item_id` AS `item_id`,
`C`.`item_name` AS `item_name`,
`C`.`item_gene_id` AS `item_gene_id`,
`C`.`unit_price` AS `unit_price`,
`C`.`qty` AS `qty`,
`C`.`unit_price` * `C`.`qty` AS `subtotal`,
`C`.`amount_gross` AS `gross`,
`C`.`amount_net` AS `net`,
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
where `S`.`banquet_schedule_id` is not null
AND `S`.`status` = 1
AND `S`.`date` >= '2025-11-01'
AND `S`.`date` < '2025-12-01'
order by `S`.`date`,
`S`.`reservation_id`,
`S`.`branch`,
`C`.`detail_number`