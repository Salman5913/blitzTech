<?php
/**
 * Deals (rs_deals / rs_deal_items): a fixed price for 2 or more stock items.
 *
 * rs_deal_items holds the stock item (and attribute, e.g. Medium) of each component. The website sells a component
 * through its website dish (rs_menu_items with the same erp_stock_id / erp_attribute_id), so a deal is sold as its
 * dishes: each dish becomes its own order line, stock is still taken per dish, and the deal price is split across
 * those lines in proportion to their normal prices (see restaurant_bridge/api/index.php, POST /orders).
 *
 * Every branch has its own deals (branch_id), made of dishes on that branch's menu. These functions work on the
 * branch the restaurant pages are on.
 */
class RestaurantDeal
{
    private static function branchSql()
    {
        return escape(RestaurantBranch::getCurrentId(), 'int');
    }

    /** Every website dish with the price the website charges for it, and whether it can be sold right now */
    const DISH_SQL = "
        SELECT m.id, m.name, m.category, m.is_visible, m.available,
               COALESCE(NULLIF(sa.selling_price, 0), s.sp, m.price) AS price,
               (m.available = 1 AND m.is_visible = 1 AND (m.erp_stock_id IS NULL OR (s.id IS NOT NULL
                   AND (m.erp_attribute_id IS NULL OR (sa.id IS NOT NULL AND sa.is_inactive <> 'Y'))))) AS sellable
        FROM rs_menu_items m
        LEFT JOIN st_stock s ON s.id = m.erp_stock_id
        LEFT JOIN st_stock_attribute sa ON sa.stock_id = m.erp_stock_id AND sa.attribute_id = m.erp_attribute_id";

