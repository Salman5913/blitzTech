-- Sales Reports > Item Selling (sales/report_item_selling.php): access area Sales / Item Selling
INSERT INTO sh_app_area (main, sub, inc_in_full_access, description, is_active)
SELECT 'Sales', 'Item Selling', 'Y', 'Qty of each item sold per date and branch', 'Y'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sh_app_area WHERE main = 'Sales' AND sub = 'Item Selling');

-- Link with Sales module (same as the other sales reports) so it appears in user group roles
INSERT INTO cp_company_module_app_areas (company_module_id, app_area_id, voucher_type_id)
SELECT cm.id, a.id, NULL
FROM cp_company_module cm, sh_app_area a
WHERE cm.module = 'Sales' AND a.main = 'Sales' AND a.sub = 'Item Selling'
  AND NOT EXISTS (SELECT 1 FROM cp_company_module_app_areas WHERE company_module_id = cm.id AND app_area_id = a.id);
