<?php

class CRMSalesPitch extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport
{
    function __construct()
    {
        parent::__construct('crm_sales_pitch', '', '', VoucherType::CRSP, false);
        $this->setProject(false);
        $this->setReportFilters(false);
    }

    protected function saveTransactionEntries()
    {

    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new  BasicVoucherFormUtils();
        $oFormUtils->setRequiredFields(['industry_name', 'modules', 'top_reports']);
        $oFormUtils->addElementGroup('Industry Details', 'col-sm-12', 'col-lg-6', [
            'existing_client',
            'modules',
            'top_reports'
        ]);

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
        return DBQM::query("SELECT csp.*, did.is_deleted, did.voucher_type_id, did.voucher_number, did.voucher_date FROM crm_sales_pitch csp, distinct_id did WHERE did.id = csp.distinct_id ORDER BY industry_name ASC");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oColumn = new BasicVoucherReportTableColumn();
        $oColumn->addColumn('industry_name', 'Industry');
        $oColumn->addColumn('existing_client', 'Clients');
        $oColumn->addColumn('modules', 'Modules');
        $oColumn->addColumn('top_reports', 'Reports');

        return $oColumn;
    }
}