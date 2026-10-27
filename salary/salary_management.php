<?
include_once("../connection/localhost.php");
SecurityShell::redirectIfNoAccess("HR Management",	"Salary Sheet");

$oSalary = new Salary();
$oSalary->initForEdit();

$SalaryId = $oSalary->getVoucherId();

$oAttendanceSheet = new AttendanceSheet();
$oLoan = new Loan();

$RowSalary = [];

if ($oSalary->isEdit() and !isset($_GET['log_id']))
{
    $RowSalary = $oSalary->getDetails();
    $_GET['log_id'] = $RowSalary['log_id'];
}

$canUpdateValues = SecurityShell::isAccess("HR Management", "Can Change Salary Values");

if (!$oSalary->isEdit())
{
	$SelectEmployee = $oAttendanceSheet->getAllEmployeeList( $_GET['log_id'] );

	$DateRange = $oAttendanceSheet->getDateRange( $_GET['date_from'], $_GET['date_till'] );
	$WorkingDays = count( $DateRange );
	$SundayCount = $oAttendanceSheet->getSundayCount( $_GET['date_from'], $_GET['date_till'] );

	$TeaAllowance = Constants::getTeaAllowance();
	$AttendanceAllowance = Constants::getAttendanceAllowance();

	$EmpLoans = $oLoan->getAllEmpLoans('', false, '', DatabaseHelper::DATE());

	$MonthDays = Constants::getDaysInMonth();

	$Holidays = $_GET['holiday'];
	$HolidayCount = count((array)$_GET['holiday']);
}
else
{
	$RowSalary = $oSalary->getDetails();
    $SelectEmployee = $oSalary->getAllEmployeeList($SalaryId);

	$DateRange = $oAttendanceSheet->getDateRange( $RowSalary['date_from'], $RowSalary['date_till'] );
	$WorkingDays = count( $DateRange );
	$SundayCount = $oAttendanceSheet->getSundayCount( $RowSalary['date_from'], $RowSalary['date_till'] );


	$TeaAllowance = $RowSalary['tea_allowance'];
	$AttendanceAllowance = $RowSalary['attendance_allowance'];

	$EmpLoans = $oLoan->getAllEmpLoans($SalaryId, false, '', DateEncode($RowSalary['date_time']));
	$MonthDays = $RowSalary['number_of_working_days'];
	$SelectHolidays = $oSalary->getHolidays($SalaryId);

	$Holidays = [];
	while($RowHoliday = DBQM::fetch_assoc($SelectHolidays))
	    $Holidays[] = $RowHoliday['holiday_date'];

	$HolidayCount = DatabaseHelper::getRecordCount($SelectHolidays);
}

$TotalBasicSalary = 0;
$TotalAdvanceTaken = 0;

include_once("../includes/inc_header.php");
?>
<style>
    input[readonly] {
        background-color: #efefef;
    }
