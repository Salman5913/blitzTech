<?php

/**
 * Helpers for Order Invoice Voucher (POS screen).
 * Voucher itself is saved through Sales class (sl_sales / sl_sales_data).
 */
class SalesPOS
{
    /**
     * All sellable items with their category, for POS item grid
     * @return array
     */
    static function getCatalog()
    {
        $Select = DBQM::query("
            SELECT
                s.id,
                s.attribute_id,
                s.ac_stock,
                s.selling_price,
                s.barcode,
                s.category_id,
                IFNULL(c.name, 'Other') AS category_name
            FROM
                vw_ac_stock s
                LEFT JOIN st_stock_category c ON c.id = s.category_id
            WHERE
                s.company_id = ".escape(Company::getLoggedInCompanyId(), 'int')."
            ORDER BY
                category_name, s.ac_stock
        ");

        $Images = self::getMenuImages();

        $Items = [];
        while ($Row = DBQM::fetch_assoc($Select))
        {
            $Key = Stock::joinStockIdAttributeId($Row['id'], $Row['attribute_id']);

            $Items[] = [
                'id' => $Key,
                'image' => $Images[$Key]['image'] ?? '',
                'emoji' => $Images[$Key]['emoji'] ?? '',
                'name' => $Row['ac_stock'],
                'price' => (float) $Row['selling_price'],
                'barcode' => (string) $Row['barcode'],
                'category_id' => (int) $Row['category_id'],
                'category' => $Row['category_name'],
            ];
        }

        return $Items;
    }

    /**
     * Item pictures uploaded from Restaurant > Website Menu (rs_menu_items), linked by stock + attribute.
     * Menu item of logged in branch is preferred, else any branch.
     * @return array "stock_id::attribute_id" => [image, emoji]
     */
    static function getMenuImages()
    {
        if (DatabaseHelper::isRecordSetEmpty(DBQM::query("SHOW TABLES LIKE 'rs_menu_items'")))
            return [];

        $Select = DBQM::query("
            SELECT
                erp_stock_id,
                erp_attribute_id,
                image,
                emoji
            FROM
                rs_menu_items
            WHERE
                erp_stock_id IS NOT NULL AND
                (IFNULL(image, '') <> '' OR IFNULL(emoji, '') <> '')
            ORDER BY
                (branch_id = ".escape((int) $_SESSION['__branch_id'], 'int').") DESC, (IFNULL(image, '') <> '') DESC, id
        ");

        $Images = [];
        while ($Row = DBQM::fetch_assoc($Select))
        {
            $Key = Stock::joinStockIdAttributeId($Row['erp_stock_id'], $Row['erp_attribute_id']);
            $Image = trim((string) $Row['image']);
            $Emoji = trim((string) $Row['emoji']);

            // only site relative paths or http(s) urls
            if ($Image !== '' && !preg_match('#^(https?://|/)#i', $Image))
                $Image = '';
            // emoji saved with wrong charset comes as "?"
            if (str_contains($Emoji, '?'))
                $Emoji = '';

            if (!isset($Images[$Key]))
                $Images[$Key] = ['image' => $Image, 'emoji' => $Emoji];
            else
            {
                $Images[$Key]['image'] = $Images[$Key]['image'] ?: $Image;
                $Images[$Key]['emoji'] = $Images[$Key]['emoji'] ?: $Emoji;
            }
        }

        return $Images;
    }

    /**
     * POS menu of a warehouse, managed from Restaurant > Website Menu > POS (kept apart from the website menu).
     * Items not listed are shown and available.
     * @param $BranchId
     * @return array ['items' => ["stock_id::attribute_id" => ['available' => bool, 'visible' => bool]], 'hidden_categories' => [int]]
     */
    static function getMenuStatus($BranchId)
    {
        $Settings = RestaurantMenu::getPOSSettings($BranchId);

        $Status = [];
        foreach (array_keys($Settings['hidden'] + $Settings['unavailable']) as $Key)
            $Status[$Key] = ['available' => empty($Settings['unavailable'][$Key]), 'visible' => empty($Settings['hidden'][$Key])];

        return [
            'items' => (object) $Status,
            'hidden_categories' => array_keys($Settings['categories']),
        ];
    }
}
