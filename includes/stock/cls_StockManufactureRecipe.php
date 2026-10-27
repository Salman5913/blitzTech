<?php
/**
 * Created by PhpStorm.
 * User: musta
 * Date: 9/25/2018
 * Time: 11:23 AM
 */

class StockManufactureRecipe extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherReport, DefaultVoucherPrint
{
    public function __construct()
    {
        parent::__construct("mnf_stock_recipe", "mnf_stock_recipe_data", "recipe_id", VoucherType::MFSR);
        $this->setReportFilters(false);
    }

    protected function saveTransactionEntries()
    {
        // TODO: Implement saveTransactionEntries() method.
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new BasicVoucherFormUtils();

        $oFormUtils->setColumnDisplayType('stock_id', new FormFieldTypeStock($oFormUtils, 0, 'stock_id', 'attribute_id', '', '', '','', false, '', false, false, false));

        $oFormUtils->setColumnDisplayType('material_stock_id', new FormFieldTypeStock($oFormUtils, 0, 'material_stock_id', 'material_attribute_id', '', '', '', '', false, '', false, false, false));

        $oFormUtils->setColumnWidth(['material_qty' => '20%']);

        return $oFormUtils;
    }

    function getVoucherTotal(): float
    {
        return 0;
    }

    function getVoucherPrintUtilsClass(): BasicVoucherPrintUtils
    {
        return new BasicVoucherPrintUtils();   
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        return new BasicVoucherReportFilters();
    }

    function getSearchQuery(): mysqli_result
    {
        return DBQM::query("
              SELECT 
                  sr.*,
                  d.voucher_type_id,
                  d.voucher_date,
                  d.is_deleted,
                  d.is_draft,
                  ms.ac_stock AS finish_good_name,
                  GROUP_CONCAT(CONCAT(mrs.ac_stock, ' - Qty: ', srd.material_qty) SEPARATOR '<br>') AS raw_materials
              FROM 
                  distinct_id d,
                  mnf_stock_recipe sr, 
                  mnf_stock_recipe_data srd,
                  vw_ac_stock ms,
                  vw_ac_stock mrs 
              WHERE
                  sr.id = srd.recipe_id AND 
                  sr.distinct_id = d.id AND
                  sr.stock_id = ms.id AND 
                  sr.attribute_id <=> ms.attribute_id AND  
                  srd.material_stock_id = mrs.id AND
                  srd.material_attribute_id <=> mrs.attribute_id
              GROUP BY 
                  sr.id
              ORDER BY 
                  sr.id DESC
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn("finish_good_name", "Finished Good");
        $oTableColumn->addColumn("raw_materials", "Raw Material");

        return $oTableColumn;
    }
}