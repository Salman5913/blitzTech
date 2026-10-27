<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 022, 1/22/2015
 * Time: 11:09 AM
 */

class StockDispose extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport
{
    private $VoucherTotal = 0;

    function __construct()
    {
        parent::__construct(
            "st_stock_dispose",
            "st_stock_dispose_data",
            "stock_dispose_id",
            VoucherType::STDV
        );
    }

    protected function saveTransactionEntries()
    {
        $oStockLedger = $this->getStockLedgerObject();

        $SelectData = $this->getData();

        while($RowData = DBQM::fetch_assoc($SelectData))
        {
            $this->VoucherTotal += $oStockLedger->recordSales(
                $RowData['stock_id'],
                $RowData['attribute_id'],
                $RowData['qty'],
                'COST',
                $RowData['branch_id']
            );
        }
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->setColumnDisplayType('stock_id', new FormFieldTypeStock(
            $oFormUtils,
            0,
            'stock_id',
            'attribute_id',
            'branch_id',
            'qty',
            '',
            '',
            true,
            '',
            false,
            false,
            false
        ));

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
            $Where .= UtilsQueryMaker::stock("sdd.stock_id", "sdd.attribute_id", $_GET['stock_id']);
        } else {
            $Where .= UtilsQueryMaker::equal("di.voucher_number", $_GET['voucher_number']);
        }


        return DBQM::query("
            SELECT
                di.voucher_type_id,
                di.voucher_number,
                di.voucher_date,
                di.is_draft,
                sd.id,
                sd.distinct_id,
                sd.description,
                di.is_deleted,
                di.voucher_total
            FROM
                distinct_id di, 
                vt_voucher_type vt,
                st_stock_dispose sd
                LEFT JOIN
                    st_stock_dispose_data sdd
                    ON
                        sd.id = sdd.stock_dispose_id
            WHERE
                di.id = sd.distinct_id AND
                di.voucher_type_id = vt.id AND
                sd.id <> '' AND
                di.year_id = ".escape(Year::getYearId())."
                {$Where}
            GROUP BY 
                sd.id 
            ORDER BY 
                di.voucher_date, sd.id
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('voucher_number', 'Voucher #', 'int');
        $oTableColumn->addColumn('voucher_total', 'Total Cost');
        $oTableColumn->addColumn('description', 'Description');

        return $oTableColumn;
    }
}