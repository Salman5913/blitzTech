-- Stock reports for restaurants: Sales Profit (sales less Stock Use cost) and Raw Material Usage
INSERT INTO sh_app_area (main, sub, inc_in_full_access, description, is_active)
SELECT 'Stock', r.sub, 'Y', '', 'Y'
FROM (SELECT 'Sales Profit' AS sub UNION ALL SELECT 'Raw Material Usage') r
WHERE NOT EXISTS (SELECT 1 FROM sh_app_area a WHERE a.main = 'Stock' AND a.sub = r.sub);

-- Link with Stock module (same as Monthly Profit) so they appear in user group roles
INSERT INTO cp_company_module_app_areas (company_module_id, app_area_id, voucher_type_id)
SELECT cm.id, a.id, NULL
FROM cp_company_module cm, sh_app_area a
WHERE cm.module = 'Stock' AND a.main = 'Stock' AND a.sub IN ('Sales Profit', 'Raw Material Usage')
  AND NOT EXISTS (SELECT 1 FROM cp_company_module_app_areas x WHERE x.company_module_id = cm.id AND x.app_area_id = a.id);

-- Groups that can see Monthly Profit can see the new reports too
INSERT INTO sh_grouproles (group_id, area_id, voucher_type_id, voucher_type_is_edit, pivot_table_id)
SELECT gr.group_id, a.id, NULL, NULL, NULL
FROM sh_grouproles gr, sh_app_area mp, sh_app_area a
WHERE gr.area_id = mp.id AND mp.main = 'Stock' AND mp.sub = 'Monthly Profit'
  AND a.main = 'Stock' AND a.sub IN ('Sales Profit', 'Raw Material Usage')
  AND NOT EXISTS (SELECT 1 FROM sh_grouproles x WHERE x.group_id = gr.group_id AND x.area_id = a.id);
