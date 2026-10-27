<?php

class BankStatementImport extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport
{

    function __construct()
    {
        parent::__construct("bsi_bank_statement_import", "bsi_bank_statement_import_data", "bank_statement_import_id", VoucherType::BSIV);
        $this->setReportFilters(false);
    }

    protected function saveTransactionEntries()
    {
        /* ***************** If New Voucher ************** */
        if (!$this->isEdit())
        {
            $this->populateDataTable();
            $this->updateMatchingAccounts();
            $this->skipExistingEntries();

            DBQM::query("UPDATE bsi_bank_statement_import SET is_hide_skipped_rows = 'N' WHERE id = ".escape($this->getVoucherId()));
        }
        /* ***************** If New Voucher ************** */

        $RowDetails = $this->getDetails();
        $BankAccountId = $RowDetails['bank_account_id'];

        /* ***************** Refill all empty accounts (recheck for matching identifier ************** */
        if ($RowDetails['is_refill_empty_accounts'] == 'Y')
            $this->updateMatchingAccounts();
        /* ***************** Refill all empty accounts (recheck for matching identifier ************** */


        /* ***************** Bank Account Type *************** */
        $BankCurrency = ChartOfAccount::getCurrencyCode($BankAccountId);
        $BankCurrencyRate = Currency::getRate($BankCurrency, ProjectHelper::DEFAULT_CURRENCY);
        $BankAccountType = $BankCurrency == ProjectHelper::DEFAULT_CURRENCY ? 'Local' : 'Int';
        /* ***************** Bank Account Type *************** */


        /* **************** Create ledger **************** */
        $SelectData = $this->getData();
        $ErrorDate = [];
        while ($RowData = DBQM::fetch_assoc($SelectData))
        {
            $RowData['currency_rate'] = $RowData['currency_rate'] > 0 ? $RowData['currency_rate'] : 1;
            if ($RowData['is_skipped'] == 'Y') continue;

            if (empty($RowData['account_id']))
            {
                $ErrorDate[$RowData['entry_date']] = DateDecode($RowData['entry_date']);
                continue;
            }

            $oLedger = new Ledger($this->getDistinctId(), $RowData['entry_date'], VoucherType::BSID, Ledger::getNewVoucherNumber(VoucherType::BSID), $RowData['bank_statement_import_id']);

            $Amount = $RowData['debit'] - $RowData['credit'];
            if ($RowData['currency_rate'] == 0)
                $RowData['currency_rate'] = 1;

            if ($BankAccountType == 'Local')
            {
                $oLedger->saveData($BankAccountId, $Amount, 1, $RowData['description'], $RowData['account_id']);
                $oLedger->saveData($RowData['account_id'], ($Amount/$RowData['currency_rate']) * -1, $RowData['currency_rate'], $RowData['description'], $BankAccountId);
            }
            else
            {
                $AccountCurrency = ChartOfAccount::getCurrencyCode($RowData['account_id']);

                if ($AccountCurrency == ProjectHelper::DEFAULT_CURRENCY)
                {
                    $oLedger->saveData($BankAccountId, $Amount, $RowData['currency_rate'], $RowData['description'], $RowData['account_id']);
                    $oLedger->saveData($RowData['account_id'], ($Amount*$RowData['currency_rate']) * -1, 1, $RowData['description'], $BankAccountId);
                }
                else
                {
                    $AccountCurrencyRate = Currency::getRate($AccountCurrency, ProjectHelper::DEFAULT_CURRENCY);
                    $BankAmount = $Amount * $BankCurrencyRate;

                    $oLedger->saveData($BankAccountId, $BankAmount / $BankCurrencyRate, $BankCurrencyRate, $RowData['description'], $RowData['account_id']);
                    $oLedger->saveData($RowData['account_id'], $BankAmount / $AccountCurrencyRate * -1, $AccountCurrencyRate, $RowData['description'], $BankAccountId);
                }
            }
        }

        if (!empty($ErrorDate))
            ErrorHandler::setError("Empty Account Ids on ".implode(", ", $ErrorDate));
        /* **************** Create ledger **************** */
    }

