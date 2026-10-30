<?php
/**
 * Restaurant website menu (rs_menu_items), managed from the ERP.
 *
 * A dish links to a stock item (erp_stock_id) and, when the item has variations, to one variation
 * (erp_attribute_id); every active variation is its own dish with its own price and stock.
 *
 * Stock items in categories marked "Show on website" (rs_menu_categories) are added to the menu
 * automatically. The same sync runs in restaurant_bridge/api/index.php whenever the website loads the menu.
 *
 * Every branch has its own menu and categories (branch_id). The functions here work on the branch the
 * restaurant pages are on (RestaurantBranch::getCurrentId()).
 */
class RestaurantMenu
{
    /**
     * Everything the ERP can sell: one row per active variation, or one row for an item without variations.
     * Name and price follow the ERP's vw_ac_stock (variation price, else item price).
     */
    const SELLABLE_SQL = "
        SELECT s.id AS stock_id, sa.attribute_id, s.category_id,
               CONCAT(s.name, IFNULL(CONCAT(' - ', csa.name), '')) AS name,
               COALESCE(NULLIF(sa.selling_price, 0), s.sp) AS price
        FROM st_stock s
        LEFT JOIN st_stock_attribute sa ON sa.stock_id = s.id AND sa.is_inactive <> 'Y'
        LEFT JOIN cp_stock_attribute csa ON csa.id = sa.attribute_id";

    /** Branch the restaurant pages work on (picked on the restaurant tabs) */
    static function getWebsiteBranchId()
    {
        return RestaurantBranch::getCurrentId();
    }

    private static function branchSql()
    {
        return escape(self::getWebsiteBranchId(), 'int');
    }

    /** "2::1" (item 2, variation 1) or "2::" (item 2 without variation), like Stock::splitStockIdAttributeId() */
    static function getStockKey($StockId, $AttributeId)
    {
        return empty($StockId) ? '' : $StockId . '::' . $AttributeId;
    }

