<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 002, 1/2/2015
 * Time: 10:14 AM
 */

class Cheque extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport
{
    public function __construct()
    {
        parent::__construct("cq_cheque", '', '', [VoucherType::CQV, VoucherType::CQF, VoucherType::CQR]);

        $this->setAutoPrint(true);
    }

    protected function saveTransactionEntries()
    {
        $oLedger = $this->getLedgerObject();
        $oSMSTemplates = new SMSTemplates();

        $RowDetails = $this->getDetails();
        $ChequeDate = $RowDetails['cheque_date'];

       // $oInvoicePayment = new InvoicePayment($RowDetails['account_id']);

        $DescriptionPrefix = "Chq #: ".$RowDetails['cheque_number']." / Chq Bank: ".$RowDetails['bank']." / Chq Date ".DateEncode($ChequeDate, 'd/m/y')." / ";

        /* ************************* Cheque Received ***************************** */
        $oLedger->updateDate($RowDetails['voucher_date']);
        $CounterAccountId = !empty($RowDetails['reissue_account_id']) ? $RowDetails['reissue_account_id'] : getConstantAccount()->getChequeAccount();

        $oLedger->saveData( $RowDetails['account_id'], "-" . $RowDetails['amount'], 1,$DescriptionPrefix.' Cheque received', $CounterAccountId);
        $oLedger->saveData( getConstantAccount()->getChequeAccount(), $RowDetails['amount'], 1,$DescriptionPrefix.' Cheque received', $RowDetails['account_id'] );
        /* ************************* Cheque Received ***************************** */

       // $oInvoicePayment->lumSumPayment($this->getDistinctId(), "-".$RowDetails['amount']);



        /* ************************* Cheque Forwarded ***************************** */
        if (!empty($RowDetails['reissue_account_id']))
        {
            $ChequeForwardedDateTime = DateEncode($RowDetails['reissue_date']);
            $oChequeForwardedLedger = new Ledger( $this->getDistinctId(), $ChequeForwardedDateTime, VoucherType::CQF, Ledger::getNewVoucherNumber(VoucherType::CQF), $this->getVoucherId());

            $oChequeForwardedLedger->saveData(getConstantAccount()->getChequeAccount(), "-".$RowDetails['amount'], 1, $DescriptionPrefix.' Cheque forwarded', $RowDetails['reissue_account_id'] );
            $oChequeForwardedLedger->saveData($RowDetails['reissue_account_id'], $RowDetails['amount'], 1, $DescriptionPrefix.' Cheque forwarded', $RowDetails['account_id']);
        }
        /* ************************* Cheque Forwarded ***************************** */



        /* ************************* Cheque Returned ***************************** */
        /**
         * If cheque is returned, post ledger entries in reverse order
         */
        if ($RowDetails['is_return'] == 'Y')
        {
            $ChequeReturnedDateTime = DateEncode($RowDetails['return_date']);
            $oChequeReturnedLedger = new Ledger( $this->getDistinctId(), $ChequeReturnedDateTime, VoucherType::CQR, Ledger::getNewVoucherNumber(VoucherType::CQR), $this->getVoucherId());

            if ( ! empty( $RowDetails['reissue_account_id'] ) )
            {
                $oChequeReturnedLedger->saveData(getConstantAccount()->getChequeAccount(), $RowDetails['amount'], 1, $DescriptionPrefix .' forwarded cheque returned', $RowDetails['reissue_account_id'] );
                $oChequeReturnedLedger->saveData($RowDetails['reissue_account_id'], "-".$RowDetails['amount'], 1,$DescriptionPrefix .' forwarded cheque returned', $RowDetails['account_id']);

                $oSMSTemplates->UserChequeRecorded($RowDetails['reissue_account_id'], $RowDetails['cheque_number'], $RowDetails['amount'], $this->isEdit());
            }

            //$oInvoicePayment->lumSumPayment($this->getDistinctId(), $RowDetails['amount']);

            $oChequeReturnedLedger->saveData($RowDetails['account_id'], $RowDetails['amount'], 1,$DescriptionPrefix . ' Cheque returned', $CounterAccountId);
            $oChequeReturnedLedger->saveData(getConstantAccount()->getChequeAccount(), "-".$RowDetails['amount'], 1,$DescriptionPrefix . ' Cheque returned', $RowDetails['account_id'] );
        }
        /* ************************* Cheque Returned ***************************** */

        $oSMSTemplates->UserChequeRecorded($RowDetails['account_id'], $RowDetails['cheque_number'], $RowDetails['amount'], $this->isEdit());

    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->setAutocomplete('account_id', Autocomplete::TYPE_ACCOUNT);
        $oFormUtils->setAutocomplete('reissue_account_id', Autocomplete::TYPE_ACCOUNT);

        $oFormUtils->addElementGroup('Cheque Details', 'col-md-12', 'col-md-3', [
            'bank',
            'cheque_date',
            'cheque_number',
            'account_id',
            'amount',
            'description'
        ]);
        $oFormUtils->addElementGroup('Re Issue', 'col-md-6', 'col-md-6', [
            'reissue_date',
            'reissue_account_id'
        ]);
        $oFormUtils->addElementGroup('Return Status', 'col-md-6', 'col-md-6', [
            'is_return',
            'return_date'
        ]);

        return $oFormUtils;
    }

