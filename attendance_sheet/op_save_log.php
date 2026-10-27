<?php
include_once("../connection/localhost.php");
SecurityShell::redirectIfNotAllowedFormSubmission();

$oAttendanceSheet = new AttendanceSheet();

if ($oAttendanceSheet->isValidFormSubmission())
{
    $oAttendanceSheet->initForSave();

    $StartDate = date("Y-m-01", strtotime($_POST['month']));
    $EndDate = date("Y-m-t", strtotime($_POST['month']));

    $oAttendanceSheet->saveMainTableData([
        $_POST['name'],
        $_POST['branch_id'],
        $StartDate,
        $EndDate,
        $_POST['start_time'],
        $_POST['end_time']
    ]);

	if (!$oAttendanceSheet->isEdit())
	{
        SuccessHandler::setMessage("Log Created Successfully");
        Utils::redirect("analyzer.php?log_id=".$oAttendanceSheet->getVoucherId());
    }
    elseif ($oAttendanceSheet->isEdit())
    {
        SuccessHandler::setMessage("Log Updated Successfully");
        Utils::redirect("report_logs.php");
    }
    elseif ($oAttendanceSheet->isDelete())
    {
        $oAttendanceSheet->deleteFormData();
        $isDeleted = empty($oAttendanceSheet->getLogDetails($oAttendanceSheet->getVoucherId())['id']);

        if (!$isDeleted)
        {
            ErrorHandler::setError( "Unable to delete this log, this log is linked with some attendance sheet." );
            Utils::redirectToReferer();
        }
        else
        {
            SuccessHandler::setMessage("Log Deleted Successfully!");
            Utils::redirect("report_logs.php");
        }
    }
	else
        ErrorHandler::setError("Unable to create new log, please try again");

	Utils::redirectToReferer();
}
else
    ErrorHandler::UnauthorizedAccess();