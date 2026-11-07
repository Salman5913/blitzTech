-- Stock functions knew only SIV (2), SRV (15) and old types 27, 28, 29 as sales.
-- Adds Order (110), Website (112), Dine-in (113) and WhatsApp (114) Invoices; Stock Use (111) shows its description.

DROP FUNCTION IF EXISTS get_stock_used_in_name;
DELIMITER $$
CREATE FUNCTION `get_stock_used_in_name`(in_voucher_type_id int, in_distinct_id int) RETURNS text CHARSET utf8mb4 COLLATE utf8mb4_general_ci
BEGIN
	DECLARE out_description TEXT DEFAULT "";

	IF in_voucher_type_id = 35 OR in_voucher_type_id = 46 THEN
			SELECT p.name INTO out_description FROM prj_stock_requisition sr, prj_project p WHERE sr.project_id = p.id AND sr.distinct_id = in_distinct_id;

	ELSEIF in_voucher_type_id IN (2, 15, 27, 28, 29, 110, 112, 113, 114) THEN
			SELECT a.`name` INTO out_description FROM sl_sales s, acc_accounts a WHERE s.customer_account_id = a.id AND s.distinct_id = in_distinct_id;

	ELSEIF in_voucher_type_id = 1 OR in_voucher_type_id = 16 THEN
			SELECT a.`name` INTO out_description FROM pr_purchase p, acc_accounts a WHERE p.supplier_account_id = a.id AND p.distinct_id = in_distinct_id;

	ELSEIF in_voucher_type_id = 40 THEN
			SELECT a.`name` INTO out_description FROM pr_profile_import p, acc_accounts a WHERE p.supplier_account_id = a.id AND p.distinct_id = in_distinct_id;

	ELSEIF in_voucher_type_id = 111 THEN
			SELECT su.description INTO out_description FROM st_stock_use su WHERE su.distinct_id = in_distinct_id;

	END IF;
	RETURN out_description;
END$$
DELIMITER ;

DROP FUNCTION IF EXISTS get_avg_sales_cost;
DELIMITER $$
CREATE FUNCTION `get_avg_sales_cost`(`in_stock_id` int, in_attribute_id int, in_date_from VARCHAR(255), in_date_till VARCHAR(255)) RETURNS int(11)
BEGIN
	DECLARE avg_cost DOUBLE(11,2) DEFAULT 0;

	SELECT
	SUM(total_cost / total_qty) into avg_cost
FROM (
select
	SUM(available_qty * cost) as total_cost,
	SUM(available_qty) as total_qty
FROM
	(select sum(stl.qty) as available_qty, batch_id, cost from st_stock_ledger stl, st_stock_batch stb where stl.batch_id = stb.id AND stock_id = in_stock_id and attribute_id <=> in_attribute_id and stl.date_time BETWEEN in_date_from and in_date_till and voucher_type_id in (2, 27, 28, 29, 15, 110, 112, 113, 114) group by batch_id) AS tmp
	) as tmp2;


	RETURN avg_cost;
END$$
DELIMITER ;
