<?php

class CRMInquiryType extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport
{
    function __construct()
    {
        parent::__construct('crm_inquiry_type', '', '', VoucherType::CRIT, false);
        $this->setProject(false);
        $this->setReportFilters(false);
    }

    protected function saveTransactionEntries()
    {

    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new  BasicVoucherFormUtils();
        $oFormUtils->setRequiredFields(['type_name']);

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
        return DBQM::query("SELECT csp.*, did.is_deleted, did.voucher_type_id, did.voucher_number, did.voucher_date FROM crm_inquiry_type csp, distinct_id did WHERE did.id = csp.distinct_id ORDER BY type_name ASC");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oColumn = new BasicVoucherReportTableColumn();
        $oColumn->addColumn('type_name', 'Inquiry Type');

        return $oColumn;
    }
}