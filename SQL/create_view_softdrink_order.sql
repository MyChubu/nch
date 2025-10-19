
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
`I`.`name` AS `item_name2`,
`I`.`name_short` AS `item_name_short`,
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
            (
              `banquet_schedules` `S` LEFT JOIN `banquet_charges` `C` ON(
                (
                  (`C`.`reservation_id` = `S`.`reservation_id`) 
                  AND (`C`.`branch` = `S`.`branch`) 
                  AND (`C`.`branch` <> 9999) 
                  AND (NOT((`C`.`item_group_id` LIKE 'X%')))
                )
              )
            ) LEFT JOIN `banquet_purposes` `P` ON(
              (`S`.`purpose_id` = `P`.`banquet_purpose_id`)
            )
          ) LEFT JOIN `banquet_categories` `BC` ON(
            (`P`.`banquet_category_id` = `BC`.`banquet_category_id`)
          )
        ) LEFT JOIN `banquet_rooms` `R` ON(
          (`S`.`room_id` = `R`.`banquet_room_id`)
        )
      ) LEFT JOIN `banquet_items` `I` ON(
        (`C`.`item_gene_id` = `I`.`banquet_item_id`)
      )
  ) LEFT JOIN `banquet_agents` `AG` ON(
    (`S`.`agent_id` = `AG`.`agent_id`)
  )
)
WHERE `S`.`banquet_schedule_id` IS NOT NULL
AND `S`.`status` IN (1,2)
AND `C`.`item_gene_id` IN ('B02-0007','B02-0008','M10-0014','M12-0001')
-- AND `I`.`name_short` IN ('ペット茶','ペット水')
ORDER BY
`S`.`date`,
`S`.`start`;