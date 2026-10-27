<?php
include_once ("../connection/localhost.php");

$oAttendanceSheet = new AttendanceSheet();
$oLoan = new Loan();
$oSalary = new Salary();
$oSalary->initForView();

$SalaryId = $oSalary->getVoucherId();
$RowSalary = [];
$isEdit = $oSalary->isEdit();
if ($isEdit and !isset($_GET['log_id']))
{
	$RowSalary = $oSalary->getDetails();
	$_GET['log_id'] = $RowSalary['log_id'];
}

$RowSalary = $oSalary->getDetails();
$SelectEmployee = $oSalary->getAllEmployeeList($SalaryId);

$DateRange = $oAttendanceSheet->getDateRange( $RowSalary['date_from'], $RowSalary['date_till'] );
$WorkingDays = count( $DateRange );

$TeaAllowance = $RowSalary['tea_allowance'];
$AttendanceAllowance = $RowSalary['attendance_allowance'];

$EmpLoans = $oLoan->getAllEmpLoans($SalaryId, false, '', DateEncode($RowSalary['date_time']));

$MonthDays = $RowSalary['number_of_working_days'];
$TotalSalary = 0;
$TotalBasicSalary = 0;
$TotalGrossSalary = 0;
$TotalAttendanceAllowance = 0;
$TotalOvertime = 0;
$TotalNightOvertime = 0;
$TotalLate = 0;
$TotalAdjustment = 0;
$TotalTeaAllowance = 0;
$TotalAdvanceTaken = 0;
$TotalAdvanceDeduction = 0;
$TotalLoanDeduction = 0;
$TotalRemaining = 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Salary Sheet </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?=ProjectHelper::SiteURL()?>assets/images/favicon.ico">
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/bootstrap.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/app.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/print.css" id="app-style" rel="stylesheet" type="text/css" />
</head>
<style>
    .table-overview tr td, .table-overview tr th{
        padding: 7px;
        border: 1px solid;
    }