    private function populateDataTable()
    {
        $FileName = $this->getDetails()['file_name'];
        $oUploadPath = new UploadPath(get_class());

        if (($handle = fopen($oUploadPath->getPath().$FileName, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000)) !== FALSE)
            {
                $Date = $data[0];
                $Description = $data[1];

                $Date = strtotime(str_replace('/', '-', $Date));

                if (empty($Date))
                    continue;

                if (isset($data[3])) // if both debit and credit column is supplied
                {
                    $Debit = str_replace(",", "", $data[3]); // Bank's debit = our credit
                    $Credit = str_replace(",", "", $data[2]); // Bank's credit = our debit
                }
                else // if only one column has amount (+ and -) both
                {
                    $Amount = floatval(str_replace(",", "", $data[2]));
                    $Amount *= -1;

                    $Debit = $Amount >= 0 ? $Amount : 0;
                    $Credit = $Amount <= 0 ? abs($Amount) : 0;
                }

                $Date = date('Y-m-d', $Date);

                $this->saveDataTableData([
                    $Date,
                    null,
                    $Debit,
                    $Credit,
                    1,
                    $Description,
                    'N'
                ]);
            }

            fclose($handle);
        }
        else
            ErrorHandler::setError("Unable to load file");
    }

    private function updateMatchingAccounts()
    {
        $SelectMatchingAccount = DBQM::query("
            SELECT
                c.conversion_rate,
                b.account_id,
                import_data_id,
                description
            FROM
                acc_bank_statement_identifier b,
                acc_accounts a,
                acc_currency c,
                (
                    SELECT
                        id AS import_data_id, description 
                    FROM
                        bsi_bank_statement_import_data 
                    WHERE
                        bank_statement_import_id = ".escape($this->getVoucherId())."
                ) as tmp
            WHERE
                b.account_id = a.id AND
                c.code = a.currency_code AND
                identifier <> '' AND
                description LIKE CONCAT( '%', b.identifier, '%' );
        ");

        while ($Row = DBQM::fetch_assoc($SelectMatchingAccount))
        {
            DBQM::query("UPDATE bsi_bank_statement_import_data SET account_id = ".escape($Row['account_id']).", currency_rate = ".escape($Row['conversion_rate'])." WHERE id = ".escape($Row['import_data_id']));
        }
    }

    /**
     * Searches for all the matching entries that are already existed.
     *
     * @return void
     */
    private function skipExistingEntries()
    {
        $BankAccountId = $this->getDetails()['bank_account_id'];
        $SelectData = $this->getData();

        while ($Row = DBQM::fetch_assoc($SelectData))
        {
            $Column = $Row['debit'] > 0 ? 'ld.debit' : 'ld.credit';
            $Amount = $Row['debit'] > 0 ? $Row['debit'] : $Row['credit'];

            $Select = DBQM::query("SELECT * FROM ledger l, ledger_data ld WHERE l.id = ld.ledger_id AND ld.account_id = ".escape($BankAccountId, 'int')." AND $Column = ".escape($Amount)." AND date_time LIKE ".escape($Row['entry_date']."%"));

            if (!DatabaseHelper::isRecordSetEmpty($Select))
                DBQM::query("UPDATE bsi_bank_statement_import_data SET is_skipped = 'Y' WHERE id = ".escape($Row['id']));
        }
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->attachJS("../js/vouchers/bank_statement.js");
        $oFormUtils->setRequiredFields(['bank_account_id']);
        $oFormUtils->setHiddenFields(['ledger_id', 'currency_rate']);
        $oFormUtils->setColumnDisplayType('file_name', new FormFieldTypeFile(new UploadPath(get_class()), [FormFieldTypeFile::TYPE_CSV]));
        $oFormUtils->setAutocomplete('bank_account_id', Autocomplete::TYPE_ACCOUNT_BY_COA, [getConstantCategory()->getBank()]);
        $oFormUtils->setAutocomplete('account_id', Autocomplete::TYPE_ACCOUNT);

        /* ****************** Highlight the skipped row or Hide the skipped row as per the user selection ******************* */
        $RowHighlightClass = 'bg-soft-danger';
        if ($this->isEdit())
        {
            $RowDetails = $this->getDetails();
            if ($RowDetails['is_hide_skipped_rows'] == 'Y')
                $RowHighlightClass = 'hidden';
        }
        $oFormUtils->highlightRow('currency_rate', '>', '1', 'bg-soft-info');
        $oFormUtils->highlightRow('is_skipped', '==', 'Y', $RowHighlightClass);
        /* ****************** Highlight the skipped row or Hide the skipped row as per the user selection ******************* */


        $oFormUtils->checkRowEmptyWithColumn('description');
        $oFormUtils->showColumnTotal(["debit", "credit"]);

        $oFormUtils->setColumnWidth(['entry_date' => '10%', 'debit' => '7%', 'credit' => '7%', 'currency_rate' => '5%', 'description' => '25%', 'is_skipped' => '5%']);

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
        return DBQM::query("SELECT bsi.*, di.voucher_type_id, di.voucher_date, di.is_deleted, di.voucher_number, a.name, di.is_draft FROM bsi_bank_statement_import bsi, distinct_id di, acc_accounts a WHERE a.id = bsi.bank_account_id AND bsi.distinct_id = di.id");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oColumn = new BasicVoucherReportTableColumn();
        $oColumn->addColumn('voucher_date', 'Date', 'date', '10%');
        $oColumn->addColumn('voucher_number', 'Voucher #', 'int', '10%');
        $oColumn->addColumn('name', 'Bank Name');
        $oColumn->addColumn('description', 'Description');

        return $oColumn;
    }
}