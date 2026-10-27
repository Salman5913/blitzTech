<?php
/**
 * Delivery areas (rs_delivery_areas): the areas the website delivers to, each with its own delivery charge.
 * Every branch has its own areas (branch_id); these functions work on the branch the restaurant pages are on.
 * The website lists the active areas of the chosen branch at checkout, and restaurant_bridge/api/index.php charges the one the customer picks.
 */
class RestaurantDeliveryArea
{
    private static function branchSql()
    {
        return escape(RestaurantBranch::getCurrentId(), 'int');
    }

    static function getAll()
    {
        return DBQM::query("SELECT * FROM rs_delivery_areas WHERE is_active = 1 AND branch_id = " . self::branchSql() . " ORDER BY sort_order, name");
    }

    static function getDetails($Id)
    {
        return DBQM::fetch_assoc(DBQM::query("SELECT * FROM rs_delivery_areas WHERE id = " . escape($Id, 'int') . " AND is_active = 1 AND branch_id = " . self::branchSql()));
    }

    /**
     * Creates or updates an area of the current branch. Free over is optional (empty = delivery is never free for this area).
     * @return array [int areaId or null, string error]
     */
    static function save($Id, $Name, $Charge, $FreeOver, $SortOrder)
    {
        if (trim($Name) === '')
            return [null, 'Please enter the area name.'];
        if ($Charge === '' || (float)$Charge < 0)
            return [null, 'Please enter the delivery charge (0 or more).'];

        $FreeOver = trim((string)$FreeOver) === '' ? null : (float)$FreeOver;
        $SortOrder = (int)$SortOrder;

        if ($Id)
        {
            DBQM::query("UPDATE rs_delivery_areas SET name = " . escape($Name) . ", delivery_charge = " . escape($Charge, 'double') . ",
                         free_over = " . escape($FreeOver, 'double', true) . ", sort_order = " . escape($SortOrder, 'int') . "
                         WHERE id = " . escape($Id, 'int') . " AND branch_id = " . self::branchSql());
            return [(int)$Id, ''];
        }

        DBQM::query("INSERT INTO rs_delivery_areas (branch_id, name, delivery_charge, free_over, sort_order) VALUES (" . self::branchSql() . ", " . escape($Name) . ", " . escape($Charge, 'double') . ",
                     " . escape($FreeOver, 'double', true) . ", " . escape($SortOrder, 'int') . ")");

        return [(int)DatabaseHelper::getLastInsertId(), ''];
    }

    /** Removes the area from the website (past orders keep the area name) */
    static function deactivate($Id)
    {
        DBQM::query("UPDATE rs_delivery_areas SET is_active = 0 WHERE id = " . escape($Id, 'int') . " AND branch_id = " . self::branchSql());
    }
}
