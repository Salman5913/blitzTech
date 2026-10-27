<?php

class PurchaseRequisition extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherReport, DefaultVoucherPrint
{
    public function __construct()
    {
        parent::__construct("pr_purchase_requisition", "pr_purchase_requisition_data", "purchase_requisition_id", VoucherType::PR);
    }

    function getVoucherNumberByRequisitionId($RequisitionId)
    {
        $Select = DBQM::query("SELECT d.voucher_number FROM pr_purchase_requisition pr, distinct_id d WHERE pr.distinct_id = d.id AND pr.id = ".escape($RequisitionId));

        return DBQM::result($Select, 0);
    }

    function getReport($DateFrom, $DateTill, $ItemName, $VoucherNumber, $DepartmentName)
    {

    }

    protected function saveTransactionEntries()
    {
        // TODO: Implement saveTransactionEntries() method.
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->setAutocomplete('requested_by_account_id', Autocomplete::TYPE_ACCOUNT_BY_COA, [getConstantCategory()->getEmployee()]);
        $oFormUtils->setColumnDisplayType('stock_id', new FormFieldTypeStock($oFormUtils, 0, 'stock_id', 'attribute_id', '', 'qty', '', '', false, '', false, false, false));

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
        $oReportFilter = new BasicVoucherReportFilters();
        $oReportFilter->setFilter('voucher_date', 'Date', 'date');
        $oReportFilter->setFilter('voucher_number', 'Voucher #', 'int');
        $oReportFilter->setFilter('stock_id', 'Stock', '', '', Autocomplete::TYPE_STOCK);

        return $oReportFilter;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = ' ';

        if (empty($_GET['voucher_number']))
        {
            $Where = UtilsQueryMaker::stock("pr.stock_id", "pr.attribute_id", $_GET['stock_id']);
            $Where .= UtilsQueryMaker::date("di.voucher_date", $_GET['date_from'], $_GET['date_till']);
        }
        else
        {
            $Where .= UtilsQueryMaker::equal("pr.id", $_GET['voucher_number']);
        }

        return DBQM::query("
                    SELECT
	                    di.voucher_date,
                        di.voucher_type_id,
                        di.voucher_number,
	                    di.is_deleted,
	                    pr.id,
	                    pr.id AS purchase_requisition_id,
	                    pr.description,
                        shl.username,
                        department,
                        vt.abbr AS linked_voucher_name
                    FROM
                        sh_login shl,
                        distinct_id di,
                        vt_voucher_type vt,
                        pr_purchase_requisition pr
                        LEFT JOIN 
                            pr_purchase_requisition_data srd
                            ON 
                            pr.id = srd.purchase_requisition_id
                        LEFT JOIN 
                            st_stock s
                            ON 
                            srd.stock_id = s.id
                    WHERE
                        di.id = pr.distinct_id AND
                        vt.id = di.voucher_type_id AND
                        shl.id = di.created_by
                        {$Where}
                    GROUP BY 
                        pr.id
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('voucher_date', 'Date', BasicVoucherReportTableColumn::TYPE_DATE);
        $oTableColumn->addColumn('voucher_number', 'Voucher #');
        $oTableColumn->addColumn('department', 'Department');
        $oTableColumn->addColumn('username', 'Requested By');
        $oTableColumn->addColumn('description', 'Description');

        return $oTableColumn;
    }
}