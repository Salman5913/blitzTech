<?
include_once("../connection/localhost.php");

if (empty($_GET['log_id']))
    ErrorHandler::showOnErrorPage("Invalid Log Id");

$oAttendanceSheet = new AttendanceSheet();
$oSalary = new Salary();

$Select = false;
$DateRange = $oAttendanceSheet->getDateRange($_GET['date_from'], $_GET['date_till']);

$LogName = $oAttendanceSheet->getLogDetails($_GET['log_id'])['month_name'];

$_GET['_page_title'] = 'Attendance Sheet Reporting - '.$LogName;
$_GET['_page_title_text'] = 'View Employee Attendance Sheet';

include_once("../includes/inc_header.php");
?>
<style>
span.theme-color {
    color: #ff0000;
}
</style>
<script src="../js_live/attendance_sheet/analyzer.min.js"></script>
<div class="row">
	<div class="col-sm-12">
		<div class="card">
			<form method="post" action="op_generate_attendance.php">
				<h3 class="card-title"> Generate Manual Attendance</h3>
				<table class="table color">
					<thead>
						<tr>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Start Date</th>
                            <th>End Date</th>
						</tr>
					</thead>
					<tr style="height: 50px">
                        <td><label><input type="text" name="start_time" placeholder="08:30" value="<?=$_GET['time_in']?>"></label></td>
                        <td><label><input type="text" name="end_time" placeholder="20:30" value="<?=$_GET['time_out']?>"></label></td>
                        <td><label><input type="text" name="date_from" data-type="date" value="<?=$_GET['date']?>"></label></td>
                        <td><label><input type="text" name="date_till" data-type="date" value="<?=$_GET['date_till']?>"></label></td>
                    </tr>
				</table>
                <input type="hidden" name="log_id" value="<?=$_GET['log_id']?>">
                <button type="submit" name="submit" class="btn button color center-block"><i class="fa fa-save"></i> Generate</button>
			</form>
		</div>
	</div>
</div>
<? include_once("../includes/inc_footer.php"); ?>