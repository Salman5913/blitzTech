<?
$_GET['_page_title'] = 'Stock Holding By Brand / Category-wise';
$_GET['_page_title_text'] = 'Stock Currently Held By Brand';

include_once("../includes/inc_header.php");

$oStockBrandCategory = new StockReportBrandCategory();

$TotalQty         = 0;
$TotalAmount      = 0;
$SelectBrand    = $oStockBrandCategory->getStockBrand();
$SelectCategories = $oStockBrandCategory->getAllCategories();

$Categories = [];
while ($RowCategory = DBQM::fetch_assoc($SelectCategories))
    $Categories[$RowCategory['category']] = 0;

$Total = [];
?>
<form action="" method="get">
    <? if (ProjectHelper::HAS_COST_CENTER) { ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <? CostCenter::designReportFilter('col-lg-4 col-md-6'); ?>
                    <div class="col-lg-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn color d-block" name="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <? } ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="row">
                    <div class="col-sm-6"><h3 class="card-title">Report</h3></div>
                    <div class="col-sm-6 text-right"><? UtilsPrint::button() ?></div>
                </div>
                <table class="table color table-striped <? UtilsPrint::makePrintable() ?>">
                    <thead>
                        <tr>
                            <th>Brand</th>
                            <? foreach ($Categories AS $Category => $Qty) { ?>
                                <th class="text-center"><?=$Category?></th>
                            <? } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?
                        while ($RowBrand = DBQM::fetch_assoc($SelectBrand)) {
                            $SelectCompanyCategoryQty = $oStockBrandCategory->getStockCategory($RowBrand['brand']);

                            while($RowCompanyCategory = DBQM::fetch_assoc($SelectCompanyCategoryQty)) {
	                            $Total[ $RowCompanyCategory['category'] ][ $RowBrand['brand'] ] = $RowCompanyCategory['category_qty'];
                            }
                            ?>
                        <tr>
                            <td><?=$RowBrand['brand']?></td>
	                        <? foreach ($Categories AS $Category => $Qty) { ?>
                                <td class="text-center"><?=number_format($Total[$Category][$RowBrand['brand']])?></td>
	                        <? } ?>
                        </tr>
                        <? } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="font-weight-bold">Total</td>
	                        <? foreach ($Categories AS $Category => $Qty) { ?>
                                <td class="text-center font-weight-bold"><?=number_format(array_sum($Total[$Category]?? []));?></td>
	                        <? } ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</form>
<? UtilsPrint::init("Stock Holding By Brand / Category-wise (".date('d/m/Y').")") ?>
<? include_once("../includes/inc_footer.php") ?>