</style>
<body data-layout="detached" data-topbar="colored" id="sales_print">
    <div class="row justify-content-center print-container">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <table class="table-overview" width="100%">
                        <tr>
                            <td>For Month of</td>
                            <td class="font-weight-bolder"><?=DateDecode($RowSalary['for_month'], 'M Y')?></td>
                            <td>Salary Date</td>
                            <td><?=DateDecode($RowSalary['date_time'], 'M d, Y')?></td>
                        </tr>
                        <tr>
                            <td>Tea Allowance</td>
                            <td><?=$TeaAllowance?></td>
                            <td>Full Attendance Allowance</td>
                            <td><?=Utils::format($AttendanceAllowance, 0)?></td>
                        </tr>
                        <tr>
                            <td>Total Working Days</td>
                            <td data-working-days="<?=$MonthDays?>"><?=$MonthDays?></td>
                            <td>Linked With Log</td>
                            <td><?=$oAttendanceSheet->getLogDetails($_GET['log_id'])['month_name']?></td>
                        </tr>
                        <tr>
                            <td>Number of Employees</td>
                            <td colspan="3"><?=DatabaseHelper::getRecordCount($SelectEmployee)?></td>
                        </tr>
                    </table>
                    <h3> Employee Salary Info</h3>
                    <table class="table-overview" width="100%" style="zoom: 80%">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">S.No</th>
                                <th width="25%">Name</th>
                                <th width="5%" class="text-center">Salary</th>
                                <th class="text-center">Absent</th>
                                <th class="text-center">Present</th>
                                <th class="text-center">Gross Salary</th>
                                <th class="text-center">Attd. Allowance</th>
                                <th class="text-center">Overtime</th>
                                <th class="text-center">Night Overtime</th>
                                <th class="text-center">Holiday Overtime</th>
                                <th class="text-center">Late</th>
                                <th class="text-center">Adjustment</th>
                                <th class="text-center">Tea Allowance</th>
                                <th class="text-center">Advance/Loan Taken</th>
                                <th class="text-center">Advance Deduction</th>
                                <th class="text-center">Loan Deduction</th>
                                <th class="text-center">Remaining</th>
                                <th class="text-center">Net Salary</th>
                                <th class="text-center" width="10%">Signature</th>
                            </tr>
                        </thead>
                        <?
                        $i=1;
                        while ($RowEmployee = DBQM::fetch_assoc($SelectEmployee))
                        {
                            $AccountId = $RowEmployee['account_id'];

                            $isNight = $RowEmployee['is_night_shift'] == 'Y';
                            $Leave = $RowEmployee['leave'];
                            $LoanDeductionAmount = $RowEmployee['loan_deduction'];
                            $AdvanceDeductionAmount = $RowEmployee['advance_deduction'];
                            $OverTime = $RowEmployee['overtime_allowance'];
                            $NightOvertime = $RowEmployee['night_overtime_allowance'];
                            $HolidayOvertime = $RowEmployee['holiday_overtime_allowance'];
                            $LateHourDeduction = $RowEmployee['late_hours_deduction'];
                            $Adjustment = $RowEmployee['adjustment'];

                            $isPaid = $RowEmployee['is_paid'] == 'Y';

                            $PresentDays = $RowSalary['number_of_working_days'] - $RowEmployee['leave'];
                            $RowTeaAllowance = $RowEmployee['tea_allowance'];
                            $RowAttendanceAllowance = $RowEmployee['attendance_allowance'];

                            $Salary = ($RowEmployee['basic_salary'] / $RowSalary['number_of_working_days']) * ($RowSalary['number_of_working_days'] - $RowEmployee['leave']);
                            $GrossSalary = $Salary;

                            $NetSalary = $GrossSalary + $RowAttendanceAllowance + $RowTeaAllowance + $OverTime + $NightOvertime + $HolidayOvertime - $LateHourDeduction - $LoanDeductionAmount - $AdvanceDeductionAmount + $Adjustment;
                            $TotalSalary += $NetSalary;
                            ?>
                            <tr>
                                <td><?=$i++?></td>
                                <td><?=$RowEmployee['emp_name']?> (EId: <?=$RowEmployee['emp_id']?>)</td>
                                <td class="text-right"><?=Utils::format($RowEmployee['basic_salary'], 0); $TotalBasicSalary += $RowEmployee['basic_salary']; ?></td>
                                <td class="text-center"><?=$Leave?></td>
                                <td class="text-center"><?=$PresentDays?></td>
                                <td class="text-right"><?=Utils::format($GrossSalary, 0); $TotalGrossSalary += $GrossSalary; ?></td>
                                <td class="text-center"><?=Utils::format($RowAttendanceAllowance, 0); $TotalAttendanceAllowance += $RowAttendanceAllowance; ?></td>
                                <td class="text-center"><?=Utils::format($OverTime, 0); $TotalOvertime += $OverTime?></td>
                                <td class="text-center"><?=Utils::format($NightOvertime, 0); $TotalNightOvertime += $NightOvertime; ?></td>
                                <td class="text-center"><?=Utils::format($HolidayOvertime, 0); $TotalHolidayOvertime += $HolidayOvertime; ?></td>
                                <td class="text-center"><?=Utils::format($LateHourDeduction, 0); $TotalLate += $LateHourDeduction; ?></td>
                                <td class="text-center"><?=Utils::format($Adjustment, 0); $TotalAdjustment += $Adjustment; ?></td>
                                <td class="text-center"><?=Utils::format($RowTeaAllowance, 0); $TotalTeaAllowance += $RowTeaAllowance; ?></td>
                                <td class="text-right"><?=Utils::format($EmpLoans[$RowEmployee['account_id']], 0); $TotalAdvanceTaken += $EmpLoans[$RowEmployee['account_id']]; ?></td>
                                <td class="text-right"><?=Utils::format($AdvanceDeductionAmount, 0); $TotalAdvanceDeduction += $AdvanceDeductionAmount; ?></td>
                                <td class="text-right"><?=Utils::format($LoanDeductionAmount, 0); $TotalLoanDeduction += $LoanDeductionAmount; ?></td>
                                <td class="text-right"><?=Utils::format($EmpLoans[$RowEmployee['account_id']] - $LoanDeductionAmount - $AdvanceDeductionAmount, 0)?></td>
                                <td class="text-right"><?=Utils::format($NetSalary, 0)?></td>
                                <td class="text-center">&nbsp;</td>
                            </tr>
                        <? } ?>
                        <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="text-right font-weight-bolder"><?=Utils::format($TotalBasicSalary, 0)?></td>
                            <td></td>
                            <td></td>
                            <td class="text-right font-weight-bolder"><?=Utils::format($TotalGrossSalary, 0)?></td>
                            <td class="text-center font-weight-bolder"><?=Utils::format($TotalAttendanceAllowance, 0)?></td>
                            <td class="text-center font-weight-bolder"><?=Utils::format($TotalOvertime, 0)?></td>
                            <td class="text-center font-weight-bolder"><?=Utils::format($TotalNightOvertime, 0)?></td>
                            <td class="text-center font-weight-bolder"><?=Utils::format($TotalHolidayOvertime, 0)?></td>
                            <td class="text-center font-weight-bolder"><?=Utils::format($TotalLate, 0)?></td>
                            <td class="text-center font-weight-bolder"><?=Utils::format($TotalAdjustment, 0)?></td>
                            <td class="text-center font-weight-bolder"><?=Utils::format($TotalTeaAllowance, 0)?></td>
                            <td class="text-right font-weight-bolder"><?=Utils::format($TotalAdvanceTaken, 0)?></td>
                            <td class="text-right font-weight-bolder"><?=Utils::format($TotalAdvanceDeduction, 0)?></td>
                            <td class="text-right font-weight-bolder"><?=Utils::format($TotalLoanDeduction, 0)?></td>
                            <td></td>
                            <td class="text-right font-weight-bolder"><?=Utils::format($TotalSalary, 0)?></td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>