</style>
<script src="../js_live/salary/salary_management.min.js" type="text/javascript"></script>
<form method="post" action="op_salary_management.php">
	<?$oSalary->showVoucherDeleteNotification()?>
    <div class="row">
        <div class="col-sm-8">
            <div class="card">
                <h3 class="card-title"> Log File Selection</h3>
                <div class="row">
                    <div class="col-md-12">
                        <label for="for_month">For Month</label>
                        <input data-type="date" name="for_month" id="for_month" value="<?=DateDecode($RowSalary['for_month'])?>">
                    </div>
                    <div class="col-md-12">
                        <label for="voucher_date">Date</label>
                        <input type="text" data-type="date" name="voucher_date" id="voucher_date" value="<?=DateDecode($RowSalary['voucher_date'])?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <h3 class="card-title"> Log File Selection</h3>
                <table class="table">
                    <tr>
                        <td>Attendance Allowance</td>
                        <td><?=$TeaAllowance?></td>
                    </tr>
                    <tr class="hidden">
                        <td>Full Attendance Allowance</td>
                        <td><?=Utils::format($AttendanceAllowance, 0)?></td>
                    </tr>
                    <tr>
                        <td>Total Working Days</td>
                        <td data-sunday-count="<?=$SundayCount?>"><?=$MonthDays?>
                            <input type="hidden" data-js="data-working-days" value="<?=$MonthDays?>">
                            <input type="hidden" data-js="data-sunday-count" value="<?=$SundayCount?>">
                        </td>
                    </tr>
                    <tr>
                        <td>Linked With Log</td>
                        <td><?=$oAttendanceSheet->getLogDetails($_GET['log_id'])['month_name']?></td>
                    </tr>
                    <tr>
                        <td>Number of Employees</td>
                        <td><?=DatabaseHelper::getRecordCount($SelectEmployee)?></td>
                    </tr>
                    <tr>
                        <td>Holidays</td>
                        <td><strong><?=$HolidayCount?> Holidays</strong><br>
                        <? foreach($Holidays as $holiday) {
                            echo DateDecode($holiday, 'l M d, y')."<br>";
                        } ?>
                            <input type="hidden" data-js="data-holidays-count" value="<?=$HolidayCount?>">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Employee Salary Info</h3>
                <div class="table-responsive">
                    <table class="table dynamic color salary non-editable table-bordered">
                        <thead>
                        <tr>
                            <th width="20%">Employee</th>
                            <th width="5%" class="text-center bg-soft-warning">Basic Salary</th>
                            <th class="text-center bg-soft-success">Absent</th>
                            <th class="text-center bg-soft-success">Present</th>
                            <th class="text-center bg-soft-success">gross Salary</th>
                            <th class="text-center">Attendance Allowance</th>
                            <th class="text-center">Overtime Allowance</th>
                            <th class="text-center">Night OT Allowance</th>
                            <th class="text-center">Holiday OT Allowance</th>
                            <th class="text-center">Late Hours Deduction</th>
                            <th class="text-center">Adjustment</th>
                            <th class="text-center hidden">Tea Allowance</th>
                            <th class="text-center bg-soft-dark">Loan/Advance Taken</th>
                            <th class="text-center bg-soft-dark">Advance Deduction</th>
                            <th class="text-center bg-soft-dark">Loan Deduction</th>
                            <th class="text-center bg-soft-dark">Remaining</th>
                            <th class="text-center">Net Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?
                        $i=1;
                        while ($RowEmployee = DBQM::fetch_assoc($SelectEmployee))
                        {
                            $AccountId = $RowEmployee['account_id'];

                            if (!$oSalary->isEdit())
                            {
                                $isNight = $_GET['emp'][$AccountId] == 'N';

                                $oAttendanceSheetParser = new AttendanceSheetParser( $_GET['log_id'], $AccountId, $_GET['start_time'], $_GET['end_time'], $_GET['night_start_time'], $_GET['night_end_time'], $_GET['date_from'], $_GET['date_till'], $isNight ? 'Y' : '', $_GET['holiday'] );
                                $SelectRow = $oAttendanceSheetParser->getAllRows();

                                while ( $Row = DBQM::fetch_assoc( $SelectRow ) ) {
                                    list( $Row, $Hint ) = $oAttendanceSheetParser->rowCalculate( $Row );
                                }

                                $Leave = $WorkingDays - $oAttendanceSheetParser->DaysWorked;
                                $Leave = $Leave < 0 ? 0 : $Leave;

                                $LoanDeductionAmount = $oLoan->getInstallmentAmountByAccountId($AccountId, $EmpLoans[$RowEmployee['account_id']], 'L');
                                $AdvanceDeductionAmount = $oLoan->getInstallmentAmountByAccountId($AccountId, $EmpLoans[$RowEmployee['account_id']], 'A');

                                $PerDaySalary = $RowEmployee['basic_salary'] / $MonthDays;

                                $PerHourSalary = $PerDaySalary / 9;

                                $NightOverTime = round((($RowEmployee['night_overtime_hours']) * $PerHourSalary * 1.5));
                                /*$HolidayOverTime = round((($oAttendanceSheetParser->HolidayOvertime / 60 / 60) * $PerHourSalary * 2));*/
                                $HolidayOverTime = round($oAttendanceSheetParser->HolidayOvertime * $PerDaySalary * 2);
                                $OverTime = round((($oAttendanceSheetParser->Overtime / 60 / 60) * $PerHourSalary * 1.5));
                                $OverTime += round((($oAttendanceSheetParser->SundayOvertime / 60 / 60) * $PerHourSalary * 2));
                                $LateDeduction = round((($oAttendanceSheetParser->LateHours / 60 / 60) *  $PerHourSalary * 1.5));
                                $isPaid = false;
                            }
                            else
                            {
                                $RowPayDetails = SalaryPayment::getPaymentDetails($RowEmployee['id']);
                                $RowEmployee['is_paid'] = empty($RowPayDetails['is_paid']) ? $RowEmployee['is_paid'] : $RowPayDetails['is_paid'];
                                $RowEmployee['paid_from_account_id'] = empty($RowPayDetails['paid_from_account_id']) ? $RowEmployee['paid_from_account_id'] : $RowPayDetails['paid_from_account_id'];
                                $RowEmployee['paid_distinct_id'] = empty($RowPayDetails['distinct_id']) ? $RowEmployee['paid_distinct_id'] : $RowPayDetails['distinct_id'];

                                $isNight = $RowEmployee['is_night_shift'] == 'Y';
                                $Leave = $RowEmployee['leave'];
                                $LoanDeductionAmount = $RowEmployee['loan_deduction'];
                                $AdvanceDeductionAmount = $RowEmployee['advance_deduction'];
                                $OverTime = $RowEmployee['overtime_allowance'];
                                $NightOverTime = $RowEmployee['night_overtime_allowance'];
                                $HolidayOverTime = $RowEmployee['holiday_overtime_allowance'];
                                $LateDeduction = $RowEmployee['late_hours_deduction'];

                                $isPaid = $RowEmployee['is_paid'] == 'Y';
                            }

                            $TotalBasicSalary += $RowEmployee['basic_salary'];
                            $TotalAdvanceTaken += $EmpLoans[$RowEmployee['account_id']];
                            ?>
                            <tr>
                                <td class="hidden"><?=$i++?>
                                    <input type="hidden" name="is_night[<?=$AccountId?>]" data-js="is_night" value="<?=$isNight ? 'Y' : 'N'?>">
                                    <input type="hidden" name="account_id[<?=$AccountId?>]" value="<?=$RowEmployee['account_id']?>">
                                    <input type="hidden" name="net_salary[<?=$AccountId?>]" data-js="net_salary_input" value="0">
                                    <input type="hidden" name="is_paid[<?=$AccountId?>]" value="<?=!empty($RowEmployee['is_paid']) ? $RowEmployee['is_paid'] : 'N'?>">
                                    <input type="hidden" name="paid_distinct_id[<?=$AccountId?>]" value="<?=$RowEmployee['paid_distinct_id']?>">
                                    <input type="hidden" name="paid_from[<?=$AccountId?>]" value="<?=$RowEmployee['paid_from_account_id']?>">
                                    <input type="hidden" name="salary_data_id[<?=$AccountId?>]" value="<?=$RowEmployee['id']?>">
                                </td>
                                <td class="text">
                                    <label>
                                        <?=$RowEmployee['emp_name']?> (EId: <?=$RowEmployee['emp_id']?>)
                                        <a href="../attendance_sheet/analyzer.php?<?=Utils::GETtoURLParams('emp')?>&account_id[]=<?=$AccountId?><?=$isNight ? '&is_night_shift=Y' : ''?>" target="_blank">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </label>
                                </td>

                                <td class="text-center bg-soft-warning" data-js="data-basic-salary" data-basic-salary="<?=$RowEmployee['basic_salary']?>"><?=Utils::format($RowEmployee['basic_salary'], 0)?></td>

                                <td data-absent="<?=$Leave?>">
                                    <label>
                                        <input type="text" <?=($isPaid) ? 'readonly' : '' ?> name="absent[<?=$AccountId?>]" data-js="absent" data-is-updated-absent="N" size="3" data-type="float" value="<?=$Leave?>"></label></td>

                                <td class="text-center bg-soft-success" data-js="present"></td>
                                <td class="text-center bg-soft-success" data-js="gross_salary"></td>
                                <td class="text-center"><label><input type="text" <?=($isPaid or !$canUpdateValues) ? 'readonly' : '' ?> name="attendance_allowance[<?=$AccountId?>]" data-js="attendance_allowance" readonly size="3" data-type="float" value="<?=$AttendanceAllowance?>"></label></td>
                                <td class="text-center"><label><input type="text" <?=($isPaid or !$canUpdateValues) ? 'readonly' : '' ?> name="overtime_allowance[<?=$AccountId?>]" data-js="overtime_allowance" size="5" data-type="float" value="<?=$OverTime?>"></label></td>
                                <td class="text-center"><label><input type="text" <?=($isPaid or !$canUpdateValues) ? 'readonly' : '' ?> name="night_overtime_allowance[<?=$AccountId?>]" data-js="night_overtime_allowance" size="5" data-type="float" value="<?=empty($NightOverTime) ? 0 : $NightOverTime?>"></label></td>
                                <td class="text-center"><label><input type="text" <?=($isPaid or !$canUpdateValues) ? 'readonly' : '' ?> name="holiday_overtime_allowance[<?=$AccountId?>]" data-js="holiday_overtime_allowance" size="5" data-type="float" value="<?=empty($HolidayOverTime) ? 0 : $HolidayOverTime?>"></label></td>
                                <td class="text-center"><label><input type="text" <?=($isPaid or !$canUpdateValues) ? 'readonly' : '' ?> name="late_hours_deduction[<?=$AccountId?>]" data-js="late_hours_deduction" size="5" data-type="float" value="<?=$LateDeduction?>"></label></td>
                                <td class="text-center">
                                    <label>
                                        <input type="text" name="adjustment[<?=$AccountId?>]" data-js="adjustment" data-type="float" size="5" value="<?=!empty($RowEmployee['adjustment'])?$RowEmployee['adjustment']:0?>">
                                    </label>
                                </td>
                                <td class="text-center hidden"><label><input type="text" <?=($isPaid or !$canUpdateValues) ? 'readonly' : '' ?> name="tea_allowance[<?=$AccountId?>]" data-js="tea_allowance" readonly size="3" data-type="float" value="<?=$TeaAllowance?>"></label></td>
                                <td class="text-center bg-soft-dark" data-js="data-loan-amount" data-loan-amount="<?=round($EmpLoans[$RowEmployee['account_id']])?>"><?=Utils::format($EmpLoans[$RowEmployee['account_id']], 0)?></td>
                                <td class="text-center"><label><input type="text" <?=($isPaid or !$canUpdateValues) ? 'readonly' : '' ?> name="advance_deduction[<?=$AccountId?>]" data-js="advance_deduction" size="3" data-type="float" value="<?=$AdvanceDeductionAmount?>"></label></td>
                                <td class="text-center"><label><input type="text" <?=($isPaid or !$canUpdateValues) ? 'readonly' : '' ?> name="loan_deduction[<?=$AccountId?>]" data-js="loan_deduction" size="3" data-type="float" value="<?=$LoanDeductionAmount?>"></label></td>
                                <td class="text-center bg-soft-dark" data-js="loan_remaining"></td>
                                <td class="text-right text font-weight-bolder" data-js="net_salary"></td>
                            </tr>
                        <? } ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td></td>
                            <td class="font-weight-bolder text-right"><?=Utils::format($TotalBasicSalary, 0)?></td>
                            <td></td>
                            <td></td>
                            <td class="text-right font-weight-bolder" data-js="total_gross_salary"></td>
                            <td></td>
                            <td class="font-weight-bolder text-center" data-js="total_overtime"></td>
                            <td class="font-weight-bolder text-center" data-js="total_night_overtime"></td>
                            <td class="font-weight-bolder text-center hidden" data-js="total_holiday_overtime"></td>
                            <td class="font-weight-bolder text-center" data-js="total_late"></td>
                            <td class="font-weight-bolder text-center" data-js="total_adjustment"></td>
                            <td class="hidden"></td>
                            <td class="font-weight-bolder text-center" data-js="total_advance_deduction"></td>
                            <td class="font-weight-bolder text-right"><?=Utils::format($TotalAdvanceTaken, 0)?></td>
                            <td class="font-weight-bolder text-center" data-js="total_loan_deduction"></td>
                            <td class="font-weight-bolder text-right"></td>
                            <td class="font-weight-bolder text-right"></td>
                            <td class="text-right font-weight-bolder" data-js="total_salary_full" >0</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="log_id" value="<?=$_GET['log_id']?>">
	<? if (!$oSalary->isEdit()) { ?>
        <input type="hidden" name="date_from" value="<?=$_GET['date_from']?>">
        <input type="hidden" name="date_till" value="<?=$_GET['date_till']?>">
    <? } ?>
    <? Utils::ArrayToInputFields($_GET['holiday'], 'holiday'); ?>
    <? $oSalary->showVoucherButtons()?>
</form>
<? include_once("../includes/inc_footer.php") ?>