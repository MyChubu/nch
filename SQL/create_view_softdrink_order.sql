
CREATE VIEW `view_softdrink_order` AS
SELECT
`S`.`banquet_schedule_id` AS `sche_id`,
`S`.`status` AS `status`,
`S`.`status_name` AS `status_name`,
`S`.`reservation_id` AS `reservation_id`,
`S`.`branch` AS `branch`,
`S`.`reservation_name` AS `reservation_name`,
`S`.`date` AS `date`,
`S`.`room_id` AS `room_id`,
`R`.`name` AS `room_name`,
`S`.`purpose_id` AS `purpose_id`,
`P`.`banquet_category_id` AS `banquet_category_id`,
`BC`.`banquet_category_name` AS `banquet_category_name`,
`S`.`due_date` AS `due_date`,
`C`.`item_gene_id` AS `item_gene_id`,
`C`.`item_name` AS `item_name`,
`C`.`qty` AS `qty`,
`C`.`unit_price` AS `unit_price`,
`C`.`amount_gross` AS `gross`,
`C`.`amount_net` AS `net`,
`S`.`pic` AS `pic`,
`S`.`pic_id` AS `pic_id`,
`S`.`additional_sales` AS `additional_sales`,
`S`.`agent_id` AS `agent_id`,
`AG`.`agent_group` AS `agent_name`,
`AG`.`agent_group_short` AS `agent_short`,
`S`.`agent_name` AS `agent_name2`,
`S`.`reserver` AS `reserver`,
`S`.`cancel_date` AS `cancel_date`,
`S`.`nehops_d_created` AS `d_created`,
`S`.`nehops_mod_date` AS `d_mod`,
`S`.`nehops_d_decided` AS `d_decided`,
`S`.`nehops_d_tentative` AS `d_tentative`,
`S`.`nehops_edited` AS `d_edited`
FROM (
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
  ) left join `banquet_agents` `AG` on(
    (`S`.`agent_id` = `AG`.`agent_id`)
  )
)
WHERE `S`.`banquet_schedule_id` IS NOT NULL
AND `S`.`status` IN (1,2)
AND `C`.`item_gene_id` IN ('B02-0007','B02-0008','M10-0014','M12-0001')

GROUP BY `S`.`date`,
`S`.`room_id`
ORDER BY
`S`.`date`,
`S`.`start`;