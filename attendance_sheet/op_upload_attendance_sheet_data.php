<?php
include_once("../connection/localhost.php");

if (isset($_POST['submit']))
{
	$oAttendanceSheet = new AttendanceSheet();
	$oAttendanceSheet->uploadFileNProcess($_FILES['upload_file'], $_POST['log_id']);

	SuccessHandler::setMessage("Data Imported Successfully");

	Utils::redirectToReferer();
}
else
	ErrorHandler::UnauthorizedAccess();
