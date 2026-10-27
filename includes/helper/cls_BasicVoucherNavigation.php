<?php

class BasicVoucherNavigation
{
    const KEY_REPORT = "report_link";
    const KEY_VOUCHER = "voucher_link";
    const KEY_FOLDER = "folder_name";
    const KEY_PAGES = "pages";
    const KEY_IS_ACCESS = "is_access";

    static $Navigation = [
        "Purchase" => [
            self::KEY_PAGES => [
                self::KEY_REPORT => "report_purchase.php"
            ],
            "others" => [
                self::KEY_IS_ACCESS => "Y"
            ]
        ],
        "Sales" => [
                self::KEY_PAGES => [
                self::KEY_REPORT => "report_sales.php"
            ],
            "others" => [
                self::KEY_IS_ACCESS => "Y"
            ]
        ]
    ];

    static function getReportFileName($ClassName)
    {
        return self::$Navigation[$ClassName][self::KEY_PAGES][self::KEY_REPORT];
    }

    static function getVoucherFileName($ClassName)
    {
        return self::$Navigation[$ClassName][self::KEY_PAGES][self::KEY_VOUCHER];
    }
}