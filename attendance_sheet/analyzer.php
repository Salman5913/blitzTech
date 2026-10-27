<?
include_once("../connection/localhost.php");

if (empty($_GET['log_id']))
    ErrorHandler::showOnErrorPage("Invalid Log Id");

$oAttendanceSheet = new AttendanceSheet();
$oSalary = new Salary();

$Select = false;

$SelectEmployees = $oAttendanceSheet->getAllEmployeeList($_GET['log_id']);
$DateRange = $oAttendanceSheet->getDateRange($_GET['date_from'], $_GET['date_till']);

$RowLogDetails = $oAttendanceSheet->getLogDetails($_GET['log_id']);
$LogName = $RowLogDetails['month_name'];

$_GET['start_time'] = $RowLogDetails['start_time'];
$_GET['end_time'] = $RowLogDetails['end_time'];

$_GET['_page_title'] = 'Attendance Sheet Reporting - '.$LogName;
$_GET['_page_title_text'] = 'View Employee Attendance Report';

include_once("../includes/inc_header.php");
?>
<style>
span.theme-color {
    color: #ff0000;
}
</style>
<script src="../js_live/attendance_sheet/analyzer.min.js"></script>
<? if (empty($oSalary->getSalaryIdByLogId($_GET['log_id']))) { ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <form method="post" enctype="multipart/form-data" action="op_upload_attendance_sheet_data.php">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="card-title"> New Excel File Upload</h3>
                        </div>
                        <div class="col-sm-6 text-right">
                            <a href="../assets/uploads/payroll/download.xlsx" target="_blank" download="download" class="shadow-sm button btn-sm bg-soft-info font-weight-bold"><i class="mdi mdi-download"></i> Download Template</a>
                        </div>
                    </div>
                    <table class="table">
                        <tr>
                            <td><label for="upload_file">Upload File</label><br></td>
                            <td><input type="file" id="upload_file" name="upload_file" /></td>
                            <td><button type="submit" name="submit" class="btn button color"><i class="fa fa-upload"></i> Upload</button></td>
                        </tr>
                    </table>
                    <input type="hidden" name="log_id" value="<?=$_GET['log_id']?>">
                </form>
            </div>
        </div>
    </div>
<? } ?>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <form method="get" action="">
                <h3 class="card-title"> Filter</h3>
                <table class="table color">
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="select_all_none"> </th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Night Start Time</th>
                        <th>Night End Time</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                    </tr>
                    </thead>
                    <? while ($RowEmployee = DBQM::fetch_assoc($SelectEmployees)) { ?>
                        <tr style="height: 50px">
                            <td><label><input type="checkbox" class="check_all" name="account_id[]" <?=(in_array($RowEmployee['account_id'], (array)$_GET['account_id'])) ? 'checked' : '' ?> value="<?=$RowEmployee['account_id']?>"> <?=$RowEmployee['emp_id']?>)&nbsp; <?=$RowEmployee['emp_name']?></label>
                            </td>
                            <td><label><input type="text" name="start_time" placeholder="10:30" value="<?=$_GET['start_time']?>"></label></td>
                            <td><label><input type="text" name="end_time" placeholder="20:00" value="<?=$_GET['end_time']?>"></label></td>
                            <td><label><input type="text" name="night_start_time" placeholder="20:30" value="<?=$_GET['night_start_time']?>"></label></td>
                            <td><label><input type="text" name="night_end_time" placeholder="08:30" value="<?=$_GET['night_end_time']?>"></label></td>
                            <td><label><input type="text" name="date_from" data-type="date" value="<?=$_GET['date_from']?>"></label></td>
                            <td><label><input type="text" name="date_till" data-type="date" value="<?=$_GET['date_till']?>"></label></td>
                        </tr>
                    <? } ?>
                </table>
                <input type="hidden" name="log_id" value="<?=$_GET['log_id']?>">
                <button type="submit" name="submit" class="btn button color center-block"><i class="fa fa-filter"></i> Filter</button>
            </form>
        </div>
    </div>
</div>

<? foreach($_GET['account_id'] AS $AccountId) {
    $oAttendanceSheetParser = new AttendanceSheetParser($_GET['log_id'], $AccountId, $_GET['start_time'], $_GET['end_time'], $_GET['night_start_time'], $_GET['night_end_time'], $_GET['date_from'], $_GET['date_till'], $_GET['is_night_shift'], []);
    $Select = $oAttendanceSheetParser->getAllRows();
    ?>
<div class="row">
	<div class="col-sm-9">
		<div class="card">
			<h3 class="card-title">
                <strong><?=$oAttendanceSheet->getNameById($AccountId)?></strong> (EMP Id: <strong><?=$oAttendanceSheetParser->getEmployeeIdByAccountId($AccountId)?></strong>)
            </h3>
			<table class="table color table-striped">
				<thead>
					<tr>
						<th>Date</th>
						<th>Time In</th>
						<th>Time Out</th>
                        <th>&nbsp;</th>
					</tr>
				</thead>
				<?

                while ($Row = DBQM::fetch_assoc($Select)) {
                    list($Row, $Hint) = $oAttendanceSheetParser->rowCalculate($Row);
				    ?>
                    <tr>
                        <td width="30%"><?=DateDecode($Row['date'], 'D, M d, Y')?></td>
                        <td width="20%"><?=date("h:i a", strtotime($Row['time_in']))?> &nbsp; <?=strtotime($Row['time_in']) > strtotime($oAttendanceSheetParser->StartTime) ? '<span class="theme-color">Late</span>' : ''?></td>
                        <td width="20%"><?=date("h:i a", strtotime($Row['time_out']))?> &nbsp; <?=strtotime($Row['time_out']) < strtotime($oAttendanceSheetParser->EndTime) ? '<span class="theme-color">Early</span>' : ''?></td>
                        <td width="30%"><?=$Hint?></td>
                    </tr>
                <? } ?>
			</table>
		</div>
	</div>
    <div class="col-sm-3">
        <div class="card">
            <h3 class="card-title"> Overview</h3>
            <table class="table color table-striped">
                <tr>
                    <td>Late Hours</td>
                    <td><?=round($oAttendanceSheetParser->LateHours / 60 / 60, 2)?></td>
                </tr>
                <tr>
                    <td>Overtime</td>
                    <td><?=round($oAttendanceSheetParser->Overtime / 60 / 60, 2);?></td>
                </tr>
                <tr>
                    <td>Sunday</td>
                    <td><?=$oAttendanceSheetParser->SundayCount; ?></td>
                </tr>
                <tr>
                    <td>Leave</td>
                    <?
                    /**
                     *  As we use dates from 1st of last month to 1st of next month, not 31st of last month so we have 1 day extra always
                     */  
                    ?>
                    <td><?=((count($DateRange)) - $oAttendanceSheetParser->DaysWorked); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
<? } ?>
<? include_once("../includes/inc_footer.php"); ?>