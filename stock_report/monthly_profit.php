<?
$_GET['_page_title'] = 'Monthly Customer Wise Profit Report';
$_GET['_page_title_text'] = 'View Monthly Customer Wise Profit';

include_once("../includes/inc_header.php");

$oStock = new StockReport();
list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_GET['stock_id']);

$Select = $oStock->getMonthlyProfit($_GET['date_from'], $_GET['date_till'], $_GET['customer_id'], $StockId, $_GET['category_id'], $_GET['brand']);

$TotalProfit = 0;
$TotalSales = 0;
$TotalCost = 0;
?>
<form action="" method="get" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label>Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] =='' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] =='' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="customer_id">Customer Name</label>
                            <input type="text" name="customer_id" id="customer_id" <? Autocomplete::ACSearchAccount($_GET['customer_id']); ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group"> 
                            <label for="stock_id">Stock Item</label>
                            <input type="text" id="stock_id" name="stock_id"  <? Autocomplete::ACSearchStock($_GET['stock_id'], ''); ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <input type="text" id="category_id" name="category_id"  <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $_GET['category_id']); ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <input type="text" id="brand" name="brand"  <? Autocomplete::Search(Autocomplete::TYPE_STOCK_BRAND, $_GET['brand']); ?>>
                        </div>
                    </div>
                    <div class="col-xl-12 text-right">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="row">
                    <div class="col-auto mr-auto">
                        <h3 class="card-title"> Full Report of Stock Value</h3>
                    </div>
                    <div class="col-auto ml-auto">
                        <? UtilsPrint::button() ?>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table data-table-report color table-striped <? UtilsPrint::makePrintable() ?>">
                        <thead>
                        <tr>
                            <th>Customer Number</th>
                            <th class="text-right">Sales</th>
                            <th class="text-right">Cost</th>
                            <th class="text-right">Profit</th>
                            <th class="text-center">Profit %</th>
                        </tr>
                        </thead>
                        <tbody>
                        <? while ($Row = DBQM::fetch_assoc($Select)) {
                            $Profit = $Row['sales'] - $Row['cost'];
                            if($Row['sales']!=0)
                            {
                                $ProfitPercentage = (1 - ($Row['cost'] / $Row['sales']));
                            }
                            else{
                                $ProfitPercentage = (1 - ($Row['cost']));
                            }

                            $TotalProfit += $Profit;
                            $TotalSales += round($Row['sales'], 0);
                            $TotalCost += $Row['cost'];
                            ?>
                            <tr>
                                <td><a href="javascript:void(0)" <? Utils::AjaxLoadedPopup('Profit Breakup Report Of '.$Row['customer_name'], ProjectHelper::SiteURL()."stock_report/ajax_get_customer_wise_sales_report.php?customer_id=".$Row['customer_account_id']."&date_from=".$_GET['date_from']."&date_till=".$_GET['date_till']."&stock_id=".$_GET['stock_id']."&category_id=".$_GET['category_id']."&brand=".$_GET['brand']) ?>> <?=$Row['customer_name']?></a></td>
                                <td class="text-right"><?=Utils::format($Row['sales'], 2)?></td>
                                <td class="text-right"><?=Utils::format($Row['cost'], 2)?></td>
                                <td class="text-right <? Utils::highlightText($Profit); ?>"><?=Utils::format($Profit, 2)?></td>
                                <td class="text-center <? Utils::highlightText($Profit); ?>"><?=Utils::format($ProfitPercentage * 100, 1)?></td>
                            </tr>
                        <? } ?>
                        <tr>
                            <td></td>
                            <td class="font-weight-bolder text-right"><?=Utils::format($TotalSales, 2)?></td>
                            <td class="font-weight-bolder text-right"><?=Utils::format($TotalCost, 2)?></td>
                            <td class="font-weight-bolder text-right <? Utils::highlightText($TotalProfit); ?>"><?=Utils::format($TotalProfit, 2)?></td>
                            <td></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>
<? UtilsPrint::init(DateEncode($_GET['date_till'], 'd/m/Y')) ?>
<? include_once("../includes/inc_footer.php") ?>