<?
include_once("../connection/localhost.php");

$oAttendanceSheet = new AttendanceSheet();
$oBranch = new Branch();
$oAttendanceSheet->initForEdit();

$RowLogDetails = $oAttendanceSheet->getDetails();

if ($oAttendanceSheet->isEdit() and empty($RowLogDetails['id'])) die("Invalid Selection");

include_once("../includes/inc_header.php");
?>
<form method="post" action="op_save_log.php" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-sm-8">
            <div class="card">
                <h3 class="card-title"> Basic Details</h3>
                <div class="row">
                    <div class="col-md-6">
                        <label for="name">Month Name</label>
                        <input type="text" name="name" id="name" placeholder="Ex: <?=date("F - Y")?>" value="<?=$RowLogDetails['month_name']?>" <? Utils::isRequired() ?>>
                    </div>
                    <div class="col-md-6">
                        <label for="branch_id">Branch Name</label>
                        <select name="branch_id" id="branch_id"><?$oBranch->getBranchSelectOption($RowLogDetails['branch_id'], true)?></select>
                    </div>
                    <div class="col-md-6">
                        <label for="start_time">Start Time</label>
                        <input type="text" name="start_time" id="start_time" placeholder="08:30" value="<?=$RowLogDetails['start_time']?>">
                    </div>
                    <div class="col-md-6">
                        <label for="end_time">End Time</label>
                        <input type="text" name="end_time" id="end_time" placeholder="20:30" value="<?=$RowLogDetails['end_time']?>">
                    </div>
                    <div class="col-md-6">
                        <label for="month">Month</label>
                        <input type="text" data-type="date" name="month" id="month" value="<?=DateDecode($RowLogDetails['month'])?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <? $oAttendanceSheet->showVoucherButtons()?>
</form>
<? include_once("../includes/inc_footer.php") ?>