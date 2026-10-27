<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 28/12/2014
 * Time: 11:49 AM
 */

class StockAttribute extends BasicForm
{
    public function __construct()
    {
        parent::__construct("cp_stock_attribute", '', '', "Stock Attribute");
        $this->setAccessAreas(CompanyModule::STOCK, "Attribute");
    }

    function getReport()
    {
        return DBQM::query("SELECT * FROM cp_stock_attribute ORDER BY name");
    }
} 