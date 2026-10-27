<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 024, 12/24/2014
 * Time: 12:36 PM
 */

class Ledger
{
    private $LedgerId;

    function __construct($DistinctId, $DateTime, $VoucherTypeId, $VoucherNumber, $VoucherId, $ProjectId='')
    {
        DBQM::query("INSERT INTO ledger VALUES (
										0,
										".escape($DistinctId, 'int').",
										".escape($DateTime).",
										".escape($VoucherTypeId, 'int').",
										".escape($VoucherNumber, 'int').",
										".escape($VoucherId, 'int').",
										".escape($ProjectId, 'int', true).",
										".escape(Year::getYearId(), 'int')."
        )");

        $this->LedgerId = DatabaseHelper::getLastInsertId();

        if (empty($this->LedgerId))
            ErrorHandler::setError("Unable to create accounting ledger");
    }

    function updateDate($NewDateTime)
    {
        DBQM::query("UPDATE ledger SET date_time = ".escape($NewDateTime)." WHERE id = ".escape($this->LedgerId));
    }

    function updateVoucherIdByDistinctId($DistinctId, $VoucherId)
    {
        DBQM::query("UPDATE ledger SET voucher_id = ".escape($VoucherId, 'int')." WHERE distinct_id = ".escape($DistinctId, 'int'));
    }

    function saveData($AccountId, $Amount, $CurrencyRate, $Description='', $CounterAccountId=null, $AdditionalInfo='', $AllowZero='N', $StockId='', $AttributeId='', $StockQty='', $StockPrice='',$InvoiceType='')
    {
//        var_dump($AccountId.'--'.$Amount.'--'. $CurrencyRate.'--'. $Description.'--'. $CounterAccountId.'--'. $AdditionalInfo.'--'.  $AllowZero='N', $StockId.'--'. $AttributeId.'--'.  $StockQty.'--'. $StockPrice.'--'. $InvoiceType);
//        exit();
        if ($InvoiceType == Sales::TYPE_DELIVERY)
            return false;

        $Amount = str_replace("--", "", $Amount);

        if ($Amount != 0 or $AllowZero == 'Y')
        {
            $Debit = ($Amount >= 0) ? $Amount : 0;
            $Credit = ($Amount < 0) ? abs($Amount) : 0;

            DBQM::query("INSERT INTO ledger_data VALUES (
										0,
										".escape($this->LedgerId, 'int').",
										".escape($AccountId, 'int').",
										".escape($CounterAccountId, 'int', true).",
										".escape($Debit, 'double').",
										".escape($Credit, 'double').",
										".escape($Description).",
										".escape($AdditionalInfo).",
										".escape($CurrencyRate, 'double').",
										".escape($StockId,'int',true).",
										".escape($AttributeId,'int',true).",
										".escape($StockQty, 'double').",
										".escape($StockPrice, 'double')."
            )");
        }
    }

    static function deleteByDistinctId($DistinctId) {
        DBQM::query("DELETE FROM ledger WHERE distinct_id = ".escape($DistinctId, 'int')." AND year_id = ".escape(Year::getYearId()));
    }

    static function deleteByDistinctIdNVoucherType($DistinctId, $VoucherTypeId) {
        DBQM::query("DELETE FROM ledger WHERE distinct_id = ".escape($DistinctId, 'int')." AND voucher_type_id = ".escape($VoucherTypeId)." AND year_id = ".escape(Year::getYearId()));
    }

    static function getDateTimeByVoucherNumber($VoucherTypeId, $VoucherNumber)
    {
        $Select = DBQM::query("SELECT date_time FROM ledger WHERE voucher_number = ".escape($VoucherNumber)." AND voucher_type_id = ".escape($VoucherTypeId)." AND year_id = ".escape(Year::getYearId(), 'int'));
        return DBQM::result($Select, 0);
    }

    static function getLedgerId($VoucherTypeId, $VoucherNumber)
    {
        $Select = DBQM::query("SELECT id FROM ledger WHERE voucher_number = ".escape($VoucherNumber)." AND voucher_type_id = ".escape($VoucherTypeId)." AND year_id = ".escape(Year::getYearId()));
        return DBQM::result($Select, 0);
    }

    static function getLedgerIdByDistinctId($DistinctId)
    {
        $Select = DBQM::query("SELECT id FROM ledger WHERE distinct_id = ".escape($DistinctId));
        return DBQM::result($Select, 0);
    }

    static function getVoucherNumberByDistinctId($DistinctId)
    {
        $Select = DBQM::query("SELECT voucher_number FROM ledger WHERE distinct_id = ".escape($DistinctId, 'int')." AND year_id = ".escape(Year::getYearId()));
        return DBQM::result($Select, 0);
    }

    function deleteByVoucherNumber($VoucherTypeId, $VoucherNumber) {
        DBQM::query("DELETE FROM ledger WHERE voucher_number = ".escape($VoucherNumber, 'int')." AND voucher_type_id = ".escape($VoucherTypeId, 'int')." year_id = ".escape(Year::getYearId()));
    }

    function getLedgerDetailsByLedgerId($LedgerId)
    {
        return DBQM::query("
                              SELECT
                                  ld.*,
                                  a.name AS account_name
                              FROM
                                  acc_accounts a,
                                  ledger_data ld
                              WHERE
                                  a.id = ld.account_id AND
                                  ld.ledger_id = ".escape($LedgerId, 'int')."
        ");
    }

    static function getNewVoucherNumber($VoucherTypeId)
	{
		$Select = DBQM::query("SELECT IFNULL(MAX(voucher_number)+1, 1) FROM ledger WHERE voucher_type_id = ".escape($VoucherTypeId)." AND year_id = ".escape(Year::getYearId()));
		return DBQM::result($Select, 0);
	}
}