    static function getSellable($StockKey)
    {
        list($StockId, $AttributeId) = array_pad(Stock::splitStockIdAttributeId($StockKey), 2, '');

        return DBQM::fetch_assoc(DBQM::query("SELECT * FROM (" . self::SELLABLE_SQL . ") x
                                             WHERE x.stock_id = " . escape($StockId, 'int') . " AND x.attribute_id <=> " . escape($AttributeId, 'int', true)));
    }

    /** Adds sellable items matching $Where (on alias x) that aren't on this branch's menu yet */
    private static function addMissingDishes($Joins, $CategorySql, $Where)
    {
        $Branch = self::branchSql();
        DBQM::query("
            INSERT INTO rs_menu_items (branch_id, name, description, price, category, emoji, available, sort_order, erp_stock_id, erp_attribute_id)
            SELECT $Branch, x.name, '', x.price, $CategorySql, '', 1, 1000 + x.stock_id, x.stock_id, x.attribute_id
            FROM (" . self::SELLABLE_SQL . ") x
            $Joins
            WHERE ($Where) AND NOT EXISTS (SELECT 1 FROM rs_menu_items m WHERE m.branch_id = $Branch AND m.erp_stock_id = x.stock_id AND m.erp_attribute_id <=> x.attribute_id)
        ");
    }

    static function syncFromStock()
    {
        self::addMissingDishes(
            "JOIN rs_menu_categories mc ON mc.stock_category_id = x.category_id AND mc.branch_id = " . self::branchSql() . " JOIN st_stock_category c ON c.id = x.category_id",
            "COALESCE(NULLIF(mc.website_name, ''), c.name)",
            "1"
        );
    }

    /* ************************* Categories ************************* */
    /** Stock categories with their website state on this branch; shown_as is the category name the website uses */
    static function getCategories()
    {
        $Branch = self::branchSql();
        return DBQM::query("
            SELECT c.id, c.name, mc.stock_category_id IS NOT NULL AS on_website, mc.website_name,
                   COALESCE(NULLIF(mc.website_name, ''), c.name) AS shown_as,
                   (SELECT COUNT(*) FROM st_stock s WHERE s.category_id = c.id) AS item_count,
                   (SELECT COUNT(*) FROM rs_menu_items m JOIN st_stock s ON s.id = m.erp_stock_id
                    WHERE s.category_id = c.id AND m.is_visible = 1 AND m.branch_id = $Branch) AS dishes_shown
            FROM st_stock_category c
            LEFT JOIN rs_menu_categories mc ON mc.stock_category_id = c.id AND mc.branch_id = $Branch
            WHERE c.company_id = " . escape(Company::getLoggedInCompanyId(), 'int') . "
            ORDER BY c.name
        ");
    }

    /**
     * Turning a category on adds its stock items to the menu and shows its hidden dishes; turning it off hides them.
     * A new website name is applied to the dishes still under the old name (dishes moved to another category by hand keep theirs).
     *
     * @param array $WebsiteNames [stock_category_id => website name] for every category shown on the form
     * @param array $OnWebsite stock_category_ids that are switched on
     */
    static function saveCategories(array $WebsiteNames, array $OnWebsite)
    {
        $Branch = self::branchSql();
        foreach ($WebsiteNames as $CategoryId => $WebsiteName)
        {
            $CategoryId = escape($CategoryId, 'int');
            $Before = DBQM::fetch_assoc(DBQM::query("
                SELECT c.name, mc.stock_category_id IS NOT NULL AS on_website, COALESCE(NULLIF(mc.website_name, ''), c.name) AS shown_as
                FROM st_stock_category c
                LEFT JOIN rs_menu_categories mc ON mc.stock_category_id = c.id AND mc.branch_id = $Branch
                WHERE c.id = $CategoryId
            "));
            if (!$Before)
                continue;

            $Dishes = "rs_menu_items m JOIN st_stock s ON s.id = m.erp_stock_id AND s.category_id = $CategoryId AND m.branch_id = $Branch";

            if (!in_array($CategoryId, array_map('intval', $OnWebsite)))
            {
                if ($Before['on_website'])
                {
                    DBQM::query("DELETE FROM rs_menu_categories WHERE branch_id = $Branch AND stock_category_id = $CategoryId");
                    DBQM::query("UPDATE $Dishes SET m.is_visible = 0");
                }
                continue;
            }

            $WebsiteName = trim($WebsiteName);
            DBQM::query("INSERT INTO rs_menu_categories (branch_id, stock_category_id, website_name) VALUES ($Branch, $CategoryId, " . escape($WebsiteName) . ")
                         ON DUPLICATE KEY UPDATE website_name = VALUES(website_name)");

            $ShownAs = $WebsiteName !== '' ? $WebsiteName : $Before['name'];
            if ($ShownAs !== $Before['shown_as'])
                DBQM::query("UPDATE $Dishes SET m.category = " . escape($ShownAs) . " WHERE m.category = " . escape($Before['shown_as']));

            if (!$Before['on_website'])
                DBQM::query("UPDATE $Dishes SET m.is_visible = 1");
        }

        self::syncFromStock();
    }

    /** Link to a category section on the website (same slug as restnext lib/data.ts) */
    static function getWebsiteCategoryUrl($CategoryName)
    {
        $Slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($CategoryName)), '-');

        return rtrim(RestaurantOrder::getSettings()['website_url'] ?? '', '/') . '/#category-' . $Slug;
    }

    /* ************************* Menu Items ************************* */
    static function getItems()
    {
        $Branch = self::branchSql();
        return DBQM::query("
            SELECT m.*, x.name AS stock_name, x.price AS stock_price, x.stock_id AS stock_exists, sq.qty AS stock_qty,
                   mc.stock_category_id IS NOT NULL AS is_auto
            FROM rs_menu_items m
            LEFT JOIN (" . self::SELLABLE_SQL . ") x ON x.stock_id = m.erp_stock_id AND x.attribute_id <=> m.erp_attribute_id
            LEFT JOIN st_stock s ON s.id = m.erp_stock_id
            LEFT JOIN rs_menu_categories mc ON mc.stock_category_id = s.category_id AND mc.branch_id = m.branch_id
            LEFT JOIN (
                SELECT b.stock_id, b.attribute_id, SUM(l.qty) AS qty
                FROM st_stock_ledger l JOIN st_stock_batch b ON b.id = l.batch_id
                WHERE l.branch_id = $Branch
                GROUP BY b.stock_id, b.attribute_id
            ) sq ON sq.stock_id = m.erp_stock_id AND sq.attribute_id <=> m.erp_attribute_id
            WHERE m.branch_id = $Branch
            ORDER BY m.category, m.sort_order, m.id
        ");
    }

    static function getDetails($Id)
    {
        return DBQM::fetch_assoc(DBQM::query("SELECT * FROM rs_menu_items WHERE id = " . escape($Id, 'int') . " AND branch_id = " . self::branchSql()));
    }

    /** Sellable items (per variation) not on this branch's menu yet, plus the one linked to the dish being edited */
    static function getStockOptions($CurrentStockKey)
    {
        $Options = ['' => '— None (own price) —'];
        $Select = DBQM::query("SELECT * FROM (" . self::SELLABLE_SQL . ") x ORDER BY x.name");
        while ($Row = DBQM::fetch_assoc($Select))
        {
            $Key = self::getStockKey($Row['stock_id'], $Row['attribute_id']);
            $isOnMenu = DBQM::result(DBQM::query("SELECT COUNT(*) FROM rs_menu_items WHERE branch_id = " . self::branchSql() . " AND erp_stock_id = " . escape($Row['stock_id'], 'int') . " AND erp_attribute_id <=> " . escape($Row['attribute_id'], 'int', true)), 0);

            if (!$isOnMenu || $Key == $CurrentStockKey)
                $Options[$Key] = $Row['name'] . ' (' . number_format($Row['price']) . ')';
        }

        // Keep a link that no longer matches a sellable item (e.g. the item got variations) so saving doesn't drop it
        if (!empty($CurrentStockKey) && !isset($Options[$CurrentStockKey]))
            $Options[$CurrentStockKey] = '⚠ Current link — no longer sellable, choose a variation';

        return $Options;
    }

    static function getWebsiteCategories()
    {
        $Categories = [];
        $Select = DBQM::query("SELECT DISTINCT category FROM rs_menu_items WHERE branch_id = " . self::branchSql() . " ORDER BY category");
        while ($Row = DBQM::fetch_assoc($Select))
            $Categories[] = $Row['category'];

        return $Categories;
    }

    static function save($Id, array $Data)
    {
        $Values = [
            'branch_id'        => self::branchSql(),
            'name'             => escape($Data['name']),
            'description'      => escape($Data['description']),
            'price'            => escape($Data['price'], 'double'),
            'category'         => escape($Data['category']),
            'emoji'            => escape($Data['emoji']),
            'image'            => escape($Data['image'], 'text', true),
            'tag'              => escape($Data['tag'], 'text', true),
            'available'        => $Data['available'] == 'N' ? 0 : 1,
            'is_visible'       => $Data['is_visible'] == 'N' ? 0 : 1,
            'sort_order'       => escape($Data['sort_order'], 'int'),
            'erp_stock_id'     => escape($Data['erp_stock_id'], 'int', true),
            'erp_attribute_id' => escape($Data['erp_attribute_id'] ?? '', 'int', true),
        ];

        if (empty($Id))
        {
            DBQM::query("INSERT INTO rs_menu_items (" . implode(', ', array_keys($Values)) . ") VALUES (" . implode(', ', $Values) . ")");
            return DatabaseHelper::getLastInsertId();
        }

        $Set = [];
        foreach ($Values as $Column => $Value)
            $Set[] = "$Column = $Value";
        DBQM::query("UPDATE rs_menu_items SET " . implode(', ', $Set) . " WHERE id = " . escape($Id, 'int') . " AND branch_id = " . self::branchSql());

        return $Id;
    }

    static function setFlag($Id, $Column, $Value)
    {
        if (!in_array($Column, ['available', 'is_visible', 'show_in_hero']))
            return;

        DBQM::query("UPDATE rs_menu_items SET $Column = " . ($Value ? 1 : 0) . " WHERE id = " . escape($Id, 'int') . " AND branch_id = " . self::branchSql());
    }

    /** Hides or shows every dish of a website category (rs_menu_items.category) on this branch */
    static function setCategoryVisible($Category, bool $Show)
    {
        if (trim($Category) === '')
            return;

        DBQM::query("UPDATE rs_menu_items SET is_visible = " . ($Show ? 1 : 0) . " WHERE category = " . escape($Category) . " AND branch_id = " . self::branchSql());
    }

    /* ************************* Product (stock) form ************************* */
    /** A dish of this stock item on the current branch's menu, preferring one that's shown */
    static function getByStockId($StockId)
    {
        return DBQM::fetch_assoc(DBQM::query("SELECT * FROM rs_menu_items WHERE branch_id = " . self::branchSql() . " AND erp_stock_id = " . escape($StockId, 'int') . " ORDER BY is_visible DESC, id LIMIT 1"));
    }

    /**
     * "Show on website" switch on stock/stock_management.php, applied to every dish (variation) of the item.
     * Ticked adds missing variations and hides dishes whose variation no longer exists;
     * unticked hides them all. An item never on the menu stays off unless its category is ticked on Website Menu.
     * Applies to the current branch's menu only.
     */
    static function saveFromStockForm($StockId, bool $Show, $WebsiteCategory)
    {
        if (empty($StockId))
            return;

        $StockId = escape($StockId, 'int');
        $Branch = self::branchSql();

        if (!$Show)
        {
            DBQM::query("UPDATE rs_menu_items SET is_visible = 0 WHERE branch_id = $Branch AND erp_stock_id = $StockId");
            return;
        }

        $hasCategory = trim($WebsiteCategory) !== '';

        self::addMissingDishes(
            "LEFT JOIN st_stock_category c ON c.id = x.category_id",
            $hasCategory ? escape($WebsiteCategory) : "COALESCE(c.name, 'Menu')",
            "x.stock_id = $StockId"
        );

        DBQM::query("UPDATE rs_menu_items SET is_visible = 1" . ($hasCategory ? ", category = " . escape($WebsiteCategory) : "") . " WHERE branch_id = $Branch AND erp_stock_id = $StockId");

        // Dishes that no longer match a sellable variation (variation removed, or variations added to a plain item)
        DBQM::query("
            UPDATE rs_menu_items m SET m.is_visible = 0
            WHERE m.branch_id = $Branch AND m.erp_stock_id = $StockId
              AND NOT EXISTS (SELECT 1 FROM (" . self::SELLABLE_SQL . ") x WHERE x.stock_id = m.erp_stock_id AND x.attribute_id <=> m.erp_attribute_id)
        ");
    }

    /**
     * "Show on hero slider" switch on stock/stock_management.php, applied to every dish (variation) of the item
     * on the current branch. The website only shows it when the dish is also visible on the menu.
     */
    static function saveHeroFromStockForm($StockId, bool $Hero)
    {
        if (empty($StockId))
            return;

        DBQM::query("UPDATE rs_menu_items SET show_in_hero = " . ($Hero ? 1 : 0) . " WHERE branch_id = " . self::branchSql() . " AND erp_stock_id = " . escape($StockId, 'int'));
    }

    /* ************************* POS menu ************************* */
    /*
     * Categories and items on the POS screen (pos/pos_voucher.php) of a branch, kept apart from the website:
     * everything is shown and available on POS unless listed in rs_pos_hidden_categories / rs_pos_items.
     */

    /**
     * POS settings of a branch
     * @return array ['categories' => [stock_category_id => true], 'hidden' => ["stock_id::attribute_id" => true], 'unavailable' => [same]]
     */
    static function getPOSSettings($BranchId)
    {
        $Settings = ['categories' => [], 'hidden' => [], 'unavailable' => []];
        if (empty($BranchId) or DatabaseHelper::isRecordSetEmpty(DBQM::query("SHOW TABLES LIKE 'rs_pos_items'")))
            return $Settings;

        $Select = DBQM::query("SELECT stock_category_id FROM rs_pos_hidden_categories WHERE branch_id = " . escape($BranchId, 'int'));
        while ($Row = DBQM::fetch_assoc($Select))
            $Settings['categories'][(int)$Row['stock_category_id']] = true;

        $Select = DBQM::query("SELECT stock_id, attribute_id, is_hidden, is_unavailable FROM rs_pos_items WHERE branch_id = " . escape($BranchId, 'int'));
        while ($Row = DBQM::fetch_assoc($Select))
        {
            $Key = Stock::joinStockIdAttributeId($Row['stock_id'], $Row['attribute_id'] ?: '');
            if ($Row['is_hidden'])
                $Settings['hidden'][$Key] = true;
            if ($Row['is_unavailable'])
                $Settings['unavailable'][$Key] = true;
        }

        return $Settings;
    }

    /** "Other" (items without category) is category 0, same as the POS screen */
    static function setPOSCategoryVisible($CategoryId, bool $Show)
    {
        $Where = "branch_id = " . self::branchSql() . " AND stock_category_id = " . escape((int)$CategoryId, 'int');
        DBQM::query($Show
            ? "DELETE FROM rs_pos_hidden_categories WHERE $Where"
            : "INSERT IGNORE INTO rs_pos_hidden_categories (branch_id, stock_category_id) VALUES (" . self::branchSql() . ", " . escape((int)$CategoryId, 'int') . ")");
    }

    /**
     * @param string $StockKey "stock_id::attribute_id"
     * @param string $Column is_hidden or is_unavailable
     */
    static function setPOSItemFlag($StockKey, $Column, bool $Value)
    {
        if (!in_array($Column, ['is_hidden', 'is_unavailable']))
            return;

        list($StockId, $AttributeId) = array_pad(Stock::splitStockIdAttributeId($StockKey), 2, '');
        if (empty($StockId))
            return;

        $Value = $Value ? 1 : 0;
        DBQM::query("INSERT INTO rs_pos_items (branch_id, stock_id, attribute_id, $Column)
                     VALUES (" . self::branchSql() . ", " . escape($StockId, 'int') . ", " . escape((int)$AttributeId, 'int') . ", $Value)
                     ON DUPLICATE KEY UPDATE $Column = $Value");
    }

    /** Items from a "Show on website" category come back on the next sync, so those are hidden instead */
    static function delete($Id)
    {
        DBQM::query("DELETE FROM rs_menu_items WHERE id = " . escape($Id, 'int') . " AND branch_id = " . self::branchSql());
    }
}
