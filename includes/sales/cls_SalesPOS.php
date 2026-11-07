<?php

/**
 * Helpers for the POS screen: Order Invoice (OIV), Dine-in Invoice (DIV, with Table No) and WhatsApp Invoice (WHIV).
 * Voucher itself is saved through Sales class (sl_sales / sl_sales_data), Table No of a Dine-in Invoice in sl_sales_dine_in.
 */
class SalesPOS
{
    /**
     * Invoices made on the POS screen: voucher type => [title, mdi icon]
     */
    private static $POSVoucherTypes = [
        VoucherType::OIV => ['Order Invoice', 'point-of-sale'],
        VoucherType::DIV => ['Dine-in Invoice', 'silverware-fork-knife'],
        VoucherType::WHIV => ['WhatsApp Invoice', 'whatsapp'],
    ];

    /**
     * POS voucher type from request value, Order Invoice when not a POS voucher type
     */
    static function getVoucherTypeId($VoucherTypeId)
    {
        return isset(self::$POSVoucherTypes[$VoucherTypeId]) ? (int) $VoucherTypeId : VoucherType::OIV;
    }

    static function getTitle($VoucherTypeId)
    {
        return self::$POSVoucherTypes[$VoucherTypeId][0] ?? 'Website Invoice';
    }

    static function getIcon($VoucherTypeId)
    {
        return self::$POSVoucherTypes[$VoucherTypeId][1] ?? 'web';
    }

    static function getTableNo($SalesId)
    {
        if (empty($SalesId))
            return '';

        $Row = DBQM::fetch_assoc(DBQM::query("SELECT table_no FROM sl_sales_dine_in WHERE sales_id = ".escape($SalesId, 'int')));
        return $Row ? $Row['table_no'] : '';
    }

    static function saveTableNo($SalesId, $TableNo)
    {
        DBQM::query("REPLACE INTO sl_sales_dine_in (sales_id, table_no) VALUES (".escape($SalesId, 'int').", ".escape(mb_substr(trim($TableNo), 0, 20)).")");
    }

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