    /** Active deals with their item count, normal price and sales (cancelled orders are left out) */
    static function getDeals()
    {
        return DBQM::query("
            SELECT d.*,
                   (SELECT COUNT(*) FROM rs_deal_items di WHERE di.deal_id = d.id) AS item_count,
                   (SELECT COALESCE(SUM(x.price), 0) FROM rs_deal_items di
                     JOIN rs_menu_items mi ON mi.erp_stock_id = di.stock_id AND mi.erp_attribute_id <=> di.attribute_id AND mi.is_visible = 1 AND mi.branch_id = d.branch_id
                     JOIN (" . self::DISH_SQL . ") x ON x.id = mi.id
                     WHERE di.deal_id = d.id) AS normal_price,
                   (SELECT COUNT(DISTINCT o.id) FROM rs_order_items ol JOIN rs_orders o ON o.id = ol.order_id
                     WHERE ol.deal_id = d.id AND o.status <> 'cancelled') AS order_count,
                   (SELECT COALESCE(SUM(ol.qty), 0) FROM rs_order_items ol JOIN rs_orders o ON o.id = ol.order_id
                     WHERE ol.deal_id = d.id AND o.status <> 'cancelled') AS lines_qty,
                   (SELECT COALESCE(SUM(ol.unit_price * ol.qty), 0) FROM rs_order_items ol JOIN rs_orders o ON o.id = ol.order_id
                     WHERE ol.deal_id = d.id AND o.status <> 'cancelled') AS revenue
            FROM rs_deals d
            WHERE d.is_active = 1 AND d.branch_id = " . self::branchSql() . "
            ORDER BY d.name");
    }

    static function getDetails($Id)
    {
        return DBQM::fetch_assoc(DBQM::query("SELECT * FROM rs_deals WHERE id = " . escape($Id, 'int') . " AND is_active = 1 AND branch_id = " . self::branchSql()));
    }

    /** The components of a deal: stock item and attribute, with the website dish that sells it (if any) */
    static function getItems($Id)
    {
        $Items = [];
        $Select = DBQM::query("
            SELECT di.stock_id, di.attribute_id,
                   CONCAT(s.name, IFNULL(CONCAT(' - ', ca.name), '')) AS name,
                   c.name AS category,
                   COALESCE(x.price, 0) AS price,
                   COALESCE(x.sellable, 0) AS sellable
            FROM rs_deal_items di
            JOIN rs_deals d ON d.id = di.deal_id
            JOIN st_stock s ON s.id = di.stock_id
            LEFT JOIN cp_stock_attribute ca ON ca.id = di.attribute_id
            LEFT JOIN st_stock_category c ON c.id = s.category_id
            LEFT JOIN rs_menu_items mi ON mi.erp_stock_id = di.stock_id AND mi.erp_attribute_id <=> di.attribute_id AND mi.is_visible = 1 AND mi.branch_id = d.branch_id
            LEFT JOIN (" . self::DISH_SQL . ") x ON x.id = mi.id
            WHERE di.deal_id = " . escape($Id, 'int') . "
            ORDER BY name");
        while ($Row = DBQM::fetch_assoc($Select))
            $Items[] = $Row;

        return $Items;
    }

    /** [stock id, attribute id or null] of each component, for the deal form */
    static function getComponentKeys($Id)
    {
        $Keys = [];
        $Select = DBQM::query("SELECT stock_id, attribute_id FROM rs_deal_items WHERE deal_id = " . escape($Id, 'int') . " ORDER BY stock_id");
        while ($Row = DBQM::fetch_assoc($Select))
            $Keys[] = [(int)$Row['stock_id'], $Row['attribute_id'] === null ? null : (int)$Row['attribute_id']];

        return $Keys;
    }

    /** Orders that included this deal, newest first */
    static function getRecentOrders($Id)
    {
        return DBQM::query("
            SELECT o.id, o.order_code, o.status, o.order_type, o.created_at, o.erp_voucher_number,
                   MAX(ol.qty) AS deal_qty, SUM(ol.unit_price * ol.qty) AS amount
            FROM rs_order_items ol JOIN rs_orders o ON o.id = ol.order_id
            WHERE ol.deal_id = " . escape($Id, 'int') . "
            GROUP BY o.id
            ORDER BY o.id DESC
            LIMIT 50");
    }

    /**
     * Creates or updates a deal from the stock item keys of the form ("stock::attribute", as the stock autocomplete posts them).
     * Needs at least 2 different stock items, one attribute per item, and each one on the website menu.
     * @return array [int dealId or null, string error]
     */
    static function save($Id, $Name, $Description, $Price, array $StockKeys)
    {
        if (trim($Name) === '')
            return [null, 'Please enter a deal name.'];
        if ((float)$Price <= 0)
            return [null, 'Please enter the deal price.'];

        /* ************* Components: stock id => attribute id (null when the item has no attribute) ************* */
        $Components = [];
        foreach ($StockKeys as $Key)
        {
            if (trim((string)$Key) === '')
                continue;

            list($StockId, $AttributeId) = array_pad(Stock::splitStockIdAttributeId($Key), 2, '');
            $StockId = (int)$StockId;
            $AttributeId = $AttributeId === '' ? null : (int)$AttributeId;
            if (!$StockId)
                continue;

            if (array_key_exists($StockId, $Components) && $Components[$StockId] !== $AttributeId)
                return [null, Stock::getName($StockId, null) . ': choose only one attribute in a deal.'];

            $Components[$StockId] = $AttributeId;
        }

        if (count($Components) < 2)
            return [null, 'A deal needs at least 2 different items.'];

        foreach ($Components as $StockId => $AttributeId)
        {
            $OnWebsite = DBQM::result(DBQM::query("SELECT COUNT(*) FROM rs_menu_items WHERE is_visible = 1 AND branch_id = " . self::branchSql() . " AND erp_stock_id = " . escape($StockId, 'int') . "
                                                   AND erp_attribute_id <=> " . escape($AttributeId, 'int', true)), 0);
            if (!$OnWebsite)
                return [null, Stock::getName($StockId, $AttributeId) . ' is not on this branch\'s website menu. Show it on the website first.'];
        }

        // A deal of another branch (switched branch while editing) is not changed
        if ($Id && !self::getDetails($Id))
            return [null, 'This deal belongs to another branch. Switch to that branch to edit it.'];

        /* ************* Save ************* */
        $Connection = DatabaseHelper::getConnection();
        $Connection->begin_transaction();

        if ($Id)
        {
            DBQM::query("UPDATE rs_deals SET name = " . escape($Name) . ", description = " . escape($Description) . ", price = " . escape($Price, 'double') . " WHERE id = " . escape($Id, 'int'));
        }
        else
        {
            DBQM::query("INSERT INTO rs_deals (branch_id, name, description, price) VALUES (" . self::branchSql() . ", " . escape($Name) . ", " . escape($Description) . ", " . escape($Price, 'double') . ")");
            $Id = DatabaseHelper::getLastInsertId();
        }

        DBQM::query("DELETE FROM rs_deal_items WHERE deal_id = " . escape($Id, 'int'));
        foreach ($Components as $StockId => $AttributeId)
            DBQM::query("INSERT INTO rs_deal_items (deal_id, stock_id, attribute_id) VALUES (" . escape($Id, 'int') . ", " . escape($StockId, 'int') . ", " . escape($AttributeId, 'int', true) . ")");

        $Connection->commit();

        return [(int)$Id, ''];
    }

    static function setWebsite($Id, bool $Show)
    {
        DBQM::query("UPDATE rs_deals SET show_on_website = " . ($Show ? 1 : 0) . " WHERE id = " . escape($Id, 'int') . " AND branch_id = " . self::branchSql());
    }

    /** Removes the deal from the ERP and the website. Past orders keep their lines. */
    static function deactivate($Id)
    {
        DBQM::query("UPDATE rs_deals SET is_active = 0, show_on_website = 0 WHERE id = " . escape($Id, 'int') . " AND branch_id = " . self::branchSql());
    }
}
