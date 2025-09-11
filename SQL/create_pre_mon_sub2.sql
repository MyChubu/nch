CREATE OR REPLACE VIEW `salmonbadger2_nchsignage`.`pre_monthly_reservation_subtotal2` AS
SELECT
  S.`banquet_schedule_id`                           AS `sche_id`,
  S.`status`                                        AS `status`,
  S.`status_name`                                   AS `status_name`,
  S.`reservation_id`                                AS `reservation_id`,
  S.`reservation_name`                              AS `reservation_name`,
  S.`reservation_date`                              AS `reservation_date`,
  S.`branch`                                        AS `branch`,
  COUNT(S.`reservation_id`)                         AS `count`,             -- 同スケジュール内の行数（必要なら 1 に固定でも可）
  MAX(S.`additional_sales`)                          AS `additional_sales`,  -- WHERE で 0 だが一応 MAX
  DATE_FORMAT(S.`reservation_date`, '%Y-%m')         AS `ym`,
  S.`date`                                          AS `date`,
  S.`room_id`                                       AS `room_id`,
  R.`name`                                          AS `room_name`,
  S.`start`                                         AS `start`,
  S.`end`                                           AS `end`,
  S.`due_date`                                      AS `due_date`,
  S.`cancel_date`                                   AS `cancel_date`,
  S.`nehops_d_created`                              AS `d_created`,
  S.`nehops_d_decided`                              AS `d_decided`,
  S.`nehops_d_tentative`                            AS `d_tentative`,

  MAX(S.`people`)                                   AS `people`,

  COALESCE(SUM(C.`unit_price` * C.`qty`), 0)        AS `subtotal`,
  COALESCE(SUM(C.`amount_gross`), 0)                AS `gross`,
  COALESCE(SUM(C.`amount_net`), 0)                  AS `net`,
  COALESCE(SUM(C.`service_fee`), 0)                 AS `service_fee`,
  COALESCE(SUM(C.`tax`), 0)                         AS `tax`,
  COALESCE(SUM(C.`discount_amount`), 0)             AS `discount`,
  COALESCE(SUM((C.`amount_gross` - C.`service_fee`) - C.`tax`), 0) AS `ex_ts`,

  S.`purpose_id`                                    AS `purpose_id`,
  S.`purpose_name`                                  AS `purpose_name`,
  P.`banquet_purpose_short`                         AS `purpose_short`,
  P.`banquet_category_id`                           AS `banquet_category_id`,
  BC.`banquet_category_name`                        AS `banquet_category_name`,

  S.`pic`                                           AS `pic`,
  S.`pic_id`                                        AS `pic_id`,

  S.`sales_dept_id`                                 AS `sales_dept_id`,
  S.`sales_dept_name`                               AS `sales_dept_name`,
  SD.`sales_dept_name`                              AS `sales_dept_name2`,
  SD.`sales_dept_short`                             AS `sales_dept_short`,
  SD.`category_id`                                  AS `sales_category_id`,
  BC2.`banquet_category_name`                       AS `sales_category_name`,

  S.`agent_id`                                      AS `agent_id`,
  AG.`agent_group`                                  AS `agent_name`,
  AG.`agent_group_short`                            AS `agent_short`,
  S.`agent_name`                                    AS `agent_name2`,
  S.`reserver`                                      AS `reserver`,
   S.`memo` AS `memo`

FROM `salmonbadger2_nchsignage`.`banquet_schedules` AS S
LEFT JOIN `salmonbadger2_nchsignage`.`banquet_charges`   AS C
  ON  C.`reservation_id` = S.`reservation_id`
  AND C.`branch`        = S.`branch`
  AND C.`branch`       <> 9999
  AND C.`item_group_id` NOT LIKE 'X%'
LEFT JOIN `salmonbadger2_nchsignage`.`banquet_purposes`  AS P
  ON S.`purpose_id` = P.`banquet_purpose_id`
LEFT JOIN `salmonbadger2_nchsignage`.`banquet_categories` AS BC
  ON P.`banquet_category_id` = BC.`banquet_category_id`
LEFT JOIN `salmonbadger2_nchsignage`.`banquet_rooms`     AS R
  ON S.`room_id` = R.`banquet_room_id`
LEFT JOIN `salmonbadger2_nchsignage`.`banquet_sales_dept` AS SD
  ON S.`sales_dept_id` = SD.`sales_dept_id`
LEFT JOIN `salmonbadger2_nchsignage`.`banquet_categories` AS BC2
  ON SD.`category_id` = BC2.`banquet_category_id`
LEFT JOIN `salmonbadger2_nchsignage`.`banquet_agents`     AS AG
  ON S.`agent_id` = AG.`agent_id`

WHERE
  S.`banquet_schedule_id` IS NOT NULL
  AND S.`additional_sales` = 0
  AND S.`status` NOT IN (3, 4)             -- ← 3.4 は誤記と判断
  AND S.`purpose_id` NOT IN (0, 88, 94)
  AND S.`reservation_name` <> '朝食会場'

-- スケジュール行ごとに束ねる
GROUP BY
  S.`banquet_schedule_id`;
