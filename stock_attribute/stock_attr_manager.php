<?php
include_once ("../connection/localhost.php");

$oStockAttribute = new StockAttribute();
$oStockAttribute->initForEdit();

$RowDetails = $oStockAttribute->getDetails();

include_once("../includes/inc_header.php");
?>
<script src="../js_live/stock_category/stock_attributes.min.js" type="text/javascript"></script>
<div class="row">
    <div class="col-sm-7">
        <div class="card">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="card-title"> Basic Details</h3>
                </div>
                <div class="col-sm-6 text-right <?=$oStockAttribute->isEdit() ? '' : 'hidden'?>">
                    <a href="stock_attr_manager.php" class="shadow-sm button btn-sm bg-soft-info font-weight-bold"><i class="mdi mdi-plus"></i> Create New</a>
                </div>
            </div>
            <form method="post" action="op_stock_attr.php" <? Utils::formValidate(); ?>>
                <div class="row">
                    <div class="col-xl-5 col-lg-4">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" <? $oStockAttribute->checkForUnique("name", $RowDetails['name']) ?> <? Utils::isRequired() ?>>
                        </div>
                    </div>
                </div>
                <? $oStockAttribute->showVoucherButtons() ?>
            </form>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="card">
            <h3 class="card-title"> View All</h3>
            <div class="ajax-table"></div>
        </div>
    </div>
</div>

<? include_once("../includes/inc_footer.php"); ?>