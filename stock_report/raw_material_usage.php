<?
/**
 * Raw Material Usage: raw material taken out with Stock Use vouchers, item wise at purchase cost,
 * with its share of net sales (food cost %).
 */
$_GET['_page_title'] = 'Raw Material Usage Report';
$_GET['_page_title_text'] = 'Raw material used (Stock Use) against sales';

include_once("../includes/inc_header.php");

SecurityShell::redirectIfNoAccess(CompanyModule::STOCK, "Raw Material Usage");

$_GET['date_from'] = $_GET['date_from'] ?? date('m/01/Y');
$_GET['date_till'] = $_GET['date_till'] ?? date('m/d/Y');

$oStockReport = new StockReport();
$Select = $oStockReport->getRawMaterialUsage($_GET['date_from'], $_GET['date_till'], $_GET['stock_id'] ?? '', $_GET['category_id'] ?? '');
$NetSales = $oStockReport->getNetSales($_GET['date_from'], $_GET['date_till']);

$Rows = [];
$TotalCost = 0;
while ($Row = DBQM::fetch_assoc($Select))
{
    $Rows[] = $Row;
    $TotalCost += $Row['cost'];
}
?>
<form action="" method="get" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group mb-0">
                            <label>Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] == '' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] == '' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="stock_id">Raw Material</label>
                            <input type="text" id="stock_id" name="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'] ?? '', ''); ?>>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <input type="text" id="category_id" name="category_id" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $_GET['category_id'] ?? ''); ?>>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-xl-3 col-md-6'); ?>
                    <div class="col-xl-12 text-right">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card"><small class="text-muted">Net Sales</small><h3 class="mb-0"><?=Utils::format($NetSales, 2)?></h3></div>
        </div>
        <div class="col-md-4">
            <div class="card"><small class="text-muted">Raw Material Used</small><h3 class="mb-0"><?=Utils::format($TotalCost, 2)?></h3></div>
        </div>
        <div class="col-md-4">
            <div class="card"><small class="text-muted">Food Cost % (raw material / net sales)</small><h3 class="mb-0"><?=$NetSales != 0 ? Utils::format($TotalCost / $NetSales * 100, 1).'%' : '-'?></h3></div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="row">
                    <div class="col-auto mr-auto">
                        <h3 class="card-title"> Raw Material Used (Stock Use)</h3>
                    </div>
                    <div class="col-auto ml-auto">
                        <? UtilsPrint::button() ?>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table data-table-report color table-striped <? UtilsPrint::makePrintable() ?>">
                        <thead>
                        <tr>
                            <th>Raw Material</th>
                            <th>Category</th>
                            <th class="text-right">Qty Used</th>
                            <th class="text-right">Avg Cost</th>
                            <th class="text-right">Cost</th>
                            <th class="text-center">% of Usage</th>
                            <th class="text-center">% of Sales</th>
                        </tr>
                        </thead>
                        <tbody>
                        <? foreach ($Rows as $Row) { ?>
                            <tr>
                                <td><a href="<?=Reporting::getStockLink($Row['id'], $Row['attribute_id'], $_GET['date_from'], $_GET['date_till'], '')?>" target="_blank"><?=htmlspecialchars($Row['ac_stock'])?></a></td>
                                <td><?=htmlspecialchars($Row['category'])?></td>
                                <td class="text-right"><?=Utils::format($Row['qty'], 2)?></td>
                                <td class="text-right"><?=Utils::format($Row['qty'] != 0 ? $Row['cost'] / $Row['qty'] : 0, 2)?></td>
                                <td class="text-right"><?=Utils::format($Row['cost'], 2)?></td>
                                <td class="text-center"><?=$TotalCost != 0 ? Utils::format($Row['cost'] / $TotalCost * 100, 1) : '-'?></td>
                                <td class="text-center"><?=$NetSales != 0 ? Utils::format($Row['cost'] / $NetSales * 100, 1) : '-'?></td>
                            </tr>
                        <? } ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td class="font-weight-bolder" colspan="4">Total</td>
                            <td class="font-weight-bolder text-right"><?=Utils::format($TotalCost, 2)?></td>
                            <td></td>
                            <td class="font-weight-bolder text-center"><?=$NetSales != 0 ? Utils::format($TotalCost / $NetSales * 100, 1) : '-'?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>
<? UtilsPrint::init(DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y')) ?>
<? include_once("../includes/inc_footer.php"); ?>
