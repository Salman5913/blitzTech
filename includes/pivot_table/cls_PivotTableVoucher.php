<?php

class PivotTableVoucher extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherReport
{
    const IND_FILTER = 'Filter';
    const IND_SORT = 'Sorting';

    public function __construct()
    {
        parent::__construct("pt_report", [
            self::IND_FILTER => 'pt_filter',
            self::IND_SORT => 'pt_sort',
        ], 'pt_report_id', VoucherType::PT, false);

        $this->setPrintVoucherLink(ProjectHelper::SiteURL()."_pivot_table/print_voucher.php");
    }

    protected function saveTransactionEntries()
    {
        // TODO: Implement saveTransactionEntries() method.
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->setHiddenFields(['pivot_table_class']);
        $oFormUtils->setRequiredFields(['row', 'column', 'value', 'report_name']);
        $oFormUtils->attachJS(ProjectHelper::SiteURL()."js_live/pivot_table/voucher.min.js");

        /**
         * @var PivotTable $oPivotTable
         */

        if (!$this->isEdit())
        {
            if (isset($_GET['pivot_table_class']))
                $oPivotTable = new $_GET['pivot_table_class']();
            else
                $oPivotTable = new $_POST['pivot_table_class']();
        }
        else
        {
            $RowDetails = $this->getDetails();
            $oPivotTable = new $RowDetails['pivot_table_class']();
        }

        $oPermittedColumnsFormFieldType = new FormFieldTypeSelect([...['' => ''], ...$oPivotTable->getPermittedColumns()]);

        $oFormUtils->setColumnDisplayType('row', $oPermittedColumnsFormFieldType);
        $oFormUtils->setColumnDisplayType('column', $oPermittedColumnsFormFieldType);
        $oFormUtils->setColumnDisplayType('sub_row', $oPermittedColumnsFormFieldType);
        $oFormUtils->setColumnDisplayType('sub_column', $oPermittedColumnsFormFieldType);
        $oFormUtils->setColumnDisplayType('value', new FormFieldTypeSelect([...['' => ''], ...$oPivotTable->getPermittedValueColumns()]));
        $oFormUtils->setColumnDisplayType('value_aggregation', new FormFieldTypeSelect([
            'SUM' => 'Sum',
            'AVG' => 'Average',
            'COUNT' => 'Count',
            'MIN' => 'Min',
            'MAX' => 'Max'
        ]));

        $oFormUtils->addAdditionalColumns(self::IND_FILTER, 'select_filter_option', '', 'last_column');


        $oFormUtils->setColumnDisplayType('filter_column', $oPermittedColumnsFormFieldType);
        $oFormUtils->setColumnDisplayType('filter_condition', new FormFieldTypeSelect([
            '=' => 'Equals to',
            '>' => 'Greater then',
            '>=' => 'Greater then Or Equals to',
            '<=' => 'Less then Or Equals to',
            '<' => 'Less then',
            '<>' => 'Not Equals To',
            'contains' => 'Contains',
            'inorequal' => 'Only from (Comma Seperated Values)'
        ]));


        $oFormUtils->setColumnDisplayType('sort_column', $oPermittedColumnsFormFieldType);
        $oFormUtils->setColumnDisplayType('sort_type', new FormFieldTypeSelect([
            'ASC' => 'Ascending',
            'DESC' => 'Descending'
        ]));

        return $oFormUtils;
    }

    function getVoucherTotal(): float
    {
        return 0;
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        return new BasicVoucherReportFilters();
    }

    function getSearchQuery(): mysqli_result
    {
        return DBQM::query("SELECT pt.*, did.voucher_type_id, did.voucher_date, did.is_deleted FROM pt_report pt, distinct_id did WHERE pt.distinct_id = did.id");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oColumn = new BasicVoucherReportTableColumn();
        $oColumn->addColumn('report_name', 'Name');

        return $oColumn;
    }

    static function getAllReports()
    {
        return DBQM::query("SELECT pt.*, did.voucher_type_id FROM pt_report pt, distinct_id did WHERE pt.distinct_id = did.id AND did.is_deleted <> 'Y'");
    }
}