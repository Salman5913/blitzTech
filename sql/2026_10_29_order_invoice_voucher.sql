-- Order Invoice Voucher (POS) - works on Sales class, data saved in sl_sales / sl_sales_data
INSERT INTO vt_voucher_type (id, name, abbr, voucher_class, is_active)
SELECT 110, 'OIV', 'Order Invoice Voucher', 'Sales', 'Y'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM vt_voucher_type WHERE id = 110);

-- Link with Sales module (same as SIV) so it appears in user group roles
INSERT INTO cp_company_module_app_areas (company_module_id, app_area_id, voucher_type_id)
SELECT cm.id, NULL, 110
FROM cp_company_module cm
WHERE cm.module = 'Sales'
  AND NOT EXISTS (SELECT 1 FROM cp_company_module_app_areas WHERE company_module_id = cm.id AND voucher_type_id = 110);
