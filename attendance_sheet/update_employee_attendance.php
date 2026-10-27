<?
include_once("../connection/localhost.php");

if (empty($_GET['log_id']))
    ErrorHandler::showOnErrorPage("Invalid Log Id");

$oAttendanceSheet = new AttendanceSheet();

$Select = false;
$SelectData = $oAttendanceSheet->getLogData($_GET['log_id'], $_GET['account_id']);

$DataRows = DatabaseHelper::getRecordCount($SelectData);
$LogName = $oAttendanceSheet->getLogDetails($_GET['log_id'])['month_name'];

$_GET['_page_title'] = 'Attendance Sheet Reporting - '. $LogName;
$_GET['_page_title_text'] = 'View / Edit '.ChartOfAccount::getName($_GET['account_id']).' Attendance Report';

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
                <h3 class="card-title"> <?=ChartOfAccount::getName($_GET['account_id'])?> Attendance Report</h3>
                <table class="table color dynamic">
                    <thead>
                        <tr>
                            <th width="3%" class="text-center">#</th>
                            <th width="15%">Start Time</th>
                            <th width="15%">End Time</th>
                            <th width="15%" class="bg-soft-dark">Start Date</th>
                            <th width="52%">Is Night</th>
                        </tr>
                    </thead>
                    <? $i=0; while ($Row = DBQM::fetch_assoc($SelectData) or $i < $DataRows) { ?>
                        <tr>
                            <td class="text-center"><?=$i+1?></td>
                            <td><label><input type="text" name="start_time[]" placeholder="09:00" value="<?=$Row['time_in']?>"></label></td>
                            <td><label><input type="text" name="end_time[]" placeholder="18:00" value="<?=$Row['time_out']?>"></label></td>
                            <td class="bg-soft-dark"><label><input type="text" name="date_from[]" data-type="date" value="<?=DateDecode($Row['date'])?>"></label></td>
                            <td><label><select name="is_night[]" data-js="is_night"><? Utils::getDropDownOptionsFromArray([0 => 'No', 1 => 'Yes'], $Row['is_night']);?></select></label></td>
                        </tr>
                    <? $i++; } ?>
                </table>
                <input type="hidden" name="log_id" value="<?=$_GET['log_id']?>">
                <input type="hidden" name="account_id" value="<?=$_GET['account_id']?>">
                <button type="submit" name="update_btn" class="btn button color center-block"><i class="fa fa-save"></i> Update</button>
            </form>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php"); ?>