<?php
include_once("../connection/localhost.php");
SecurityShell::redirectIfNotAllowedFormSubmission();

$oChartOfAccount = new ChartOfAccount();
$oSalaryPayment = new SalaryPayment();
$oSalary = new Salary();

$oSalaryPayment->setVariablesForSaveManually($_POST['payment_id'], VoucherType::SLRY);

if (isset($_POST['submit']))
{
    $oSalaryPayment->initForSave();
    $oSalaryPayment->saveMainTableData([
        $_POST['salary_data_id'],
        $_POST['paid_from'],
        $_POST['status']
    ]);

	$SalaryId = $_POST['salary_id'];
    DBQM::query("UPDATE hr_salary_data SET is_paid = ".escape($_POST['status']).", paid_from_account_id = ".escape($_POST['paid_from'], 'int', true).", paid_distinct_id = ".escape($oSalaryPayment->getDistinctId(), 'int', true)." WHERE id = ".escape($_POST['salary_data_id']));

    if ($_POST['status'] == 'Y')
    {
        $oLedger = $oSalaryPayment->getLedgerObject();

        $oLedger->saveData($_POST['account_id'], $_POST['payment'] + ($_POST['loan_deduction']+ $_POST['advance_deduction']), "Salary Payment", $_POST['paid_from']);
        $oLedger->saveData($_POST['account_id'], "-".($_POST['loan_deduction']+$_POST['advance_deduction']),"Loan Deduction");

        $oLedger->saveData($_POST['paid_from'], "-".$_POST['payment'],"Salary Payment", $_POST['account_id']);
    }

	SuccessHandler::setMessage("Salary Payment Status Updated Successfully!");

	Utils::redirectToReferer();
}