SELECT
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  `status`,
  `status_name`,
  `agent_id`,
  `agent_name`,
  MAX(`people`) AS `people`,
  SUM(`gross`) AS `gross`,
  SUM(`net`) AS `net`,
  `pic_id`,
  `pic`,
  `nehops_created`
FROM `view_monthly_new_reservation` WHERE `date` >= '2025-08-01' AND `nehops_created` BETWEEN '2025-08-01 00:00:00.000000' AND '2025-09-01 00:00:00.000000'
GROUP BY
  `reservation_id`,
  `reservation_date`,
  `reservation_name`,
  `status`,
  `status_name`,
  `agent_id`,
  `agent_name`,
  `pic_id`,
  `pic`,
  `nehops_created`
ORDER BY
  `nehops_created`,
  `reservation_id`