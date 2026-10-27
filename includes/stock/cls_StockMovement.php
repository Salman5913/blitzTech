<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 022, 1/22/2015
 * Time: 11:09 AM
 */

class StockMovement extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport
{
    private float $TotalCost = 0;

    public function __construct()
    {
        parent::__construct("st_stock_movement", "st_stock_movement_data", "stock_movement_id", VoucherType::SMV);
    }

    protected function saveTransactionEntries()
    {
        $RowDetails = $this->getDetails();

        $SelectData = $this->getData();
        while ($RowData = DBQM::fetch_assoc($SelectData))
        {
            if ($RowData['qty'] > 0)
            {
                $TotalCost = $this->getStockLedgerObject()->recordSales($RowData['stock_id'], $RowData['attribute_id'], $RowData['qty'], 'COST', $RowDetails['from_branch_id']);
                $this->getStockLedgerObject()->recordPurchase($RowData['stock_id'], $RowData['attribute_id'], $RowDetails['to_branch_id'], $RowData['qty'], $TotalCost / $RowData['qty'],'','','',$RowDetails['invoice_type']);

                $this->TotalCost += $TotalCost;
            }
        }
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->setRequiredFields(['from_branch_id', 'to_branch_id']);

        $oFormUtils->addAdditionalColumns(0, 'available_qty', 'double', 'qty');

        $oFormUtils->setAutocomplete('from_branch_id', Autocomplete::TYPE_BRANCH);
        $oFormUtils->setAutocomplete('to_branch_id', Autocomplete::TYPE_BRANCH);

        $oFormUtils->setColumnDisplayType('stock_id', new FormFieldTypeStock(
            $oFormUtils,
            0,
            'stock_id',
            'attribute_id',
            'from_branch_id',
            'qty',
            '',
            'available_qty',
            true,
            '',
            '',
            '',
            false
        ));

        return $oFormUtils;
    }

    function getVoucherTotal(): float
    {
        return $this->TotalCost;
    }

    function getVoucherPrintUtilsClass(): BasicVoucherPrintUtils
    {
        return new BasicVoucherPrintUtils;
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        $oFilters = new BasicVoucherReportFilters();
        $oFilters->setFilter('voucher_date', 'Date', 'date');
        $oFilters->setFilter('stock_id', 'Stock', '', '', Autocomplete::TYPE_STOCK);

        return $oFilters;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = '';

        if (empty($_GET['voucher_number']))
        {
            $Where .= UtilsQueryMaker::date("di.voucher_date", $_GET['date_from'], $_GET['date_till']);
            $Where .= UtilsQueryMaker::stock("smd.stock_id", "smd.attribute_id", $_GET['stock_id']);
        }
        else
        {
            $Where .= UtilsQueryMaker::equal("di.voucher_number", $_GET['voucher_number']);
        }

        return DBQM::query("
                    SELECT
                        sm.id,
                        sm.distinct_id,
                        di.voucher_date,
                        di.voucher_number,
                        di.voucher_type_id,
                        di.is_draft,
                        di.is_deleted,
                        bf.name AS branch_from,
                        bt.name AS branch_to,
                        sm.description
                    FROM
                        distinct_id di,
                    	branch bf,
                    	branch bt,
                        st_stock_movement sm
                        LEFT JOIN
                            st_stock_movement_data smd
                            ON
                                sm.id = smd.stock_movement_id
                        LEFT JOIN
                            st_stock s
                            ON
                                s.id = smd.stock_id
                    WHERE
                        sm.from_branch_id = bf.id AND 
                        sm.to_branch_id = bt.id AND
                        sm.distinct_id = di.id AND
                        di.year_id = ".escape(Year::getYearId())."
                        {$Where}
                    GROUP BY 
                        sm.id 
                    ORDER BY 
                        di.voucher_date, di.voucher_number
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('voucher_number', 'Voucher #', 'int');
        $oTableColumn->addColumn('branch_from', 'Branch From');
        $oTableColumn->addColumn('branch_to', 'Branch To');
        $oTableColumn->addColumn('description', 'Description');

        return $oTableColumn;
    }
}