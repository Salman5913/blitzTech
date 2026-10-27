<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 012, 1/12/2015
 * Time: 2:26 PM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("../../connection/localhost.php");

class SMSTemplates extends SMSManager
{
    function AdminSalesUpdated($SalesId, $VoucherTypeId)
    {
        $this->sendToCompany("Sales (".VoucherType::getName($VoucherTypeId).") Invoice # ".$SalesId." Updated by ".SecurityShell::getLoggedInUsername());
    }

    function AdminPurchaseRecorded($PurchaseId)
    {
        $this->sendToCompany("Purchase Invoice # ".$PurchaseId." Created by ".SecurityShell::getLoggedInUsername());
    }

    function AdminPurchaseUpdated($PurchaseId)
    {
        $this->sendToCompany("Purchase Invoice # ".$PurchaseId." Updated by ".SecurityShell::getLoggedInUsername());
    }

    function AdminVoucherUpdated($VoucherTypeId, $VoucherNumber)
    {
        $this->sendToCompany(VoucherType::getName($VoucherTypeId)."-".$VoucherNumber." Updated by ".SecurityShell::getLoggedInUsername());
    }

    function UserSalesRecorded($CustomerId, $SalesId, $ItemCount, $Amount, $AmountPaid, $isEdit=false)
    {

        $Balance = LedgerReport::getAccountBalance($CustomerId, DatabaseHelper::DATE());
	    $Prefix = $isEdit ? "Updated: " : '';

        $this->sendToUser($CustomerId, $Prefix."Thank you for the transaction of ".$ItemCount." item(s). Sales Voucher# is ".$SalesId." of amount ".Utils::format($Amount)."\nPAID ".Utils::format($AmountPaid)."\nCurrent Bal: ".Utils::format($Balance));
    }

	function UserPurchaseRecorded($SupplierId, $PurchaseId, $ItemCount, $Amount, $isEdit=false)
	{
		$Balance = LedgerReport::getAccountBalance($SupplierId, DatabaseHelper::DATE());

		$Prefix = $isEdit ? "Updated: " : '';

		$this->sendToUser($SupplierId, $Prefix."Thank you for the transaction of " . $ItemCount . " item(s). Purchase Voucher# is " . $PurchaseId . " of amount " . Utils::format($Amount) . "\nCurrent Bal: " . Utils::format($Balance));
	}

	function UserChequeRecorded($AccountId, $ChequeNumber, $Amount, $isEdit=false)
	{
		$Balance = LedgerReport::getAccountBalance($AccountId, DatabaseHelper::DATE());

		$oLedgerReport = new LedgerReport();
		$oLedgerReport->setDate('', DatabaseHelper::DATE());
		$PostChequesBalance = $oLedgerReport->getPostChequesAmount(ChartOfAccount::getName($AccountId));

		$Prefix = $isEdit ? "Updated: " : '';

		$this->sendToUser($AccountId, $Prefix."Cheque # " . $ChequeNumber . " of amount " . Utils::format($Amount) . " with has been recorded into your account. \nCurrent Bal: " . Utils::format($Balance + $PostChequesBalance));
	}

	function UserSalesRecordedOnSalesMobNumber($Number, $SalesId, $ItemCount, $Amount, $isEdit=false)
	{
		$Prefix = $isEdit ? "Updated: " : '';
		$this->sendToNumberDirectly($Number, $Prefix."Thank you for the transaction of ".$ItemCount." item(s). Sales Voucher# is ".$SalesId." of amount ".Utils::format($Amount));
	}

    function UserPaymentDone($AccountId, $Amount, $Description, $Date, $isEdit=false)
    {
        $Balance = LedgerReport::getAccountBalance($AccountId, DatabaseHelper::DATE());
	    $Prefix = $isEdit ? "Updated: " : '';

        $this->sendToUser($AccountId, $Prefix."You have been paid ".Utils::format($Amount)." (".$Description.") \nCurrent Bal: ".Utils::format($Balance))."\nDate: ".$Date;
    }

    function UserPaymentReceived($AccountId, $Amount, $Description, $Date, $isEdit=false)
    {
        $Balance = LedgerReport::getAccountBalance($AccountId, DatabaseHelper::DATE());
	    $Prefix = $isEdit ? "Updated: " : '';

        $this->sendToUser($AccountId, $Prefix."Your payment of ".Utils::format($Amount)." has been recorded. (".$Description.") \nCurrent Bal: ".Utils::format($Balance))."\nDate: ".$Date;
    }

    function CashBankTransaction($AccountId, $Amount, $Description, $isEdit=false)
    {
        $Balance = LedgerReport::getAccountBalance($AccountId, DatabaseHelper::DATE());
	    $Prefix = $isEdit ? "Updated: " : '';

        $this->sendToUser($AccountId, $Prefix."Transaction of ".Utils::format($Amount)." has been done on ".ChartOfAccount::getName($AccountId).". (".$Description.") \nCurrent Bal: ".Utils::format($Balance));
    }

    /**
     * @param array $EmployeeAccountIds
     * @param $ProjectName
     * @param $Amount
     * @param $Description
     */
    function UserProjectExpensePaymentDone($EmployeeAccountIds, $ProjectName, $Amount, $Description)
    {
        $this->sendToUser($EmployeeAccountIds, "You have been paid ".Utils::format($Amount)." for ".$ProjectName." Project (".$Description.")");
    }

    function UserProjectCommissionPaymentDone($EmployeeAccountId, $ProjectName, $Amount)
    {
        $this->sendToUser($EmployeeAccountId, "You have been paid commission ".Utils::format($Amount)." against ".$ProjectName." Project");
    }

    function JournalTransaction($AccountId, $Amount, $Description, $isEdit=false)
    {
        $Balance = LedgerReport::getAccountBalance($AccountId, DatabaseHelper::DATE());
	    $Prefix = $isEdit ? "Updated: " : '';

        $this->sendToUser($AccountId, $Prefix."Transaction of ".Utils::format($Amount)." has been done on your account. (".$Description.") \nCurrent Bal: ".Utils::format($Balance));
    }
}