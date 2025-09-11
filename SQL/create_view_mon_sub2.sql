CREATE OR REPLACE VIEW `salmonbadger2_nchsignage`.`view_monthly_new_reservation2` AS
WITH ranked_reservations AS (
  SELECT
    p.`sche_id`,
    p.`status`,
    p.`status_name`,
    p.`reservation_id`,
    p.`reservation_name`,
    p.`reservation_date`,
    p.`cancel_date`,
    p.`due_date`,
    p.`d_created`,
    p.`d_decided`,
    p.`d_tentative`,
    p.`branch`,
    p.`count`,
    p.`date`,
    p.`room_id`,
    p.`room_name`,
    p.`people`,
    p.`subtotal`,
    p.`gross`,
    p.`net`,
    p.`service_fee`,
    p.`tax`,
    p.`discount`,
    p.`ex-ts`              AS `ex_ts`,             -- 以降扱いやすい別名に
    p.`purpose_id`,
    p.`purpose_name`,
    p.`purpose_short`,
    p.`banquet_category_id`,
    p.`banquet_category_name`,
    p.`sales_dept_id`,
    p.`sales_dept_name2`   AS `sales_dept_name`,
    p.`sales_dept_short`,
    p.`sales_category_id`,
    p.`sales_category_name`,
    p.`pic`,
    p.`pic_id`,
    p.`agent_id`,
    p.`agent_name`,
    p.`agent_short`,
    p.`agent_name2`,
    p.`reserver`,
    ROW_NUMBER() OVER (
      PARTITION BY p.`date`, p.`room_id`
      ORDER BY p.`gross` DESC
    ) AS `rn`
  FROM `salmonbadger2_nchsignage`.`pre_monthly_reservation_subtotal2` AS p
),

-- 売上最大の代表予約のみ
top_res AS (
  SELECT *
  FROM ranked_reservations
  WHERE `rn` = 1
),

-- 予約単位で金額集計（同一予約に複数明細がある想定）
agg AS (
  SELECT
    t1.`status`,
    t1.`date`,
    t1.`room_id`,
    t1.`reservation_id`,
    SUM(t1.`subtotal`)     AS `subtotal`,
    SUM(t1.`gross`)        AS `gross`,
    SUM(t1.`net`)          AS `net`,
    SUM(t1.`service_fee`)  AS `service_fee`,
    SUM(t1.`tax`)          AS `tax`,
    SUM(t1.`discount`)     AS `discount`,
    SUM(t1.`ex-ts`)        AS `ex_ts`
  FROM `salmonbadger2_nchsignage`.`pre_monthly_reservation_subtotal2` AS t1
  GROUP BY
    t1.`status`, t1.`date`, t1.`room_id`, t1.`reservation_id`
)

SELECT
  a.`status`,
  tr.`status_name`,
  a.`date`,
  a.`room_id`,

  tr.`sche_id`,
  tr.`reservation_id`,
  tr.`reservation_date`,
  tr.`cancel_date`,
  tr.`due_date`,
  tr.`d_created`,
  tr.`d_decided`,
  tr.`d_tentative`,
  tr.`branch`,
  tr.`reservation_name`,
  tr.`room_name`,
  tr.`banquet_category_id`,
  tr.`banquet_category_name`,
  tr.`sales_dept_id`,
  tr.`sales_dept_name`,
  tr.`sales_dept_short`,
  tr.`sales_category_id`,
  tr.`sales_category_name`,
  tr.`people`,
  tr.`purpose_id`,
  tr.`purpose_name`,
  tr.`purpose_short`,

  a.`subtotal`,
  a.`gross`,
  a.`net`,
  a.`service_fee`,
  a.`tax`,
  a.`discount`,
  a.`ex_ts`,

  tr.`pic`,
  tr.`pic_id`,
  tr.`agent_id`,
  tr.`agent_name`,
  tr.`agent_short`,
  tr.`agent_name2`,
  tr.`reserver`
FROM agg AS a
JOIN top_res AS tr
  ON  tr.`date` = a.`date`
  AND tr.`room_id` = a.`room_id`
  AND tr.`reservation_id` = a.`reservation_id`;
