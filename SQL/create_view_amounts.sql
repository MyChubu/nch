CREATE VIEW `view_amounts`  AS
select
`C`.`reservation_id` AS `reservation_id`,
`C`.`branch` AS `branch`,
`C`.`date` AS `date`,
sum(`C`.`amount_gross`) AS `gross`,
sum(`C`.`amount_net`) AS `net`,
sum(`C`.`service_fee`) AS `service_fee`,
sum(`C`.`tax`) AS `tax`,
sum(`C`.`discount_amount`) AS `discount`
FROM `banquet_charges` AS `C`
WHERE
  (`C`.`branch` <> 9999)
  AND(`C`.`item_group_id` not like 'X%')
GROUP BY `C`.`reservation_id`, `C`.`branch`, `C`.`date` ;
