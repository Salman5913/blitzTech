-- Branch-wise restaurant website: every branch has its own menu, categories, deals, delivery areas and web orders.
-- Existing rows go to branch 1 (the branch in restaurant_bridge/config.php before this change).

-- Branches the website offers (ERP: Restaurant > Branches)
CREATE TABLE IF NOT EXISTS rs_branches (
    branch_id INT NOT NULL PRIMARY KEY,
    show_on_website TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Branch 1 already runs the website; branch 2 starts hidden until its menu is set up
INSERT IGNORE INTO rs_branches (branch_id, show_on_website, sort_order) VALUES (1, 1, 1), (2, 0, 2);

ALTER TABLE rs_menu_items ADD COLUMN branch_id INT NULL AFTER id;
UPDATE rs_menu_items SET branch_id = 1;
ALTER TABLE rs_menu_items MODIFY branch_id INT NOT NULL, ADD INDEX idx_branch (branch_id);

ALTER TABLE rs_menu_categories ADD COLUMN branch_id INT NULL FIRST;
UPDATE rs_menu_categories SET branch_id = 1;
ALTER TABLE rs_menu_categories MODIFY branch_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (branch_id, stock_category_id);

ALTER TABLE rs_deals ADD COLUMN branch_id INT NULL AFTER id;
UPDATE rs_deals SET branch_id = 1;
ALTER TABLE rs_deals MODIFY branch_id INT NOT NULL, ADD INDEX idx_branch (branch_id);

ALTER TABLE rs_delivery_areas ADD COLUMN branch_id INT NULL AFTER id;
UPDATE rs_delivery_areas SET branch_id = 1;
ALTER TABLE rs_delivery_areas MODIFY branch_id INT NOT NULL, ADD INDEX idx_branch (branch_id);

ALTER TABLE rs_orders ADD COLUMN branch_id INT NULL AFTER id;
UPDATE rs_orders SET branch_id = 1;
ALTER TABLE rs_orders MODIFY branch_id INT NOT NULL, ADD INDEX idx_branch (branch_id);
