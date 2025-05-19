CREATE VIEW `view_room_schedules_summary` AS
SELECT
DATE_FORMAT(`date`, '%Y-%m') AS `ym`,
 `date`,
`room_id`,
`room_name`,
COUNT(`reservation_id`) AS `count`,

FROM `banquet_schedules`
WHERE 
`status` <> 5
AND `purpose_id` <> 88
AND `purpose_id` <> 94
AND `purpose_id` <> 0
GROUP BY `date`, `room_id`