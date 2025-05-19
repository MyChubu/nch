CREATE VIEW `view_package_charges` 
AS
SELECT
`B`.`reservation_id` AS `reservation_id`,
`B`.`branch` AS `branch`,
`B`.`detail_number` AS `detail_number`,
min(`B`.`date`) AS `Date`,
count(`B`.`reservation_id`) AS `Count`,
`B`.`package_category` AS `package_category`,
`B`.`package_id` AS `package_id`,
`P`.`banquet_pack_id` AS `banquet_pack_id`,
`B`.`package_name` AS `package_name`,
`P`.`package_name` AS `PackageName2`,
`P`.`package_name_short` AS `NameShort`,
sum(`B`.`unit_price`) AS `UnitP`,
max(`B`.`qty`) AS `Qty`,
sum(`B`.`amount_gross`) AS `Gross`,
sum(`B`.`amount_net`) AS `Net`,
sum(`B`.`service_fee`) AS `ServiceFee`,
sum(`B`.`tax`) AS `Tax`,
sum(`B`.`discount_amount`) AS `Discount`
FROM (`banquet_charges` `B` left join `banquet_packages` `P` on(
  ((`B`.`package_category` = `P`.`package_category`) and (`B`.`package_id` = `P`.`package_id`))
  ))
  WHERE
    (
      (`B`.`package_category` <> '') 
      AND (`B`.`package_category` <> ' ') 
      AND (`B`.`package_category` is not null) 
      AND (`B`.`package_category` <> '　') 
      AND (`B`.`branch` <> '9999')
    )
  GROUP BY 
  `B`.`reservation_id`,
  `B`.`branch`,
  `B`.`detail_number`,
  `B`.`package_category`,
  `B`.`package_id`,
  `B`.`package_name` ;