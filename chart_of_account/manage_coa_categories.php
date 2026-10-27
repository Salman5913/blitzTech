<?
include_once("../connection/localhost.php");

$oChartOfAccountCategory = new ChartOfAccountCategory();
$oChartOfAccountCategory->initForEdit();

$isEdit = $oChartOfAccountCategory->isEdit();
$Id = $oChartOfAccountCategory->getVoucherId();
$RowDetails = $oChartOfAccountCategory->getDetails();
include_once("../includes/inc_header.php");
?>
<script src="../js_live/chart_of_account/chart_of_account.min.js" type="text/javascript"></script>
<div class="row">
    <div class="col-sm-6">
        <form method="post" action="op_save_coa_categories.php" id="category" <? Utils::formValidate(); ?>>
            <div class="card">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="card-title"> Basic Details</h3>
                    </div>
                    <div class="col-sm-6 text-right <?=$isEdit ? '' : 'hidden'?>">
                        <a href="manage_coa_categories.php" class="shadow-sm button btn-sm bg-soft-warning font-weight-bold"><i class="mdi mdi-plus"></i> Create New Category</a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-12">
                        <div class="form-group">
                            <label for="parent_id">Parent Category</label>
                            <input type="text" name="parent_id" id="parent_id" <? Utils::isRequired(); Autocomplete::ACSearchCOA($RowDetails['parent_id']) ?> />
                        </div>
                    </div>
                    <div class="col-xl-12">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" <? UniquenessChecker::checkForUnique("acc_chart_of_account", "name", $RowDetails['name']); ?> <? Utils::isRequired(); ?> >
                        </div>
                    </div>
                </div>
                <? $oChartOfAccountCategory->showVoucherButtons(); ?>
            </div>
        </form>
    </div>
    <div class="col-sm-6">
        <div class="card">
            <h3 class="card-title"> Hierarchy</h3>
            <div class="hierarchy-table"></div>
        </div>
        <div class="card">
            <h4 class="card-title"> Categories</h4>
            <div class="ajax-table"></div>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php") ?>