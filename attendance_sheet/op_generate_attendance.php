<?php
include_once("../connection/localhost.php");
$oAttendanceSheet = new AttendanceSheet();

if (isset($_POST['submit']))
{
    $isNight = isset($_POST['is_night']) ? 1 : 0;
    $oAttendanceSheet->generateManualAttendance($_POST['log_id'], $_POST['date_from'], $_POST['date_till'], $_POST['start_time'], $_POST['end_time'], $isNight);

    SuccessHandler::setMessage("Attendance Log Created Successfully");
    Utils::redirectToReferer();
}
elseif (isset($_POST['update_btn']))
{
    $oAttendanceSheet->deleteLogData($_POST['log_id'], $_POST['account_id']);
    $CountDataRows = count($_POST['start_time']);

    for($i=0; $i<$CountDataRows; $i++)
    {
        if (!empty($_POST['start_time'][$i]))
        {
            $oAttendanceSheet->createLogData($_POST['log_id'], $_POST['account_id'], DateEncode($_POST['date_from'][$i]), $_POST['start_time'][$i], $_POST['end_time'][$i], $_POST['is_night'][$i]);
        }
    }

    SuccessHandler::setMessage("Attendance Log Updated Successfully");
    Utils::redirectToReferer();
}
else
    ErrorHandler::UnauthorizedAccess();