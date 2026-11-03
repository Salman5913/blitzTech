-- Dine-in Invoice Voucher - restaurant table orders on the POS screen with a Table No (same as OIV: Sales class, data saved in sl_sales / sl_sales_data)
INSERT INTO vt_voucher_type (id, name, abbr, voucher_class, is_active)
SELECT 113, 'DIV', 'Dine-in Invoice Voucher', 'Sales', 'Y'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM vt_voucher_type WHERE id = 113);

-- WhatsApp Invoice Voucher - orders taken on WhatsApp, POS screen (same as OIV)
INSERT INTO vt_voucher_type (id, name, abbr, voucher_class, is_active)
SELECT 114, 'WHIV', 'WhatsApp Invoice Voucher', 'Sales', 'Y'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM vt_voucher_type WHERE id = 114);

-- Link with Sales module (same as SIV / OIV) so they appear in user group roles
INSERT INTO cp_company_module_app_areas (company_module_id, app_area_id, voucher_type_id)
SELECT cm.id, NULL, vt.id
FROM cp_company_module cm, vt_voucher_type vt
WHERE cm.module = 'Sales'
  AND vt.id IN (113, 114)
  AND NOT EXISTS (SELECT 1 FROM cp_company_module_app_areas WHERE company_module_id = cm.id AND voucher_type_id = vt.id);

-- Table No of a Dine-in Invoice (kept apart from sl_sales so the Sales save columns stay the same)
CREATE TABLE IF NOT EXISTS sl_sales_dine_in (
    sales_id INT NOT NULL PRIMARY KEY,
    table_no VARCHAR(20) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Groups that can use the POS Order Invoice get Dine-in and WhatsApp Invoice too (same edit right)
INSERT INTO sh_grouproles (group_id, area_id, voucher_type_id, voucher_type_is_edit, pivot_table_id)
SELECT gr.group_id, NULL, vt.id, gr.voucher_type_is_edit, NULL
FROM sh_grouproles gr, vt_voucher_type vt
WHERE gr.voucher_type_id = 110
  AND vt.id IN (113, 114)
  AND NOT EXISTS (SELECT 1 FROM sh_grouproles x WHERE x.group_id = gr.group_id AND x.voucher_type_id = vt.id);