    function getVoucherTotal(): float
    {
        $RowDetails = $this->getDetails();
        return $RowDetails['amount'];
    }

    function getVoucherPrintUtilsClass(): BasicVoucherPrintUtils
    {
        return new BasicVoucherPrintUtils();
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        $oReportFilters = new BasicVoucherReportFilters();
        $oReportFilters->setFilter('cheque_date', 'Cheque Date', 'date');
        $oReportFilters->setFilter('cheque_number', 'Cheque #', '');
        $oReportFilters->setFilter('voucher_number', 'Voucher #', '');
        $oReportFilters->setFilter('account_id', 'Account', '', '', Autocomplete::TYPE_ACCOUNT);
        $oReportFilters->setFilter('reissue_account_id', 'Re-issued To', '', '', Autocomplete::TYPE_ACCOUNT);
        $oReportFilters->setFilter('is_return', 'Return?', 'select', ['' => 'All', 'Y' => 'Yes', 'N' => 'No']);

        return $oReportFilters;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = '';
        if (empty($_GET['voucher_number']) and empty($_GET['cheque_number']))
        {
            $Where .= UtilsQueryMaker::date("di.voucher_date", $_GET['date_from'], $_GET['date_till']);
            $Where .= UtilsQueryMaker::equal("a.id", $_GET['account_id']);
            $Where .= UtilsQueryMaker::equal("ra.id", $_GET['reissue_account_id']);
            $Where .= UtilsQueryMaker::equal("c.is_return", $_GET['is_return']);
        }
        elseif (!empty($_GET['cheque_number']))
            $Where .= UtilsQueryMaker::equal("c.cheque_number", $_GET['cheque_number']);
        else
            $Where .= UtilsQueryMaker::equal("di.voucher_number", $_GET['voucher_number']);

        return DBQM::query("
                    SELECT
                        c.*,
                        di.voucher_date,
                        di.voucher_total,
                        di.voucher_type_id,
                        di.voucher_number,
                        di.is_draft,
                        a.name AS from_name,
                        ra.name AS to_name
                    FROM
                        acc_accounts a,
                        distinct_id di,
                        cq_cheque c
                        LEFT JOIN
                            acc_accounts ra
                            ON
                                ra.id = c.reissue_account_id
                    WHERE
                        di.id = c.distinct_id AND
                        a.id = c.account_id AND
                        di.year_id = ".escape(Year::getYearId(), 'int')."
                        {$Where}
                    GROUP BY
                        c.id
                    ORDER BY 
                        DATE(di.voucher_date) DESC, c.id DESC
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oReportColumn = new BasicVoucherReportTableColumn();
        $oReportColumn->addColumn('voucher_date', 'Voucher Date', 'date');
        $oReportColumn->addColumn('voucher_number', 'Voucher #', 'int');
        $oReportColumn->addColumn('cheque_date', 'Cheque Date', 'date');
        $oReportColumn->addColumn('cheque_number', 'Cheque Number');
        $oReportColumn->addColumn('amount', 'Amount', 'float');
        $oReportColumn->addColumn('from_name', 'Account');
        $oReportColumn->addColumn('to_name', 'Issued To');
        $oReportColumn->addColumn('is_return', 'Returned?');

        return $oReportColumn;
    }

    public function highlightDeletedRow($Row)
    {
        parent::highlightDeletedRow($Row);

        if ($Row['is_return'] == 'Y')
            echo ' bg-warning ';
    }
}