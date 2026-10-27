<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Employee HR Data Update';
$_GET['_page_title_text'] = 'Update Employee Transaction Accounts';

include_once("../includes/inc_header.php");

$oChartOfAccount = new ChartOfAccount();
$oEmployeeBranch = new Branch();

$SelectEmployee = $oChartOfAccount->getAllEmployeeList();
$TotalSalary = 0;
?>
<script src="../js_live/chart_of_account/accounts.min.js" type="text/javascript"></script>
<form method="post" action="op_save_employees.php" id="accounts">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Search Results</h3>
                <div class="table-responsive">
                    <table class="table table-striped color">
                        <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Account Name</th>
                            <th>CNIC</th>
                            <th>Basic Salary</th>
                            <th>EMP Id</th>
                            <th>Branch</th>
                            <th>Calc OT?</th>
                            <th><i class="fa fa-gear"></i> Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <? $i=1; while($RowEmployee = DBQM::fetch_assoc($SelectEmployee)) { $TotalSalary += $RowEmployee['basic_salary']; ?>
                            <tr>
                                <td><?=$i++;?></td>
                                <td><?=$RowEmployee['name']?></td>
                                <td><label><input type="text" name="cnic[<?=$RowEmployee['account_id']?>]" value="<?=$RowEmployee['cnic']?>"></label></td>
                                <td><label><input type="text" data-type="float" name="basic_salary[<?=$RowEmployee['account_id']?>]" value="<?=round($RowEmployee['basic_salary'], 0)?>"></label></td>
                                <td><label><input type="text" data-type="int" name="emp_id[<?=$RowEmployee['account_id']?>]" value="<?=$RowEmployee['emp_id']?>"></label></td>
                                <td><label><select name="branch_id[<?=$RowEmployee['account_id']?>]"><? $oEmployeeBranch->getBranchSelectOption($RowEmployee['branch_id']) ?></select></label></td>
                                <td><label><select name="can_overtime[<?=$RowEmployee['account_id']?>]"><? Utils::getDropDownOptionsFromArray(['N'=> 'No', 'Y'=>'Yes'], $RowEmployee['can_overtime']) ?></select></label>
                                    <input type="hidden" value="<?=$RowEmployee['join_date']?>" name="join_date[<?=$RowEmployee['account_id']?>]">
                                    <input type="hidden" value="<?=$RowEmployee['is_active']?>" name="is_active[<?=$RowEmployee['account_id']?>]">
                                </td>
                                <td class="text-right report-actions">
                                    <a href="manage_accounts.php?id=<?=$RowEmployee['account_id']?>"><i class="fa fa-edit"></i> Edit</a>
                                </td>
                            </tr>
                        <? } ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="3" class="text-right font-weight-bold"><?=Utils::format($TotalSalary, 0)?></td>
                            <td colspan="4"></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <button class="btn button color center-block margin-10" name="submit" type="submit">Save</button>
</form>
<? include_once("../includes/inc_footer.php") ?>