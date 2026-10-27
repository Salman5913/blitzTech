<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 009, 1/9/2015
 * Time: 3:41 PM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("connection/localhost.php");

class Reporting
{
    protected $DateFrom;
    protected $DateTill;

    protected $EncodedDateFrom;
    protected $EncodedDateTill;

    function setDate($DateFrom, $DateTill)
    {
        $this->DateFrom = $DateFrom;
        $this->DateTill = $DateTill;

        $this->EncodedDateFrom = DateEncode($DateFrom);
        $this->EncodedDateTill = DateEncode($DateTill);
    }

    static function getStockLink($StockId, $AttributeId, $DateFrom, $DateTill, $BranchId)
    {
        $CombineId = Stock::joinStockIdAttributeId($StockId, $AttributeId);
        return ProjectHelper::SiteURL()."stock_report/stock_ledger.php?stock_id=".$CombineId."&branch_id=".$BranchId."&date_from=".$DateFrom."&date_till=".$DateTill."&search=true";
    }
}