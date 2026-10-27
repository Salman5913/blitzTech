<?
$_GET['_page_title'] = 'Stock Holding Report';
$_GET['_page_title_text'] = 'Stock Holding Report';

include_once("../includes/inc_header.php");

$StockReport = new StockReport();

if (isset($_GET['search']))
{
    $Select = $StockReport->getProfitReport($_GET['category_id'], $_GET['stock_id'], $_GET['date_from'], $_GET['date_till']);
}
?>
<form action="" method="get" <? Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group mb-0">
                            <label for="date_from">Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] =='' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] =='' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label for="stock_id">Item Name</label>
                            <input type="text" name="stock_id" id="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'], '') ?>>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <input type="text" name="category_id" id="category_id" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $_GET['category_id']);?>>
                        </div>
                    </div>
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
</form>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="row">
                <div class="col-auto mr-auto">
                    <h3 class="card-title"> Stock Profit Report</h3>
                </div>
                <div class="col-auto ml-auto">
                    <? UtilsPrint::button() ?>
                </div>
            </div>
            <table class="table data-table-report color table-striped <? UtilsPrint::makePrintable() ?>">
                <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th class="text-right">Sold Qty</th>
                    <th class="text-center">Avg Cost</th>
                    <th class="text-center">Avg Selling Price</th>
                    <th class="text-right">Profit / Loss</th>
                </tr>
                </thead>
                <tbody>
                <? while ($Row = DBQM::fetch_assoc($Select)) { $Profit = $Row['avg_sp'] - $Row['avg_cost'];?>
                    <tr>
                        <td><a href="<?=Reporting::getStockLink($Row['id'], $Row['attribute_id'], $_GET['date_from'], $_GET['date_till'], '')?>" target="_blank"><?=$Row['ac_stock']?></a></td>
                        <td><?=$Row['category']?></td>
                        <td class="text-right" data-js="qty"><?=Utils::format($Row['sold_qty'])?></td>
                        <td class="text-center"><?=Utils::format($Row['avg_cost'], 2)?></td>
                        <td class="text-center"><?=Utils::format($Row['avg_sp'], 2)?></td>
                        <td class="text-right font-weight-bold <?=$Profit > 0 ? 'text-success' : 'text-danger' ?>"><?=Utils::format($Profit, 2)?></td>
                    </tr>
                <? } ?>
                </tbody>
                <tfoot>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="text-right font-weight-bold display-6" <? Utils::sumReportColumn('[data-js="qty"]'); ?>>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init(DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y')) ?>
<? include_once("../includes/inc_footer.php") ?>