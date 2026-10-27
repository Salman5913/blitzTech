<?
include_once("../connection/localhost.php");

$oBranch = new Branch();
$oBranch->initForEdit();

$RowDetails = $oBranch->getDetails();

include_once("../includes/inc_header.php");
?>
<form action="op_save_branch.php" method="post" id="branch" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-sm-8">
            <div class="card">
                <h3 class="card-title"> Branch Details</h3>
                <div class="row">
                    <div class="col-xl-6 col-lg-4">
                        <div class="form-group">
                            <label for="branch_name">Branch Name</label>
                            <input type="text" class="large" name="branch_name" id="branch_name" value="<?=$RowDetails['name']?>" <? Utils::isRequired() ?>>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-4">
                        <div class="form-group">
                            <label for="phone">Branch Phone</label>
                            <input type="text" name="phone" id="phone" value="<?=$RowDetails['phone']?>">
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-4">
                        <div class="form-group">
                            <label for="email">Branch Email</label>
                            <input type="text" name="email" id="email" value="<?=$RowDetails['email']?>">
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-4">
                        <div class="form-group">
                            <label for="fax">Branch_Fax</label>
                            <input type="text" name="fax" id="fax" value="<?=$RowDetails['fax']?>">
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-4">
                        <div class="form-group">
                            <label for="address">Branch Address</label>
                            <textarea name="address" id="address"><?=$RowDetails['address']?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <h3 class="card-title"> Other Details</h3>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="is_warehouse">Branch Type</label>
                            <select name="is_warehouse" id="is_warehouse">
                                <? Utils::getDropDownOptionsFromArray([
                                        Branch::BRANCH_TYPE_BRANCH => 'Branch',
                                        Branch::BRANCH_TYPE_WAREHOUSE => 'Warehouse',
                                        Branch::BRANCH_TYPE_HEADQUARTER => 'Headquarter'
                                ], $RowDetails['is_warehouse']) ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="is_active">is Active?</label>
                            <select name="is_active" id="is_active">
                                <? Utils::getDropDownOptionsFromArray(['Y' => 'Yes', 'N' => 'No'], $RowDetails['is_active']); ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <? $oBranch->showVoucherButtons() ?>
</form>
<? include_once("../includes/inc_footer.php"); ?>