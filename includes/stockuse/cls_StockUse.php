<?php

class StockUse extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport
{
    private $VoucherTotal = 0;

    function __construct()
    {
        parent::__construct(
            "st_stock_use",
            "st_stock_use_data",
            "stock_use_id",
            VoucherType::SU // Define STUV in VoucherType class
        );
    }

    protected function saveTransactionEntries()
    {
        $oStockLedger = $this->getStockLedgerObject();
        $SelectData = $this->getData();

        while ($RowData = DBQM::fetch_assoc($SelectData))
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
            $Where .= CostCenter::getFilterSql("di.cost_center_id", true);
            $Where .= UtilsQueryMaker::stock("sud.stock_id", "sud.attribute_id", $_GET['stock_id']);
        } else {
            $Where .= UtilsQueryMaker::equal("di.voucher_number", $_GET['voucher_number']);
        }

        return DBQM::query("
            SELECT
                di.voucher_type_id,
                di.voucher_number,
                di.voucher_date,
                di.is_draft,
                su.id,
                su.distinct_id,
                su.description,
                di.is_deleted,
                di.voucher_total
            FROM
                distinct_id di, 
                vt_voucher_type vt,
                st_stock_use su
                LEFT JOIN
                    st_stock_use_data sud
                    ON
                        su.id = sud.stock_use_id
            WHERE
                di.id = su.distinct_id AND
                di.voucher_type_id = vt.id AND
                su.id <> '' AND
                di.year_id = ".escape(Year::getYearId())."
                {$Where}
            GROUP BY 
                su.id 
            ORDER BY 
                di.voucher_date, su.id
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