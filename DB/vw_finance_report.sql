select 
	`ms_order`.`id` AS `id`,
	date_format(`ms_order`.`ETD`,'%y') AS `yr`,
	date_format(`ms_order`.`ETD`,'%m') AS `mn`,
	date_format(`ms_order`.`ETD`,'%d') AS `dy`,
	`ms_order`.`companyName` AS `companyName`,
	`ms_order`.`verificationNumber` AS `verificationNumber`,
	`ms_order`.`invoiceNumber` AS `invoiceNumber`,
	`ms_order`.`clientName` AS `clientName`,
	NULL AS `declareNumber`,
	`ms_order`.`CD` AS `CD`,
	`vw_active_product`.`customerChsAbbr` AS `customerChsAbbr`,
	`vw_active_product`.`customerCode` AS `customerCode`,
	`vw_active_product`.`factoryPriceRMB` AS `factoryPriceRMB`,
	`vw_active_product`.`factoryPriceUSD` AS `factoryPriceUSD`,
	`ms_order`.`salesQuantity` AS `salesQuantity`,
	`vw_active_product`.`factoryPriceRMB` * `ms_order`.`salesQuantity` AS `amount`,
	`vw_active_product`.`factoryPriceUSD` * `ms_order`.`salesQuantity` AS `usdAmount`,
	`rl_hs_rate`.`taxRefundRate` AS `taxRefundRate`,
	if(`vw_active_product`.`factoryPriceRMB` > 0,`vw_active_product`.`factoryPriceRMB` * `ms_order`.`salesQuantity` / 1.16 * `rl_hs_rate`.`taxRefundRate`,0) AS `taxRefund`,
	`vw_active_product`.`supplier` AS `supplier`,
	`ms_order`.`salesQuantity` / `vw_active_product`.`numberInOuterBox` * `vw_active_product`.`outerBoxNetWeight` AS `netWeight`,
	'个' AS `declareUnit`,
	concat(`ms_order`.`PI`,'-',`ms_order`.`Number`) AS `PINumber`,
	`vw_active_product`.`declareUnitPriceUSD` AS `declareUnitPriceUSD`,
	`ms_order`.`salesQuantity` * `vw_active_product`.`declareUnitPriceUSD` AS `declareAmount`,
	format(`ms_exchange_rate`.`exchangeRate`,4) AS `xrate`,
	format(`ms_exchange_rate`.`exchangeRate`,4) * (`ms_order`.`salesQuantity` * `vw_active_product`.`declareUnitPriceUSD`) - if(`vw_active_product`.`factoryPriceRMB` > 0,`vw_active_product`.`factoryPriceRMB` * `ms_order`.`salesQuantity`,`vw_active_product`.`factoryPriceUSD` * `ms_order`.`salesQuantity` * format(`ms_exchange_rate`.`exchangeRate`,4)) + if(`vw_active_product`.`factoryPriceRMB` > 0,`vw_active_product`.`factoryPriceRMB` * `ms_order`.`salesQuantity` / 1.16 * `rl_hs_rate`.`taxRefundRate`,0) AS `profit`,
	(format(`ms_exchange_rate`.`exchangeRate`,4) * (`ms_order`.`salesQuantity` * `vw_active_product`.`declareUnitPriceUSD`) - if(`vw_active_product`.`factoryPriceRMB` > 0,`vw_active_product`.`factoryPriceRMB` * `ms_order`.`salesQuantity`,`vw_active_product`.`factoryPriceUSD` * `ms_order`.`salesQuantity` * format(`ms_exchange_rate`.`exchangeRate`,4)) + if(`vw_active_product`.`factoryPriceRMB` > 0,`vw_active_product`.`factoryPriceRMB` * `ms_order`.`salesQuantity` / 1.16 * `rl_hs_rate`.`taxRefundRate`,0)) / (`ms_order`.`salesQuantity` * `vw_active_product`.`declareUnitPriceUSD`) / format(`ms_exchange_rate`.`exchangeRate`,4) AS `profitRate`,
	`ms_order`.`statusCode` AS `statusCode`,
	`rl_order_finance`.`FileLink` AS `FileLink`,
	`ms_order`.`ETD` AS `ETD` 
from ((((`ms_order` 
		left join `vw_active_product` on(`ms_order`.`clientName` = `vw_active_product`.`clientName` and `ms_order`.`CD` = `vw_active_product`.`CD`)) 
		left join `rl_hs_rate` on(`vw_active_product`.`customerCode` = `rl_hs_rate`.`customerCode`)) 
		left join `ms_exchange_rate` on(concat(date_format(`ms_order`.`ETD`,'%Y'),date_format(`ms_order`.`ETD`,'%m')) = `ms_exchange_rate`.`dateString`)) 
		left join `rl_order_finance` on(`ms_order`.`id` = `rl_order_finance`.`orderId`))