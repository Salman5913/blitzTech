<?
include_once("../connection/localhost.php");

$oStockCategory = new StockCategory();
$oStockCategory->initForEdit();

$isEdit = $oStockCategory->isEdit();
$Id = $oStockCategory->getVoucherId();
$RowDetails = $oStockCategory->getDetails();

include_once("../includes/inc_header.php");
?>
<script src="../js_live/stock_category/stock_category.min.js" type="text/javascript"></script>
<div class="row">
    <div class="col-sm-7">
        <div class="card">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="card-title"> Basic Details</h3>
                </div>
                <div class="col-sm-6 text-right <?=$isEdit ? '' : 'hidden'?>">
                    <a href="manage_stock_category.php" class="shadow-sm button btn-sm bg-soft-info font-weight-bold"><i class="mdi mdi-plus"></i> Create New</a>
                </div>
            </div>
            <form method="post" action="op_save_stock_category.php" id="category" <? Utils::formValidate(); ?>>
                <div class="row">
                    <div class="col-xl-6 col-lg-6">
                        <div class="form-group">
                            <label for="parent_id">Parent Category</label>
                            <input type="text" name="parent_id" id="parent_id" <? Utils::requireAutoComplete(); Utils::isRequired(); Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $RowDetails['parent_category_id']) ?> />
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" <? UniquenessChecker::checkForUnique("st_stock_category", "name", $RowDetails['name']); ?> <? Utils::isRequired(); ?> >
                        </div>
                    </div>
                </div>
                <? $oStockCategory->showVoucherButtons(); ?>
            </form>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="card">
            <h3 class="card-title"> Hierarchy</h3>
            <div class="hierarchy-table"></div>
        </div>
        <div class="card">
            <h3 class="card-title"> View All</h3>
            <div class="ajax-table"></div>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php") ?>