<?php
include_once("../connection/localhost.php");
SecurityShell::redirectIfNotAllowedFormSubmission();
$oLoan = new Loan();

if ($oLoan->isValidFormSubmission())
{
    $oLoan->initForSave();
	$DateTime = DateEncode($_POST['voucher_date'], 'Y-m-d H:i:s');

    $oLoan->saveMainTableData([
        $_POST['account_id'],
        $_POST['cash_bank_id'],
        $DateTime,
        $_POST['amount'],
        $_POST['monthly_installment'],
        $_POST['type'],
        'N'
    ]);

    $oLoan->performTransactions();

    $oLoan->notifyStatusMessage();
    Utils::redirectToReferer();
}
else
	ErrorHandler::UnauthorizedAccess();