<?
$_GET['_page_title'] = 'Add Employee';
$_GET['_page_title_text'] = 'Create Add Employees';

include_once("../includes/inc_header.php");

$oBranch = new Branch();

$LogId = $_GET['log_id'];
$BranchId = $oBranch->getBranchId();

//$ExpenseRows = DatabaseHelper::getRecordCount($SelectExpenses) + 1;

?>
<form method="post" action="op_save_emp.php" <?Utils::formValidate()?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Basic Details</h3>
                <div class="row">
                    <div class="col-md-6">
                        <label>Employee Name</label>
                        <input type="text" id="employee_id" name="employee_id" <? Utils::isRequired(); Autocomplete::ACSearchAccount('');?>>
                    </div>
                    <div class="col-md-6">
                        <label>Branch</label>
                        <input type="text" name="branch_id" id="branch_id" <?Autocomplete::Search(Autocomplete::TYPE_BRANCH, $BranchId)?>>
                    </div>
                    <div class="col-md-6">
                        <label>Start Time</label>
                        <input type="text" name="start_time" id="start_time" placeholder="09:00">
                    </div>
                    <div class="col-md-6">
                        <label>End Time</label>
                        <input type="text" name="end_time" id="end_time" placeholder="18:00">
                    </div>
                    <div class="col-md-6">
                        <label>Is Night?</label>
                        <input type="checkbox" name="is_night" id="is_night">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="log_id" id="log_id" value="<?=$LogId?>">
    <button class="btn button color pull-right margin-10" name="submit" type="submit"><i class="fa fa-save"></i>Save</button>
</form>
<? include_once("../includes/inc_footer.php"); ?>

