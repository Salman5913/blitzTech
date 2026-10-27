<?
include_once("../connection/localhost.php");

$oCostCenter = new CostCenter();
$oCostCenter->initForEdit();

$RowDetails = $oCostCenter->getDetails();

include_once("../includes/inc_header.php");
?>
<form action="op_save_cost_center.php" method="post" id="costcenter" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Basic Details</h3>
                <div class="row">
                    <div class="col-xl-6 col-lg-4">
                        <div class="form-group">
                            <label for="cost_center_name">Cost Center Name</label>
                            <input type="text" class="large" name="cost_center_name" id="cost_center_name" value="<?=$RowDetails['cost_center_name']?>" <? echo UniquenessChecker::checkForUniqueByType(UniquenessChecker::TYPE_COST_CENTER, $RowDetails['cost_center_name']); Utils::isRequired() ?>>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <? $oCostCenter->showVoucherButtons() ?>
</form>
<? include_once("../includes/inc_footer.php"); ?>