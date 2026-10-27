<?php
include_once("../connection/localhost.php");
SecurityShell::redirectIfNotAllowedFormSubmission();
//error_reporting(E_ALL);
$oSalary = new Salary();

if ($oSalary->isValidFormSubmission())
{
    $oSalary->initForSave();
	$DateTime = DateEncode($_POST['voucher_date'], 'Y-m-d H:i:s');

    $oSalary->saveMainTableData([
        $_POST['log_id'],
        DateEncode($_POST['salary_date']),
        DateEncode($_POST['for_month']),
        Constants::getTeaAllowance(),
        Constants::getAttendanceAllowance(),
        Constants::getDaysInMonth(),
        DateEncode($_POST['date_from']),
        DateEncode($_POST['date_till'])
    ]);

    if($oSalary->isVoucherCreatedSuccessfully())
    {
        DBQM::query("DELETE FROM hr_salary_holiday WHERE salary_id = ".escape($oSalary->getVoucherId()));

        foreach ($_POST['holiday'] as $HolidayDate)
            $oSalary->saveDataTableData([$HolidayDate], Salary::IND_SALARY_HOLIDAY);

        foreach ($_POST['account_id'] AS $EmpId => $AccountId)
        {
            $BasicSalary = ChartOfAccount::getBasicSalaryByAccountId($AccountId);
            $oSalaryData = new DatabaseInsertUpdateHelper('hr_salary_data');

            $oSalaryData->saveData([
                $oSalary->getVoucherId(),
                $AccountId,
                $_POST['is_night'][$EmpId],
                $_POST['absent'][$EmpId],
                $BasicSalary,
                $_POST['tea_allowance'][$EmpId],
                $_POST['attendance_allowance'][$EmpId],
                $_POST['overtime_allowance'][$EmpId],
                $_POST['night_overtime_allowance'][$EmpId],
                $_POST['holiday_overtime_allowance'][$EmpId],
                $_POST['late_hours_deduction'][$EmpId],
                $_POST['adjustment'][$EmpId],
                $_POST['loan_deduction'][$EmpId],
                $_POST['advance_deduction'][$EmpId],
                $_POST['net_salary'][$EmpId],
                $_POST['is_paid'][$EmpId],
                $_POST['paid_from'][$EmpId],
                $_POST['paid_distinct_id'][$EmpId]
            ], $_POST['salary_data_id'][$EmpId]);

            if (!empty($_POST['salary_data_id'][$EmpId]) AND ($oSalary->isEdit() || $oSalary->isDelete()))
                $oSalary->deleteSalaryLoanInstallmentBySalaryDataId($_POST['salary_data_id'][$EmpId]);
        }

        $oSalary->performTransactions();
    }

    $oSalary->notifyStatusMessage();
	$oSalary->redirectToStatus(get_class($oSalary), VoucherType::SM);
}
