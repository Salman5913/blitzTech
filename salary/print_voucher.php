<?php
include_once ("../connection/localhost.php");

$oSalary = new Salary();
$oLoan = new Loan();

$oSalary->initForView();
$SalaryId = $oSalary->getVoucherId();

$RowSalary = $oSalary->getDetailsByVoucherId($SalaryId);
$SelectEmployee = $oSalary->getAllEmployeeList($SalaryId, $_GET['account_id']);


$EmpLoans = $oLoan->getAllEmpLoans($SalaryId, false, '', DateEncode($RowSalary['date_time']));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Salary Payslip </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?=ProjectHelper::SiteURL()?>assets/images/favicon.ico">
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/bootstrap.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/app.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/print.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>assets/css/app-font.css?v=1" rel="stylesheet" type="text/css" />
</head>
<body data-layout="detached" data-topbar="colored" id="sales_print">
<style>

    .margin-bottom {
        margin-bottom: 180px;
    }
</style>
<div class="row justify-content-center print-container">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <?
                $i = 1;
                while ($RowEmployee = DBQM::fetch_assoc($SelectEmployee)) {
                    $Salary = ($RowEmployee['basic_salary'] / $RowSalary['number_of_working_days']) * ($RowSalary['number_of_working_days'] - $RowEmployee['leave']);
                    $Space = '';
                    if($i==1)
                        $Space = 'margin-top:-20px';
                    elseif($i % 2 == 1)
                        $Space = 'margin-top:360px !important';
                    ?>
                    <table class="table table-bordered" style="<?=$Space?>" data="<?=$i?>">
                        <tr>
                            <td class="text-center" colspan="6"><h2>Salary - <?=$RowEmployee['branch_name']?></h2></td>
                        </tr>
                        <tr>
                            <td>Name</td>
                            <td class="emp_name text-uppercase" colspan="3"><?=$RowEmployee['emp_name']?> <small>(EId: <?=$RowEmployee['emp_id']?>)</small></td>
                        </tr>
                        <tr>
                            <td width="30%">Month</td>
                            <td width="20%" class="text-bold"><?=DateDecode($RowSalary['for_month'], 'M - Y')?></td>
                            <td width="30%">Salary <span class="urdu">تنخواہ</span></td>
                            <td width="20%" class="text-bold"><?=Utils::format($RowEmployee['basic_salary'], 0)?></td>
                        </tr>
                        <tr>
                            <td>Leaves <span class="urdu">غیر حاضر</span></td>
                            <td><?=$RowEmployee['leave']?></td>
                            <td>Leave Deduction <span class="urdu">غیر حاضر کٹوتی</span></td>
                            <td><?=Utils::format($RowEmployee['basic_salary'] - $Salary, 0)?></td>
                        </tr>
                        <tr>
                            <td>Loan Deduction <span class="urdu">قرض کٹوتی</span></td>
                            <td><?=Utils::format($RowEmployee['loan_deduction'], 0)?></td>
                            <td>Advance Deduction <span class="urdu">ایڈوانس کٹوتی</span></td>
                            <td><?=Utils::format($RowEmployee['advance_deduction'], 0)?></td>
                        </tr>
                        <tr>
                            <td class="gross_salary">Gross Salary</td>
                            <td class="gross_salary" colspan="3"><?=Utils::format($Salary - $RowEmployee['loan_deduction'] - $RowEmployee['advance_deduction'], 0)?></td>
                        </tr>
                        <tr>
                            <td>Overtime <span class="urdu">اورٹاۂم</span></td>
                            <td><?=Utils::format($RowEmployee['overtime_allowance'], 0)?></td>
                            <td>Night Overtime <span class="urdu">نائٹ اورٹاۂم</span></td>
                            <td><?=Utils::format($RowEmployee['night_overtime_allowance'], 0)?></td>
                        </tr>
                        <tr>
                            <td>Holiday Overtime <span class="urdu">چھٹی اورٹاۂم</span></td>
                            <td colspan="3"><?=Utils::format($RowEmployee['holiday_overtime_allowance'], 0)?></td>
                        </tr>
                        <tr>
                            <td>Late <span class="urdu">دیر</span></td>
                            <td><?=Utils::format($RowEmployee['late_hours_deduction'], 0)?></td>
                            <td>Adjustment <span class="urdu">ایڈجسٹمنٹ</span></td>
                            <td><?=Utils::format($RowEmployee['adjustment'], 0)?></td>
                        </tr>
                        <tr>
                            <td class="hidden">Attendance Allowance <span class="urdu">حاضری الاؤنس</span></td>
                            <td class="hidden"><?=Utils::format($RowEmployee['attendance_allowance'], 0)?></td>
                            <td class="hidden">Tea Allowance <span class="urdu">چائے الاؤنس</span></td>
                            <td class="hidden"><?=Utils::format($RowEmployee['tea_allowance'], 0)?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bolder">Remaining Advance <span class="urdu">ایڈوانس باقی</span></td>
                            <td class="font-weight-bolder"><?=Utils::format($EmpLoans[$RowEmployee['account_id']] - $RowEmployee['loan_deduction'] - $RowEmployee['advance_deduction'], 0)?></td>
                            <td class="total font-weight-bolder">Total</td>
                            <td class="total font-weight-bolder"><?=Utils::format($Salary - $RowEmployee['loan_deduction'] - $RowEmployee['advance_deduction'] + $RowEmployee['tea_allowance'] + $RowEmployee['overtime_allowance'] + $RowEmployee['night_overtime_allowance'] + $RowEmployee['holiday_overtime_allowance'] - $RowEmployee['late_hours_deduction'] + $RowEmployee['attendance_allowance'] + $RowEmployee['adjustment'], 0)?></td>
                        </tr>
                    </table>
                    <div class="margin-bottom"></div>
                <? $i++; } ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>