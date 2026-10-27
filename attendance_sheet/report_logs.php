<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Log Report';
$_GET['_page_title_text'] = 'View / Edit Monthly Attendance Logs';

include_once("../includes/inc_header.php");

$oAttendanceSheet = new AttendanceSheet();
$Select = $oAttendanceSheet->getAllLogs();

?>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <? $oAttendanceSheet->showVoucherButtonsWithOptions([["form_type" => $oAttendanceSheet->getFormType(), "additional_param" => "", "form_class" => ""]]) ?>
            <table class="table data-table-report color table-striped">
                <thead>
                    <tr>
                        <th>Month Name</th>
                        <th>Branch Name</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th><i class="fa fa-gear"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                    <tr>
                        <td><?=$Row['month_name']?></td>
                        <td><?=$Row['branch_name']?></td>
                        <td><?=$Row['start_time']?></td>
                        <td><?=$Row['end_time']?></td>
                        <td class="text-right report-actions">
                            <div class="btn-group">
                                 <? if ($Row['no_of_attendance'] > 0) { ?>
                                <a class="btn color btn-sm" href="employee_list.php?log_id=<?=$Row['id']?>" target="_blank">
                                    <i class="mdi mdi-view-list"></i> View Employees</a>
                                <button class="btn btn-sm color dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="mdi mdi-chevron-down"></i>
                                </button>
                                <? } if (empty($Row['salary_id'])) { ?>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item action-edit" href="manage_logs.php?id=<?=$Row['id']?>"><i class="mdi mdi-pencil"></i> Edit</a>
                                </div>
                                 <? } ?>
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