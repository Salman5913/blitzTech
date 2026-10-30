<?
include_once("../connection/localhost.php");

$oStockReportMIS = new StockReportMIS();
$HeadingSuffix = '';

if (isset($_GET['search']))
{
    $oStockReportMIS->setDate($_GET['date_from'], $_GET['date_till']);
    $Select = $oStockReportMIS->getReport($_GET['category_id'], $_GET['brand'], $_GET['sort_by'], $_GET['stock_id']);

    $HeadingSuffix = " - ".$oStockReportMIS->DisplayType[$_GET['sort_by']];
}

$TotalSoldQty = 0;
$TotalProfit = 0;
$TotalAvQty = 0;

$_GET['_page_title'] = 'MIS Stock Report'.$HeadingSuffix;
$_GET['_page_title_text'] = 'Overview Report for Management';

include_once("../includes/inc_header.php");
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
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <input type="text" name="brand" id="brand" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_BRAND, $_GET['brand']);?>>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="sort_by">Display Type</label>
                            <select name="sort_by" id="sort_by"><? Utils::getDropDownOptionsFromArray($oStockReportMIS->DisplayType, $_GET['sort_by']);?></select>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-lg-3 col-md-6'); ?>
                    <div class="col-sm-12 text-right">
                        <button type="submit" class="btn color" name="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
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
                    <h3 class="card-title">Overview Report</h3>
                </div>
                <div class="col-auto ml-auto">
                    <? UtilsPrint::button() ?>
                </div>
            </div>
            <table id="data-table" class="table color table-striped <? UtilsPrint::makePrintable() ?>">
                <thead>
                <tr>
                    <th width="20%">Item Name</th>
                    <th width="10%" class="text-right">Sold Qty</th>
                    <th width="10%" class="text-right">Avg. Cost</th>
                    <th width="10%" class="text-right">Avg. SP</th>
                    <th width="10%" class="text-right">Avg. Profit</th>
                    <th width="10%" class="text-right">Profit %</th>
                    <th width="15%" class="text-center">Last Sold On</th>
                    <th width="10%" class="text-right">Max Sold in Single Sales</th>
                    <th width="15%" class="text-right">Avg. Sales/Month</th>
                    <th width="10%" class="text-right">Available Qty</th>
                </tr>
                </thead>
                <tbody>
                <?
                    while ($Row = DBQM::fetch_assoc($Select))
                    {
                        $TotalSoldQty += $Row['total_sales'];
                        $TotalProfit += ($Row['avg_profit'] * $Row['total_sales']);
                        $TotalAvQty += $Row['available_qty'];
                ?>
                    <tr>
                        <td><a href="<?=Reporting::getStockLink($Row['id'], $Row['attribute_id'], $_GET['date_from'], $_GET['date_till'], '') ?>" target="_blank"><?=$Row['ac_stock']?></a></td>
                        <td class="text-right"><?=Utils::format($Row['total_sales'], 0)?></td>
                        <td class="text-right"><?=Utils::format($Row['avg_cost'], 0)?></td>
                        <td class="text-right"><?=Utils::format($Row['avg_sp'], 0)?></td>
                        <td class="text-right <?=$Row['avg_profit'] >= 0 ? 'text-success' : 'text-danger'?>"><?=Utils::format($Row['avg_profit'], 0)?></td>
                        <td class="text-right <?=$Row['profit_percentage'] >= 0 ? 'text-success' : 'text-danger'?>"><?=Utils::format($Row['profit_percentage'] * 100, 0)?>%</td>
                        <td class="text-center"><?=DateDecode($Row['last_sales_date'])?></td>
                        <td class="text-right"><?=Utils::format($Row['one_time_max_sales'], 0)?></td>
                        <td class="text-right"><?=Utils::format($Row['avg_sales_per_month'], 0)?></td>
                        <td class="text-right"><?=Utils::format($Row['available_qty'], 0)?></td>
                    </tr>
                <? } ?>
                <tr>
                    <td></td>
                    <td class="font-weight-bold text-right"><?=Utils::format($TotalSoldQty, 0)?></td>
                    <td></td>
                    <td class="font-weight-bold text-right">Total Profit: </td>
                    <td class="font-weight-bold text-right"><?=Utils::format($TotalProfit, 0)?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="font-weight-bold text-right"><?=Utils::format($TotalAvQty, 0)?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init(DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y')) ?>
<? include_once("../includes/inc_footer.php") ?>