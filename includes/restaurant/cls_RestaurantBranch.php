<?php
/**
 * Branches of the restaurant website (rs_branches). Every branch has its own menu, categories, deals,
 * delivery areas and web orders (branch_id on rs_menu_items, rs_menu_categories, rs_deals, rs_delivery_areas, rs_orders).
 *
 * The restaurant pages in the ERP work on one branch at a time: the one picked in the branch dropdown
 * on the restaurant tabs (restaurant/_tabs.php), kept in the session.
 */
class RestaurantBranch
{
    const SESSION_KEY = '__restaurant_branch_id';

    /** Every branch of the company with its website settings */
    static function getAll()
    {
        return DBQM::query("
            SELECT b.id, b.name, b.address, b.phone, b.is_active,
                   COALESCE(rb.show_on_website, 0) AS show_on_website, COALESCE(rb.sort_order, 0) AS sort_order,
                   (SELECT COUNT(*) FROM rs_menu_items m WHERE m.branch_id = b.id AND m.is_visible = 1) AS dish_count,
                   (SELECT COUNT(*) FROM rs_delivery_areas a WHERE a.branch_id = b.id AND a.is_active = 1) AS area_count
            FROM branch b
            LEFT JOIN rs_branches rb ON rb.branch_id = b.id
            WHERE b.company_id = " . escape(Company::getLoggedInCompanyId(), 'int') . "
            ORDER BY COALESCE(rb.sort_order, 0), b.name
        ");
    }

    /** [branch id => name] for dropdowns */
    static function getOptions()
    {
        $Options = [];
        $Select = self::getAll();
        while ($Row = DBQM::fetch_assoc($Select))
            $Options[$Row['id']] = $Row['name'] . ($Row['show_on_website'] ? '' : ' (not on website)');

        return $Options;
    }

    static function isCompanyBranch($BranchId)
    {
        return (bool)DBQM::result(DBQM::query("SELECT COUNT(*) FROM branch WHERE id = " . escape($BranchId, 'int') . "
                                               AND company_id = " . escape(Company::getLoggedInCompanyId(), 'int')), 0);
    }

    /** The branch the restaurant pages work on: picked in the dropdown, else the login branch, else restaurant_bridge/config.php */
    static function getCurrentId()
    {
        foreach ([$_SESSION[self::SESSION_KEY] ?? null, $_SESSION['__branch_id'] ?? null, RestaurantOrder::getSettings()['branch_id']] as $BranchId)
        {
            if (!empty($BranchId) && self::isCompanyBranch($BranchId))
                return (int)$BranchId;
        }

        return (int)RestaurantOrder::getSettings()['branch_id'];
    }

    static function setCurrentId($BranchId)
    {
        if (self::isCompanyBranch($BranchId))
            $_SESSION[self::SESSION_KEY] = (int)$BranchId;
    }

    /**
     * Saves which branches the website offers, and their order on the website's branch picker.
     * @param array $OnWebsite branch ids that are switched on
     * @param array $SortOrders [branch id => sort order]
     */
    static function saveSettings(array $OnWebsite, array $SortOrders)
    {
        $OnWebsite = array_map('intval', $OnWebsite);
        foreach ($SortOrders as $BranchId => $SortOrder)
        {
            if (!self::isCompanyBranch($BranchId))
                continue;

            DBQM::query("INSERT INTO rs_branches (branch_id, show_on_website, sort_order)
                         VALUES (" . escape($BranchId, 'int') . ", " . (in_array((int)$BranchId, $OnWebsite) ? 1 : 0) . ", " . escape($SortOrder, 'int') . ")
                         ON DUPLICATE KEY UPDATE show_on_website = VALUES(show_on_website), sort_order = VALUES(sort_order)");
        }
    }

    /**
     * Copies a branch's website menu to another branch: categories, dishes (with their modifiers) and deals.
     * Dishes the target already has (same stock item and variation, or same name for unlinked dishes) are skipped,
     * so copying twice adds nothing. Delivery areas are not copied (each branch delivers to its own areas).
     *
     * @return array [int dishes copied, int deals copied, string error]
     */
    static function copyMenu($FromBranchId, $ToBranchId)
    {
        $From = escape($FromBranchId, 'int');
        $To = escape($ToBranchId, 'int');

        if ($From == $To)
            return [0, 0, 'Choose a different branch to copy from.'];
        if (!self::isCompanyBranch($FromBranchId) || !self::isCompanyBranch($ToBranchId))
            return [0, 0, 'Branch not found.'];

        $Connection = DatabaseHelper::getConnection();
        $Connection->begin_transaction();

        DBQM::query("INSERT IGNORE INTO rs_menu_categories (branch_id, stock_category_id, website_name)
                     SELECT $To, stock_category_id, website_name FROM rs_menu_categories WHERE branch_id = $From");

        $DishCount = 0;
        $Select = DBQM::query("SELECT * FROM rs_menu_items m WHERE m.branch_id = $From
                               AND NOT EXISTS (SELECT 1 FROM rs_menu_items t WHERE t.branch_id = $To
                                   AND ((m.erp_stock_id IS NOT NULL AND t.erp_stock_id = m.erp_stock_id AND t.erp_attribute_id <=> m.erp_attribute_id)
                                        OR (m.erp_stock_id IS NULL AND t.erp_stock_id IS NULL AND t.name = m.name)))
                               ORDER BY m.id");
        while ($Dish = DBQM::fetch_assoc($Select))
        {
            DBQM::query("INSERT INTO rs_menu_items (branch_id, name, description, price, category, emoji, image, tag, available, is_visible, show_in_hero, sort_order, erp_stock_id, erp_attribute_id)
                         SELECT $To, name, description, price, category, emoji, image, tag, available, is_visible, show_in_hero, sort_order, erp_stock_id, erp_attribute_id
                         FROM rs_menu_items WHERE id = " . escape($Dish['id'], 'int'));
            $NewDishId = (int)DatabaseHelper::getLastInsertId();
            $DishCount++;

            $SelectGroups = DBQM::query("SELECT * FROM rs_modifier_groups WHERE menu_item_id = " . escape($Dish['id'], 'int') . " ORDER BY id");
            while ($Group = DBQM::fetch_assoc($SelectGroups))
            {
                DBQM::query("INSERT INTO rs_modifier_groups (menu_item_id, name, type, required, replaces_price, sort_order)
                             SELECT $NewDishId, name, type, required, replaces_price, sort_order FROM rs_modifier_groups WHERE id = " . escape($Group['id'], 'int'));
                $NewGroupId = (int)DatabaseHelper::getLastInsertId();
                DBQM::query("INSERT INTO rs_modifier_options (group_id, name, price, is_active, sort_order)
                             SELECT $NewGroupId, name, price, is_active, sort_order FROM rs_modifier_options WHERE group_id = " . escape($Group['id'], 'int') . " ORDER BY id");
            }
        }

        $DealCount = 0;
        $SelectDeals = DBQM::query("SELECT * FROM rs_deals d WHERE d.branch_id = $From AND d.is_active = 1
                                    AND NOT EXISTS (SELECT 1 FROM rs_deals t WHERE t.branch_id = $To AND t.is_active = 1 AND t.name = d.name)
                                    ORDER BY d.id");
        while ($Deal = DBQM::fetch_assoc($SelectDeals))
        {
            DBQM::query("INSERT INTO rs_deals (branch_id, name, description, price, show_on_website, is_active)
                         SELECT $To, name, description, price, show_on_website, 1 FROM rs_deals WHERE id = " . escape($Deal['id'], 'int'));
            $NewDealId = (int)DatabaseHelper::getLastInsertId();
            DBQM::query("INSERT INTO rs_deal_items (deal_id, stock_id, attribute_id)
                         SELECT $NewDealId, stock_id, attribute_id FROM rs_deal_items WHERE deal_id = " . escape($Deal['id'], 'int'));
            $DealCount++;
        }

        $Connection->commit();

        return [$DishCount, $DealCount, ''];
    }
}
