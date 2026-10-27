<?php
include_once("../connection/localhost.php");

$oProjectWisePayment = new ProjectWisePayment();

if ($oProjectWisePayment->isValidFormSubmission())
{
    foreach ($_POST['project_amount'] as $ProjectId => $AdjustedAmount)
    {
        if ($AdjustedAmount > 0)
        {
            $_POST['_project_id'] = $ProjectId;
            $Description = "Overall Received: ".Utils::format($_POST['amount'])."<br>";
            $Description .= $_POST['description'];

            $oProjectWisePayment->initForSave();
            $oProjectWisePayment->saveMainTableData([
                $_POST['cash_bank_id'],
                $_POST['cheque_number'],
                DateEncode($_POST['cheque_date']),
                $_POST['cheque_bank'],
                $_POST['payment_type'],
                $AdjustedAmount,
                $Description
            ]);

            if ($oProjectWisePayment->isVoucherCreatedSuccessfully())
                $oProjectWisePayment->performTransactions();

            $oProjectWisePayment->updateVoucherTotal($AdjustedAmount);
        }
    }

    SuccessHandler::setMessage("Project wise Payment has been received successfully.");
    Utils::redirectToReferer();
}