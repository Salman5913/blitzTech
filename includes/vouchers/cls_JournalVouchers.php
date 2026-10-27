<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 002, 1/2/2015
 * Time: 10:14 AM
 */

class JournalVouchers extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport, DefaultVoucherPrintAdditionalFormatting
{
    private float $VoucherTotal = 0;

    public function __construct()
    {
        parent::__construct("vc_journal_voucher", "vc_journal_voucher_data", "voucher_id", [
            VoucherType::OPA,
            VoucherType::JV
        ]);

        $this->setAutoPrint(true);
    }

    protected function saveTransactionEntries()
    {
        $SelectData = $this->getData();

        $oSMSTemplates = new SMSTemplates();
        $oLedger = $this->getLedgerObject();

        while ($Row = DBQM::fetch_assoc($SelectData))
        {
            $Row['currency_rate'] = $Row['currency_rate'] > 0 ? $Row['currency_rate'] : 1;
            $oLedger->saveData($Row['account_id'], ($Row['debit'] / $Row['currency_rate']), $Row['currency_rate'], $Row['description']);
            $oLedger->saveData($Row['account_id'], "-".($Row['credit'] / $Row['currency_rate']), $Row['currency_rate'], $Row['description']);
            $this->VoucherTotal += $Row['debit'];

            /* ***************** SMS ********************* */
            $oSMSTemplates->JournalTransaction($Row['account_id'], round(abs(($Row['debit']) / $Row['currency_rate'])), $Row['description']);
            /* ***************** SMS ********************* */
        }
    }

    function getVoucherPrintUtilsClass(): BasicVoucherPrintUtils
    {
        $oBasicVoucherPrintUtils = new BasicVoucherPrintUtils();
        $oBasicVoucherPrintUtils->calcTotals(["amount"]);

        if (in_array($this->getVoucherType(), [VoucherType::CPV, VoucherType::CRV]))
            $oBasicVoucherPrintUtils->setHideColumns(['cheque_number', 'cheque_date', 'wht_amount', 'currency_rate']);

        if ($this->getVoucherType() == VoucherType::CPV)
            $oBasicVoucherPrintUtils->setIsSignatureRequire(true);

        return $oBasicVoucherPrintUtils;
    }

    function getFormattedColumnValue($ColumnName, $Value): string|null
    {
        if ($ColumnName == 'description')
            return nl2br($Value);
        else
            return null;
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new BasicVoucherFormUtils();

        $oFormUtils->addAdditionalColumns(0, 'current_balance', 'float', 'debit', 'text-right');
        $oFormUtils->setColumnDisplayType('account_id', new FormFieldTypeAccount('current_balance', 'currency_rate'));

        $oFormUtils->setColumnWidth([
            'account_id' => '30%',
            'debit' => '10%',
            'current_balance' => '10%',
            'currency_rate' => '10%',
            'credit' => '10%'
        ]);

        $oFormUtils->showColumnTotal(['debit', 'credit']);
        $oFormUtils->setHiddenFields(['currency_rate']);
        $oFormUtils->attachJS(ProjectHelper::SiteURL()."js_live/vouchers/journal_voucher.min.js");

        return $oFormUtils;
    }

    function getVoucherTotal(): float
    {
        return $this->VoucherTotal;
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        $oReportFilter = new BasicVoucherReportFilters();
        $oReportFilter->setFilter('voucher_date', 'Date', 'date');
        $oReportFilter->setFilter('voucher_number', 'Voucher #', 'int');
        $oReportFilter->setFilter('voucher_type_id', 'Voucher Type', 'select', [
            VoucherType::JV => 'Journal Voucher',
            VoucherType::OPA => 'Opening Voucher'
        ]);
        $oReportFilter->setFilter('account_id', 'Account', '', Autocomplete::TYPE_ACCOUNT);
        $oReportFilter->setFilter('description', 'Description', '');

        return $oReportFilter;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = '';

        if (empty($_GET['voucher_number']))
        {
            $Where .= UtilsQueryMaker::date("di.voucher_date", $_GET['date_from'], $_GET['date_till']);
            $Where .= UtilsQueryMaker::like("di.voucher_type_id", $_GET['voucher_type_id']);
            $Where .= UtilsQueryMaker::equal("a.id", $_GET['account_id']);
            $Where .= UtilsQueryMaker::like("vd.description", $_GET['description']);
        }
        else
        {
            $Where .= UtilsQueryMaker::like("di.voucher_number", $_GET['voucher_number']);
            $Where .= UtilsQueryMaker::like("di.voucher_type_id", $_GET['voucher_type_id']);
        }

        return DBQM::query("
                    SELECT
                        di.voucher_date,
                        di.voucher_number,
                        di.voucher_type_id,
                        di.voucher_total,
                        di.is_draft,
                        vt.abbr,
                        v.id,
                        v.distinct_id,
                        di.is_deleted,
                        get_cost_center_name(di.cost_center_id) as cost_center_name,
                        get_journal_voucher_data_account_names_by_voucher_id(v.id) AS voucher_data_account_names
                    FROM
                        vt_voucher_type vt,
                        distinct_id di,
                        vc_journal_voucher v
                        LEFT JOIN
                            vc_journal_voucher_data vd ON v.id = vd.voucher_id
                        LEFT JOIN
                            acc_accounts a ON vd.account_id = a.id
                    WHERE
                        di.id = v.distinct_id AND
                        di.voucher_type_id = vt.id
                        {$Where}
                    GROUP BY 
                        v.id 
                    ORDER BY 
                        di.voucher_date DESC, di.voucher_number DESC
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('voucher_date', 'Date', BasicVoucherReportTableColumn::TYPE_DATE);
        $oTableColumn->addColumn('abbr', 'Voucher Type');
        $oTableColumn->addColumn('voucher_number', 'Voucher #', BasicVoucherReportTableColumn::TYPE_INT);
        $oTableColumn->addColumn('voucher_data_account_names', 'Accounts');
        $oTableColumn->addColumn('voucher_total', 'Total', BasicVoucherReportTableColumn::TYPE_FLOAT);
        $oTableColumn->addColumn('cost_center_name', 'Cost Center');

        return $oTableColumn;
    }
}