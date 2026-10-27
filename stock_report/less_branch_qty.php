<?
$_GET['_page_title'] = 'Less Branch Qty Report';
$_GET['_page_title_text'] = 'View Stock Batch';

include_once("../includes/inc_header.php");

$oBranch = new Branch();
$oStockReport = new StockReport();

$Branches = $oBranch->getBranchesArray();
$AllStock = $oStockReport->getLessBranchQty($_GET['stock_id']);
?>
<style>
    .bg-danger td {
        background-color: rgba(255, 0, 0, 0.18);
    }
</style>
<form action="" method="get" <? Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Filter Options</h3>
                <div class="row">
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="stock_id">Item Name</label>
                            <input type="text" id="stock_id" name="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'], '') ?>>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Search Results</h3>
                <div class="table-responsive">
                <table class="table data-table-report color table-striped non-editable table-bordered">
                    <thead>
                        <tr>
                            <th>Stock Name</th>
                            <? foreach ($Branches as $BranchName) { ?>
                            <th><?=$BranchName?></th>
                            <? } ?>
                        </tr>
                    </thead>
                    <tbody>
                    <? foreach ($AllStock as $StockBranchName => $StockDetails) { ?>
                        <tr>
                            <td><?=$StockDetails[0]?></td>
                            <? foreach ($Branches as $BranchName) { ?>
                                <th><?=$AllStock[$BranchName."-".$StockDetails[2]][1]?></th>
                            <? } ?>
                        </tr>
                    <? } ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</form>
<? include_once("../includes/inc_footer.php") ?>