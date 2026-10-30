<?
$_GET['_page_title'] = 'Sales By Brand / Category-wise';
$_GET['_page_title_text'] = 'Stock Currently Held By Brand';

include_once("../includes/inc_header.php");

$oStockBrandCategory = new StockReportBrandCategory();

$TotalQty = 0;
$TotalAmount = 0;
$SelectBrand = $oStockBrandCategory->getStockBrand();
$SelectCategories = $oStockBrandCategory->getAllCategories();

$Categories = [];
while ($RowCategory = DBQM::fetch_assoc($SelectCategories))
    $Categories[$RowCategory['category']] = 0;

$Total = [];
$TotalValue = [];
?>
    <form action="" method="get">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <h3 class="card-title"> Filter Options</h3>
                    <div class="row">
                        <div class="col-xl-6 col-md-6">
                            <div class="form-group mb-0">
                                <label>Date Range</label>
                                <div class="input-daterange input-group" data-provide="datepicker">
                                    <input type="text" class="form-control" name="date_from" data-type="date"
                                           value="<?= $_GET['date_from'] == '' ? '' : DateEncode($_GET['date_from'], 'M d, Y') ?>" data-empty="true"/>
                                    <input type="text" class="form-control" name="date_till" data-type="date"
                                           value="<?= $_GET['date_till'] == '' ? '' : DateEncode($_GET['date_till'], 'M d, Y') ?>" data-empty="true"/>
                                </div>
                            </div>
                        </div>
                        <? CostCenter::designReportFilter('col-xl-3 col-md-6'); ?>
                        <div class="col-xl-12 text-right">
                            <button type="submit" class="btn button color" name="search" id="search"><i class="fa fa-search"></i> Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
<? if (isset($_GET['date_from'])) { ?>
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
                        <th>Company</th>
                        <? foreach ($Categories as $Category => $Qty) { ?>
                            <th class="text-center"><?= $Category ?></th>
                        <? } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?
                    while ($RowBrand = DBQM::fetch_assoc($SelectBrand)) {
                        $SelectCompanyCategoryQty = $oStockBrandCategory->getStockCategoryByVoucherType($RowBrand['brand'], $_GET['date_from'], $_GET['date_till'], '2,15', 'stl.sp');

                        while ($RowCompanyCategory = DBQM::fetch_assoc($SelectCompanyCategoryQty)) {
                            $Total[$RowCompanyCategory['category']][$RowBrand['brand']] = $RowCompanyCategory['category_qty'] * -1;
                            $TotalValue[$RowCompanyCategory['category']][$RowBrand['brand']] = $RowCompanyCategory['stock_value'] * -1;
                        }
                        ?>
                        <tr>
                            <td><?= $RowBrand['brand'] ?></td>
                            <? foreach ($Categories as $Category => $Qty) { ?>
                                <td class="text-center">
                                    <?= number_format($Total[$Category][$RowBrand['brand']]) ?> Pcs
                                    <br>
                                    (<?= number_format($TotalValue[$Category][$RowBrand['brand']]) ?>)
                                </td>
                            <? } ?>
                        </tr>
                    <? } ?>
                    </tbody>
                    <tfoot>
                    <tr class="font-weight-bold">
                        <td>Total</td>
                        <? foreach ($Categories as $Category => $Qty) { ?>
                            <td class="text-center font-weight-bolder"><?= number_format(array_sum(empty($Total[$Category]) ? [] : $Total[$Category])); ?></td>
                        <? } ?>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<? } ?>
<? UtilsPrint::init("Sales By Brand / Category-wise (" . DateEncode($_GET['date_from'], 'M d, Y') . " - " . DateEncode($_GET['date_till'], 'M d, Y') . ")") ?>
<? include_once("../includes/inc_footer.php") ?>