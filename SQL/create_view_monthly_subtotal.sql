SELECT
DATE_FORMAT(`S`.`date`, '%Y-%m') AS `ym`,
sum(`C`.`amount_gross`) AS `gross`,
`P`.`banquet_category_id`
FROM `banquet_charges` AS `C`
LEFT JOIN `banquet_schedules` AS `S`
ON (`C`.`reservation_id` = `S`.`reservation_id`) AND (`C`.`branch` =`S`.`branch`)
LEFT JOIN `banquet_purposes` AS `P`
ON `S`.`purpose_id` = `P`.`banquet_purpose_id`
WHERE `S`.`status` <> 5
AND `C`.`branch`<>9999
AND `item_group_id` NOT LIKE 'X%' 
AND (`S`.`purpose_id`<> 88 AND `S`.`purpose_id`<> 94 AND`S`.`purpose_id`<> 0)
GROUP BY `ym` ,`P`.`banquet_category_id`
