<?php

class VoucherTypeHelper
{
    static VoucherTypeHelper|null $oVoucherTypeHelper = null;
    private array $VoucherTypeDetails = [];

    private function __construct()
    {
        $Select = DBQM::query("SELECT * FROM vt_voucher_type");

        while ($Row = DBQM::fetch_assoc($Select))
        {
            $this->VoucherTypeDetails[$Row['id']]['name'] = $Row['name'];
            $this->VoucherTypeDetails[$Row['id']]['abbr'] = $Row['abbr'];
            $this->VoucherTypeDetails[$Row['id']]['voucher_class'] = $Row['voucher_class'];
        }
    }

    static function getInstance() : VoucherTypeHelper
    {
        if (!(self::$oVoucherTypeHelper instanceof VoucherTypeHelper))
            self::$oVoucherTypeHelper = new VoucherTypeHelper();

        return self::$oVoucherTypeHelper;
    }

    function getCode($VoucherTypeId)
    {
        return $this->VoucherTypeDetails[$VoucherTypeId]['name'];
    }

    function getAbbr($VoucherTypeId)
    {
        return $this->VoucherTypeDetails[$VoucherTypeId]['abbr'];
    }

    function getClass($VoucherTypeId)
    {
        return $this->VoucherTypeDetails[$VoucherTypeId]['voucher_class'];
    }
}