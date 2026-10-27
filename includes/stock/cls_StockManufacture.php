<?php
/**
 * Created by PhpStorm.
 * User: musta
 * Date: 9/25/2018
 * Time: 11:23 AM
 */

class StockManufacture extends BasicVoucher implements DefaultVoucherPrint, DefaultVoucherReport, DefaultVoucherForm, DefaultVoucherReportAdditionalUtils
{
    private float $VoucherTotal = 0;

    public function __construct()
    {
        parent::__construct("mnf_manufacturing", "mnf_manufacturing_data", "manufacturing_id", [VoucherType::BMV]);
    }

    function getData($TableIndex = 0, array $Ids = [])
    {
        if ($this->isNew() and $TableIndex == 0)
            return $this->getRecipe($_GET['finish_stock_id'], $_GET['finish_qty']);
        else
            return parent::getData($TableIndex, $Ids);
    }

    function saveTransactionEntries()
    {
        $oStockLedger = $this->getStockLedgerObject();

        $RowDetails = $this->getDetails();
        $TotalCOGS = 0;

        /* *********** Sale Raw Material On Cost Price ************ */
        $SelectRawMaterial = $this->getData();
        while ($Row = DBQM::fetch_assoc($SelectRawMaterial)) {
            $COGS = $oStockLedger->recordSales(
                $Row['material_stock_id'],
                $Row['material_attribute_id'],
                $Row['material_qty'],
                'COST',
                $Row['material_branch_id']
            );

            $TotalCOGS += $COGS;
        }
        /* *********** Sale Raw Material On Cost Price ************ */


        /* *********** Purchase Finished Good ************ */
        $CostPrice = empty($RowDetails['finish_cost_price']) ? ($TotalCOGS / $RowDetails['finish_qty']) : $RowDetails['finish_cost_price'];

        $oStockLedger->recordPurchase(
            $RowDetails['finish_stock_id'],
            $RowDetails['finish_attribute_id'],
            $RowDetails['finish_branch_id'],
            $RowDetails['finish_qty'],
            $CostPrice,'','','',$RowDetails['invoice_type']
        );
        /* *********** Purchase Finished Good ************ */

        $this->VoucherTotal = $TotalCOGS;
    }

    function getReport($CombineStockId, $VoucherNumber, $DateFrom, $DateTill, $Description)
    {

    }

    function getRecipe($CombineStockId, $Qty)
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombineStockId);

        return DBQM::query("SELECT rd.*, rd.material_qty * $Qty AS material_qty FROM mnf_stock_recipe_data rd, mnf_stock_recipe r WHERE rd.recipe_id = r.id AND r.stock_id = ".escape($StockId)." AND r.attribute_id <=> ".escape($AttributeId, 'int', true));
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        /*if(!isset($_GET['finish_stock_id']))
            Utils::redirect('../manufacturing/pre_manufacture.php');*/



        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->setAutocomplete("finish_stock_id", Autocomplete::TYPE_STOCK);
        $oFormUtils->setHiddenFields(["finish_attribute_id"]);
        $oFormUtils->setRequiredFields(['finish_branch_id', 'finish_stock_id', 'finish_qty']);
        /*$oFormUtils->setColumnDisplayType('finish_stock_id', new FormFieldTypeStock($oFormUtils, 0, 'finish_stock_id', 'finish_attribute_id', '', '', '','', false, '', false, false, false));*/

        $oFormUtils->setColumnDisplayType('material_stock_id', new FormFieldTypeStock($oFormUtils, 0, 'material_stock_id', 'material_attribute_id', 'material_branch_id', 'material_qty', '', '', true, '', false, false, false));
        $oFormUtils->setAutocomplete('finish_branch_id', Autocomplete::TYPE_BRANCH);

        return $oFormUtils;
    }

    function getVoucherTotal(): float
    {
        return $this->VoucherTotal;
    }

    function getVoucherPrintUtilsClass(): BasicVoucherPrintUtils
    {
        return new BasicVoucherPrintUtils;
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        $oReportFilter = new BasicVoucherReportFilters();
        $oReportFilter->setFilter('finish_stock_id', 'Finished Stock', '', '', Autocomplete::TYPE_STOCK);
        $oReportFilter->setFilter('voucher_date', 'Date', 'date');
        $oReportFilter->setFilter('voucher_number', 'Voucher #', '');
        $oReportFilter->setFilter('description', 'Description', '');

        return $oReportFilter;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = '';

        if (empty($_GET['voucher_number']))
        {
            $Where .= UtilsQueryMaker::date("did.voucher_date", $_GET['date_from'], $_GET['date_till']);
            $Where .= UtilsQueryMaker::stock("m.finish_stock_id", "m.finish_attribute_id", $_GET['finish_stock_id']);
            $Where .= UtilsQueryMaker::like("m.description", $_GET['description']);
        }
        else
            $Where .= UtilsQueryMaker::equal("did.voucher_number", $_GET['voucher_number']);



        $Query="SELECT 
                                          m.*,
                                          did.voucher_type_id,
                                          did.voucher_total,
                                          did.voucher_date,
                                          did.voucher_number,
                                          did.is_draft,
                                          ms.ac_stock AS finish_good_name,
                                          vt.name AS voucher_prefix,
                                          GROUP_CONCAT(CONCAT(mrs.ac_stock, ' - Qty: ', mr.material_qty) SEPARATOR '\n') AS raw_materials 
                                      FROM 
                                          distinct_id did,
                                          mnf_manufacturing m,
                                          mnf_manufacturing_data mr,
                                          vw_ac_stock ms,
                                          vw_ac_stock mrs,
                                          vt_voucher_type vt
                                      WHERE
                                          did.id = m.distinct_id AND
                                          did.voucher_type_id = vt.id AND
                                          m.id = mr.manufacturing_id AND 
                                          m.finish_stock_id = ms.id AND 
                                          m.finish_attribute_id <=> ms.attribute_id AND  
                                          mr.material_stock_id = mrs.id AND
                                          mr.material_attribute_id <=> mrs.attribute_id
                                      	  {$Where}
                                      GROUP BY 
                                          m.id
                                      ORDER BY 
                                          m.id DESC";


        SuccessHandler::setMessage($Query);
                                 return DBQM::query($Query);


    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('voucher_date', 'Date', 'date');
        $oTableColumn->addColumn('voucher_number', 'Voucher #', BasicVoucherReportTableColumn::TYPE_INT);
        $oTableColumn->addColumn('finish_good_name', 'Finish Good');
        $oTableColumn->addColumn('finish_qty', 'Qty');
        $oTableColumn->addColumn('raw_materials', 'Material Used');

        return $oTableColumn;
    }

    function additionalReportLinks($Row): array
    {
        return [];
    }

    function additionalReportBulkOptionLinks(): array
    {
        return [];
    }

    function additionalReportVoucherVoucherTypesForOpenNew(): array
    {
       return [VoucherType::BMV."-New"];
    }

    function additionalReportAddVoucherTypesQueryParam(): array
    {
        return [];
    }

    function additionalReportAddOtherPagesLink(): array
    {
        return [];
    }
}