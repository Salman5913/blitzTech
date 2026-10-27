<?php

class StockRequisition extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherReport
{
    private bool $isReturnVoucher;
    public function __construct()
    {
        parent::__construct("prj_stock_requisition","prj_stock_requisition_data", 'stock_requisition_id', [VoucherType::STRV, VoucherType::STRRV]);

        $this->setAutoPrint(true);
        $this->isReturnVoucher = $_GET['voucher_type_id'] == VoucherType::STRRV;
    }

    protected function saveTransactionEntries()
    {
        $oStockLedger = $this->getStockLedgerObject();
        $SelectData = $this->getData();

        while ($RowData = DBQM::fetch_assoc($SelectData))
        {
            $Qty = $RowData['qty'] - $RowData['qty_return'];
            $oStockLedger->recordSales($RowData['stock_id'], $RowData['attribute_id'], $Qty, 'COST', $RowData['branch_id']);
        }
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $AllowAvailableQty = true;
        $HiddenFields[] = "requisition_id";

        if ($this->isReturnVoucher)
        {
            $HiddenFields[] = "qty";
            $AllowAvailableQty = false;
        }
        else
        {
            $HiddenFields[] = "qty_return";
        }

        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->setHiddenFields($HiddenFields);
        $oFormUtils->setColumnDisplayType("stock_id", new FormFieldTypeStock(
            $oFormUtils,
            0,
            "stock_id",
            "attribute_id",
            "branch_id",
            "qty",
            "",
            "",
            $AllowAvailableQty,
            '',
            false,
            false,
            false

        ));
        $oFormUtils->setColumnWidth([
            "stock_id" => "50%",
            "branch_id" => "20%",
            "available_qty" => "10%",
            "qty" => "10%",
            "qty_return" => "10%",
        ]);
        $oFormUtils->showColumnTotal(['qty', 'qty_return']);

        return $oFormUtils;
    }

    function getVoucherTotal(): float
    {
        return 0;
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        $oReportFilter = new BasicVoucherReportFilters();
        $oReportFilter->setFilter('voucher_date', 'Date', 'date');
        $oReportFilter->setFilter('voucher_number', 'Voucher #', '');
        $oReportFilter->setFilter('project_id', 'Project Name', '', '',Autocomplete::TYPE_PROJECT);
        $oReportFilter->setFilter('account_id', 'Customer Name', '', '',Autocomplete::TYPE_ACCOUNT);
        $oReportFilter->setFilter('stock_id', 'Item Name', '', '', Autocomplete::TYPE_STOCK);
        $oReportFilter->setFilter('branch_id', 'Warehouse', '', '',Autocomplete::TYPE_BRANCH);
        $oReportFilter->setFilter('voucher_type_id', 'Voucher Type', 'select', [
            "" => "All",
            VoucherType::STRV => VoucherType::getName(VoucherType::STRV),
            VoucherType::STRRV => VoucherType::getName(VoucherType::STRRV)
        ]);
        $oReportFilter->setFilter('description', 'Description', '');

        return $oReportFilter;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = "";
        $Where .= UtilsQueryMaker::equal("voucher_type_id", $_GET['voucher_type_id']);
        if (empty($_GET['voucher_number']))
        {
            list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_GET['stock_id']);

            $Where .= UtilsQueryMaker::equal("project_id", $_GET['project_id']);
            $Where .= UtilsQueryMaker::equal("account_id", $_GET['account_id']);
            $Where .= UtilsQueryMaker::equal("stock_id", $StockId);
            $Where .= UtilsQueryMaker::equal("attribute_id", $AttributeId);
            $Where .= UtilsQueryMaker::equal("branch_id", $_GET['branch_id']);
            $Where .= UtilsQueryMaker::like("description", $_GET['description']);
            $Where .= UtilsQueryMaker::date("d.voucher_date", $_GET['date_from'], $_GET['date_till']);
        }
        else
        {
            $Where = UtilsQueryMaker::equal("voucher_number", $_GET['voucher_number']);
        }

        $Query = "
            SELECT
                d.voucher_date,
                d.voucher_number,
                d.voucher_type_id,
                d.is_deleted,
                d.is_draft,
                vt.abbr,
                qe.*,
                a.ac_project AS project_name,
                a.account_name
            FROM
                prj_stock_requisition qe,
                prj_stock_requisition_data qed,
                vt_voucher_type vt,
                distinct_id d
                LEFT JOIN vw_ac_project a ON d.project_id = a.id
            WHERE
                d.id = qe.distinct_id AND
                qe.id = qed.stock_requisition_id AND
                vt.id = d.voucher_type_id
                {$Where}
            GROUP BY
                qe.id 
            ORDER BY 
                d.voucher_date DESC
        ";

        return DBQM::query($Query);
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('voucher_date', 'Date', BasicVoucherReportTableColumn::TYPE_DATE, "10%");
        $oTableColumn->addColumn('abbr', 'Voucher', '', "15%");
        $oTableColumn->addColumn('voucher_number', 'Voucher Number','', "8%");
        $oTableColumn->addColumn('account_name', 'Account', '', "20%");
        $oTableColumn->addColumn('project_name', 'Project', '', "20%");
        $oTableColumn->addColumn('description', 'Description', '', '27%');

        return $oTableColumn;
    }

    function highlightDeletedRow($Row)
    {
        parent::highlightDeletedRow($Row); // TODO: Change the autogenerated stub
         return "";
    }
}