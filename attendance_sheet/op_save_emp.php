<?php
include_once("../connection/localhost.php");
SecurityShell::redirectIfNotAllowedFormSubmission();

$oAttendanceSheet = new AttendanceSheet();
$oBranch = new Branch();

if (isset($_POST['submit']))
{
    $LogId = $_POST['log_id'];
    $StartDate = $_POST['start_time'];
    $EndDate = $_POST['end_time'];

    $isNight = isset($_POST['is_night']) ? 1 : 0;
    $oAttendanceSheet->createLogData($LogId, $_POST['employee_id'], DatabaseHelper::DATE(), $StartDate, $EndDate, $isNight);

    Utils::redirect('employee_list.php?log_id='.$LogId);
    SuccessHandler::setMessage("Log Created Successfully");
    //Utils::redirectToReferer();
}
else
    ErrorHandler::UnauthorizedAccess();