CREATE VIEW `view_banquet_pic` AS
SELECT
  `reservation_id`,
  `reservation_name`,
  DATE_FORMAT(`date`, '%Y-%m') AS `ym`,
  `date` ,
  COUNT(`room_id`) AS `rooms`,
  MAX(`people`) AS `people`,
  `pic`,
  `status`,
  `status_name`
FROM `banquet_schedules`
WHERE
  `status` < 3
GROUP BY
  `reservation_id`,
  `date`,
  `pic`
ORDER BY
  `date`,
  `reservation_id`;