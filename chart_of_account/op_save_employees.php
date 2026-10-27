<?php
include_once("../connection/localhost.php");
SecurityShell::redirectIfNotAllowedFormSubmission();

$oChartOfAccount = new ChartOfAccount();

if (isset($_POST['submit']))
{
	foreach($_POST['emp_id'] as $AccountId => $EmpId)
	{
		$oChartOfAccount->updateAccountHRData(
		    $AccountId,
            $EmpId,
            $_POST['branch_id'][$AccountId],
            $_POST['basic_salary'][$AccountId],
            $_POST['can_overtime'][$AccountId],
            $_POST['cnic'][$AccountId],
            $_POST['join_date'][$AccountId],
            $_POST['is_active'][$AccountId]
        );
	}

	if (!ErrorHandler::hasError())
		SuccessHandler::setMessage("Employee Data Updated Successfully");

	Utils::redirectToReferer();
}
else
	ErrorHandler::UnauthorizedAccess();