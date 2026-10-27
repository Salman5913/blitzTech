<?php
include_once("../connection/localhost.php");

$oLoan = new Loan();

if ($oLoan->isValidFormSubmission())
{
    $DateTime = DateEncode($_POST['voucher_date'], 'Y-m-d H:i:s');
	foreach($_POST['advance_amount'] as $AccountId => $Amount)
	{
		if (empty($Amount)) continue;

        $oLoan->initForSave();
		$oLoan->saveMainTableData([
		    $AccountId,
            $_POST['cash_bank_id'],
            $DateTime,
            $_POST['advance_amount'][$AccountId],
            $_POST['advance_amount'][$AccountId],
            'A'
        ]);

        $oLoan->performTransactions();
	}

	SuccessHandler::setMessage("Loan Registered Successfully");
	Utils::redirectToReferer();
}