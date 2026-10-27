<?php

class QuotationEstimate extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherReport
{
    const IND_INVENTORY = 'INVENTORY';
    const IND_EXPENSE = 'EXPENSE';

    public function __construct()
    {
        parent::__construct("qt_quote_estimate",
            [
                self::IND_INVENTORY => "qt_quote_estimate_inventory",
                self::IND_EXPENSE => "qt_quote_estimate_expenses"
            ], 'estimate_id', VoucherType::QTEV, false);

        $this->setAutoPrint(true);
        $this->setProject(false);
        $this->setAddNewOptionInReport(false);
    }

    protected function saveTransactionEntries()
    {

    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        if ($this->isEdit())
            $QuoteId = $this->getDetails()['quote_id'];
        else
            $QuoteId = $_GET['quote_id'];


        list($QuoteVoucherNumber, $ProjectName) = Quotation::getQuoteVoucherNumber($QuoteId);
        $_GET['quote_voucher_number'] = $QuoteVoucherNumber;
        $_GET['project_name'] = $ProjectName;

        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->attachJS("../js_live/project/estimate.min.js");
        $oFormUtils->setHiddenFields(['quote_id', 'attribute_id']);
        $oFormUtils->setReadonlyFields(['quote_voucher_number', 'project_name']);
        $oFormUtils->setAutocomplete("stock_id", Autocomplete::TYPE_STOCK);
        $oFormUtils->setAutocomplete("expense_account_id", Autocomplete::TYPE_ACCOUNT);

        $oFormUtils->addDefaultGroup('col-md-4', 'col-md-12');
        $oFormUtils->addElementGroup('Labour Details', 'col-md-4', 'col-md-12', [
            'number_of_working_days',
            'number_of_labour',
            'labour_working_hours_per_day',
            'labour_working_hours_per_day',
            'average_labour_amount_per_day'
        ]);
        $oFormUtils->addElementGroup('Quotation Details', 'col-md-4', 'col-md-12', ['quote_voucher_number', 'project_name']);
        $oFormUtils->setColumnWidth([
            "item_description" => "50%",
            "qty" => "17%",
            "rate" => "17%",
            "total" => "15%",
            "stock_id" => "50%"
        ]);
        $oFormUtils->addAdditionalColumns(self::IND_INVENTORY, "total", "float", "last_column", "text-right");
        $oFormUtils->showColumnTotal(['qty', 'total', 'amount']);

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
        $oReportFilter->setFilter('project_id', 'Project', '', '',Autocomplete::TYPE_PROJECT);
        $oReportFilter->setFilter('quote_voucher_number', 'Quotation Voucher #', BasicVoucherReportTableColumn::TYPE_INT);
        $oReportFilter->setFilter('description', 'Description', '');

        return $oReportFilter;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = "";
        if (empty($_GET['voucher_number']))
        {
            $Where .= UtilsQueryMaker::equal("qd.project_id", $_GET['project_id']);
            $Where .= UtilsQueryMaker::like("qd.voucher_number", $_GET['quote_voucher_number']);
            $Where .= UtilsQueryMaker::like("q.description", $_GET['description']);
            $Where .= UtilsQueryMaker::date("d.voucher_date", $_GET['date_from'], $_GET['date_till']);
        }
        else
        {
            $Where = UtilsQueryMaker::equal("d.voucher_number", $_GET['voucher_number']);
        }

        $Query = "
            SELECT
                d.voucher_date,
                d.voucher_number,
                d.voucher_type_id,
                d.is_deleted,
                d.is_draft,
                qe.*,
                a.`name` AS project_name,
                qd.voucher_number as quote_voucher_number
            FROM
                qt_quote_estimate qe,
                distinct_id d,
                qt_quote q,
                distinct_id qd
                LEFT JOIN prj_project a ON qd.project_id = a.id
            WHERE
                d.id = qe.distinct_id AND
                q.id = qe.quote_id AND
                qd.id = q.distinct_id
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
        $oTableColumn->addColumn('voucher_date', 'Date', BasicVoucherReportTableColumn::TYPE_DATE);
        $oTableColumn->addColumn('voucher_number', 'Voucher #');
        $oTableColumn->addColumn('quote_voucher_number', 'Quote Voucher #');
        $oTableColumn->addColumn('project_name', 'Project');
        $oTableColumn->addColumn('description', 'Description');

        return $oTableColumn;
    }

    function highlightDeletedRow($Row)
    {
        parent::highlightDeletedRow($Row); // TODO: Change the autogenerated stub
         return "";
    }
}