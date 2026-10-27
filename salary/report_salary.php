<?
$_GET['_page_title'] = "Salary Report";
$_GET['_page_title_text'] = "View / Edit Salary";

include_once("../includes/inc_header.php");
if (!SecurityShell::isAccess("HR Management",	"Salary Sheet") and !SecurityShell::isAccess("HR Management", "Salary Pay Status Change"))
    ErrorHandler::UnauthorizedAccess();

$oSalary = new Salary();
$Select = $oSalary->getReport();

?>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <? $oSalary->showVoucherButtonsWithOptions([VoucherType::SM."-New"]); ?>
            <div class="table-responsive">
                <table class="table data-table-report color table-striped">
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="check_all" value="Y"> </th>
                        <th>For Month</th>
                        <th>Date</th>
                        <th>Log</th>
                        <th>Branch</th>
                        <th class="hidden">Tea Allow.</th>
                        <th class="hidden">Attendance Allow.</th>
                        <th>Number of Working Days</th>
                        <th>Number of Employees</th>
                        <th class="text-right">voucher Status</th>
                        <th><i class="fa fa-gear"></i> Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?
                    while ($Row = DBQM::fetch_assoc($Select)) {
                        $isDeleted = false;
                        $AdditionalLink = [];

                        if ($Row['is_deleted'] == 'Y') {
                            $isDeleted = true;
                        }

                        $AdditionalLink['../attendance_sheet/employee_list.php?log_id='.$Row['log_id']] = '<i class="mdi mdi-cash"></i> View';
                        if (SecurityShell::isAccess("HR Management", "Salary Pay Status Change") and !$isDeleted)
                            $AdditionalLink['pay_status_update.php?salary_id='.$Row['id']] = '<i class="mdi mdi-cash"></i> Update Pay Status';

                        if (!$isDeleted)
                            $AdditionalLink['print_salary_sheet.php?id='.$Row['id']] = '<i class="mdi mdi-printer"></i> Print Sheet';

                        ?>
                        <tr class="<? $oSalary->highlightDeletedRow($Row); ?>">
                            <td><label><input type="checkbox" name="distinct_id[<?=$Row['distinct_id']?>]" data-js="distinct_id" id="distinct_id" value="<?=$Row['distinct_id']?>"></label></td>
                            <td class="text-center"><?=DateDecode($Row['for_month'], 'M - Y')?></td>
                            <td class="text-center"><?=DateDecode($Row['date_time'], 'M d, Y')?></td>
                            <td class="text-center"><?=$Row['month_name']?></td>
                            <td class="text-center"><?=$Row['branch_name']?></td>
                            <td class="text-center hidden"><?=$Row['tea_allowance']?></td>
                            <td class="text-center hidden"><?=$Row['attendance_allowance']?></td>
                            <td class="text-center"><?=$Row['number_of_working_days']?></td>
                            <td class="text-center"><?=$Row['emp_count']?></td>
                            <td class="text-right"><? $oSalary->getVoucherStatus($Row); ?></td>
                            <td class="text-right report-actions">
                                <? $oSalary->getReportButtons(VoucherType::SM, $Row['id'], $AdditionalLink); ?>
                            </td>
                        </tr>
                    <? } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php") ?>