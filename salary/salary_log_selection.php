<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Select a Log File';
$_GET['_page_title_text'] = 'Select '.'a log file from your uploaded logs or create a new log';

include_once("../includes/inc_header.php");
SecurityShell::redirectIfNoAccess("HR Management",	"Salary Sheet");


//if (isset($_GET['id']))
//    Utils::redirect(ProjectHelper::SiteURL() . "Controller/" . VoucherType::getVoucherClass(VoucherType::SM)."?voucher_type_id=".VoucherType::SM."&id=".$_GET['id']);

$oAttendanceSheet = new AttendanceSheet();
$SelectAllUnlinedLogs = $oAttendanceSheet->getAllUnlinkedLogs();

$LogName = '';
if (isset($_GET['log_id']))
{
    $SelectEmployee = $oAttendanceSheet->getAllEmployeeList($_GET['log_id']);
    $LogName = $oAttendanceSheet->getLogDetails($_GET['log_id'])['month_name'];

	$DateRange = $oAttendanceSheet->getDateRange($_GET['date_from'], $_GET['date_till']);
}

$_GET['holiday'] = isset($_GET['holiday']) ? $_GET['holiday'] : [];
?>
<script src="../js_live/chart_of_account/accounts.min.js" type="text/javascript"></script>
<? if (!isset($_GET['log_id'])) { ?>
    <div class="row">
        <div class="col-sm-12">
            <form method="get" action="">
                <div class="card">
                    <h3 class="card-title"> Log File Selection</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="log_id">Attendance Log</label>
                            <select name="log_id" id="log_id"><? Utils::getDropDownOptionsFromSelect($SelectAllUnlinedLogs, 'id', 'month_name', $_GET['log_id']); ?></select>
                        </div>
                        <div class="col-md-6">
                            <label for="start_time">Start Time</label>
                            <input type="text" name="start_time" id="start_time" placeholder="08:30">
                        </div>
                        <div class="col-md-6">
                            <label for="end_time">End Time</label>
                            <input type="text" name="end_time" id="end_time" placeholder="20:30">
                        </div>
                        <div class="col-md-6">
                            <label for="night_start_time">Night Start Time</label>
                            <input type="text" name="night_start_time" id="night_start_time" placeholder="20:30">
                        </div>
                        <div class="col-md-6">
                            <label for="night_end_time">Night End Time</label>
                            <input type="text" name="night_end_time" id="night_end_time" placeholder="08:30">
                        </div>
                        <div class="col-md-2">
                            <label for="date_from">Start Date</label>
                            <input type="text" name="date_from" id="date_from" data-type="date">
                        </div>
                        <div class="col-md-2">
                            <label for="date_till">End Date</label>
                            <input type="text" name="date_till" id="date_till" data-type="date">
                        </div>
                        <div class="col-md-12 mt-3">
                            <button class="btn button color center-block" name="submit" type="submit">Select Logs</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
<? } else { ?>
    <form method="get" action="salary_management.php">
        <div class="row">
            <div class="col-sm-8">
                <div class="card">
                    <h3 class="card-title"> Employee Shift Select for <strong><?=$LogName?></strong></h3>
                    <table class="table">
                        <? while ($RowEmployee = DBQM::fetch_assoc($SelectEmployee)) { ?>
                            <tr>
                                <td><?=$RowEmployee['emp_name']?></td>
                                <td><label><select name="emp[<?=$RowEmployee['account_id']?>]"><? Utils::getDropDownOptionsFromArray(['D' => 'Day Shift', 'N' => 'Night Shift'], strstr($RowEmployee['emp_name'], "night") ? 'N' : 'D') ?></select></label></td>
                            </tr>
                        <? } ?>
                        <tr>
                            <td colspan="2"><button class="btn button color pull-right" name="submit" type="submit">Proceed To Next Step <i class="fa fa-arrow-right"></i></button></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <h3 class="card-title"> Holiday Selection for <strong><?=$LogName?></strong></h3>
                    <table class="table">
                        <? foreach ($DateRange AS $Date) { ?>
                            <tr>
                                <td><label><input type='checkbox' name='holiday[]' value='<?=$Date?>' <? if (in_array($Date, $_GET['holiday'])) { ?>checked<? } ?>> <?=DateDecode($Date, 'l, M d, y')?></label></td>
                            </tr>
                        <? } ?>
                    </table>
                </div>
            </div>
        </div>
        <input type="hidden" name="voucher_type_id" value="<?=VoucherType::SM?>">
        <input type="hidden" name="log_id" value="<?=$_GET['log_id']?>">
        <input type="hidden" name="start_time" value="<?=$_GET['start_time']?>">
        <input type="hidden" name="end_time" value="<?=$_GET['end_time']?>">
        <input type="hidden" name="night_start_time" value="<?=$_GET['night_start_time']?>">
        <input type="hidden" name="night_end_time" value="<?=$_GET['night_end_time']?>">
        <input type="hidden" name="date_from" value="<?=$_GET['date_from']?>">
        <input type="hidden" name="date_till" value="<?=$_GET['date_till']?>">
    </form>
<? } ?>
<? include_once("../includes/inc_footer.php") ?>