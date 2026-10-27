<?
include_once("../connection/localhost.php");

$oAttendanceSheet = new AttendanceSheet();
$Select = $oAttendanceSheet->getAllEmployeesByLog($_GET['log_id']);

$RowLogDetails = $oAttendanceSheet->getLogDetailsDetailedView($_GET['log_id']);
$LogName = $RowLogDetails['month_name'];
$StartDate = DateDecode($RowLogDetails['start_date']);
$EndDate = DateDecode($RowLogDetails['end_date']);
$StartTime = $RowLogDetails['start_time'];
$EndTime = $RowLogDetails['end_time'];

$_GET['_page_title'] = 'Employee List of '. $LogName;
$_GET['_page_title_text'] = 'View / Edit Logs';

include_once("../includes/inc_header.php");
?>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="card-title"> search Result Report</h3>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="add_employee.php?log_id=<?=$_GET['log_id']?>" class="shadow-sm button btn-sm bg-soft-info font-weight-bold" target="_blank"><i class="mdi mdi-plus"></i> Add Employee</a>
                </div>
            </div>
            <table class="table data-table-report color table-striped">
                <thead>
                    <tr>
                        <th>Account Id</th>
                        <th><i class="fa fa-gear"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                    <tr>
                        <td><?=$Row['name']?></td>
                        <td class="text-right report-actions">
                            <div class="btn-group">
                                <a class="btn color btn-sm" href="update_employee_attendance.php?log_id=<?=$_GET['log_id']?>&account_id=<?=$Row['account_id']?>&view=true" target="_blank">
                                    <i class="mdi mdi-eye"></i> View</a>
                                <button class="btn btn-sm color dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="mdi mdi-chevron-down"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item"
                                       href="analyzer.php?log_id=<?=$_GET['log_id']?>&account_id[]=<?=$Row['account_id']?>&date_from=<?=$StartDate?>&date_till=<?=$EndDate?>&start_time=<?=$StartTime?>&end_time=<?=$EndTime?>">
                                        <i class="mdi mdi-view-list"></i> Attendance Log</a>
                                    <a class="dropdown-item action-edit" href="update_employee_attendance.php?log_id=<?=$_GET['log_id']?>&account_id=<?=$Row['account_id']?>">
                                        <i class="mdi mdi-pencil"></i> Edit</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <? } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php") ?>