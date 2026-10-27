<?
$_GET['_page_title'] = 'Update Pay Status';
$_GET['_page_title_text'] = 'Create / Update Salary Sheet Information';

include_once("../includes/inc_header.php");

$oSalary = new Salary();
$SalaryId = $_GET['salary_id'];

$RowSalary = $oSalary->getDetailsByVoucherId($SalaryId);
$SelectEmployee = $oSalary->getAllEmployeeList($SalaryId);

$TeaAllowance = $RowSalary['tea_allowance'];
$AttendanceAllowance = $RowSalary['attendance_allowance'];

$MonthDays = $RowSalary['number_of_working_days'];

?>
<div class="row">
    <div class="col-sm-8">
        <div class="card">
            <h3 class="card-title"> Log File Selection</h3>
            <div class="row">
                <div class="col-md-6">
                    <dl>
                        <dt>For Month of</dt>
                        <dd class="text-danger font-weight-bolder"><?=DateDecode($RowSalary['for_month'], 'M d, Y')?></dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl>
                        <dt>Salary Date</dt>
                        <dd class="text-danger font-weight-bolder"><?=DateDecode($RowSalary['date_time'], 'M d, Y')?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card">
            <h3 class="card-title"> Log File Selection</h3>
            <div class="row">
                <div class="col-md-5">
                    <dl>
                        <dt>Tea Allowance</dt>
                        <dd class="text-success font-weight-bolder"><?=$TeaAllowance?></dd>
                    </dl>
                </div>
                <div class="col-md-5">
                    <dl>
                        <dt>Full Attendance Allowance</dt>
                        <dd class="text-success font-weight-bolder"><?=Utils::format($AttendanceAllowance, 0)?></dd>
                    </dl>
                </div>
                <div class="col-md-5">
                    <dl>
                        <dt>Total Working Days</dt>
                        <dd class="text-success font-weight-bolder" data-working-days="<?=$MonthDays?>"><?=$MonthDays?></dd>
                    </dl>
                </div>
                <div class="col-md-5">
                    <dl>
                        <dt>Number of Employees</dt>
                        <dd class="text-success font-weight-bolder"><?=DatabaseHelper::getRecordCount($SelectEmployee)?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <h3 class="card-title"> Employee Salary Info</h3>
           <div class="table-responsive">
               <table class="table color table-striped">
                   <thead>
                   <tr>
                       <th>Name</th>
                       <th class="text-right">Salary</th>
                       <th class="text-right">Reprint?</th>
                       <th class="text-right">Is Paid?</th>
                   </tr>
                   </thead>
                   <?
                   $i=1;
                   while ($RowEmployee = DBQM::fetch_assoc($SelectEmployee))
                   {
                       $AccountId = $RowEmployee['account_id'];

                       $isNight = $RowEmployee['is_night_shift'] == 'Y';
                       $Salary = ($RowEmployee['basic_salary'] / $RowSalary['number_of_working_days']) * ($RowSalary['number_of_working_days'] - $RowEmployee['leave']);
                       $FinalSalary = $Salary - $RowEmployee['loan_deduction'] - $RowEmployee['advance_deduction'] + $RowEmployee['tea_allowance'] + $RowEmployee['overtime_allowance'] + $RowEmployee['night_overtime_allowance'] + $RowEmployee['holiday_overtime_allowance'] - $RowEmployee['late_hours_deduction'] + $RowEmployee['attendance_allowance'] + $RowEmployee['adjustment'];
                       ?>
                       <tr>
                           <td><?=$RowEmployee['emp_name']?> (EId: <?=$RowEmployee['emp_id']?>)</td>
                           <td class="text-right"><?=Utils::format($FinalSalary, 0)?></td>
                           <td class="text-right"><a href="print_voucher.php?id=<?=$SalaryId?>&account_id=<?=$RowEmployee['account_id']?>" target="_blank">Reprint Salary Slip</a></td>
                           <td class="text-right">
                               <? if (
                                   ($RowEmployee['is_paid'] == 'Y') or
                                   $RowEmployee['is_paid'] == 'N'
                               ) { ?>
                                   <form method="post" action="op_pay_status_update.php" <? Utils::formValidate(); ?>>
                                       <input name="account_id" type="hidden" value="<?=$RowEmployee['account_id']?>">
                                       <input name="status" type="hidden" value="<?=$RowEmployee['is_paid'] == 'Y' ? 'N' : 'Y'?>" >
                                       <input name="payment" type="hidden" value="<?=$FinalSalary?>">
                                       <input type="hidden" name="salary_id" value="<?=$SalaryId?>">
                                       <input type="hidden" name="loan_deduction" value="<?=$RowEmployee['loan_deduction']?>">
                                       <input type="hidden" name="advance_deduction" value="<?=$RowEmployee['advance_deduction']?>">
                                       <input type="hidden" name="paid_distinct_id" value="<?=$RowEmployee['paid_distinct_id']?>">
                                       <input type="hidden" name="payment_id" value="<?=SalaryPayment::getIdBySalaryDataId($RowEmployee['id'])?>">
                                       <input type="hidden" name="salary_data_id" value="<?=$RowEmployee['id']?>">

                                       <? if ($RowEmployee['is_paid'] == 'N') { ?>
                                           <input type="text" name="paid_from" placeholder="Paid From?" data-label="Paid From" id="paid_from_<?=$i?>" <? Utils::isRequired(); Autocomplete::ACSearchAccountByCOAId([getConstantCategory()->getBank(), getConstantCategory()->getCashInHand()], $RowEmployee['paid_from_account_id']); ?>>
                                       <? } else { ?>
                                           Paid From: <strong><?=ChartOfAccount::getName($RowEmployee['paid_from_account_id'])?></strong>
                                       <? } ?>
                                       <button class="btn button color inline" name="submit" type="submit"><?=$RowEmployee['is_paid'] == 'Y' ? 'Mark As Unpaid' : 'Mark As Paid'?></button>
                                   </form>
                               <? } else { ?>
                                   Paid From: <strong><?=ChartOfAccount::getName($RowEmployee['paid_from_account_id'])?></strong>
                               <? } ?>
                           </td>
                       </tr>
                   <? } ?>
               </table>
           </div>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php") ?>