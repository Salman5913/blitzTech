<?
$_GET['_page_title'] = 'Purchase Report';
$_GET['_page_title_text'] = 'View Purchase Invoices';

include_once("../includes/inc_header.php");

$oPurchase = new PurchaseReport();
$Select = false;

if (isset($_GET['search']))
{
    $Select = $oPurchase->getAvgPriceNQty($_GET['date_from'], $_GET['date_till'], $_GET['stock_id'], $_GET['brand'], $_GET['category_id']);
    $PrintDescription = "Avg Purchase Report : ".DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y');
    if (!empty($_GET['cost_center_id']))
        $PrintDescription .= " | Cost Center: ".CostCenter::getName($_GET['cost_center_id']);
}
?>
<form action="" method="get" <?Utils::formValidate()?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Filter Options</h3>
                <div class="row">
                    <div class="col-xl-6 col-md-6">
                        <div class="form-group mb-0">
                            <label>Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] == '' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] == '' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-md-6">
                        <div class="form-group">
                            <label for="stock_id">Stock Item</label>
                            <input type="text" id="stock_id" name="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'], ''); ?> >
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
                    <? CostCenter::designReportFilter('col-xl-4 col-md-6'); ?>
                    <div class="col-xl-12 text-right">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="fa fa-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="row">
                    <div class="col-sm-6"><h3 class="card-title"> Search Results</h3></div>
                    <div class="col-sm-6 text-right"><? UtilsPrint::button(); ?></div>
                </div>
                <table id="datatable" class="table data-table-report color table-striped dt-responsive nowrap <? UtilsPrint::makePrintable(); ?>">
                    <thead>
                    <tr>
                        <th>Item Name</th>
                        <th class="text-center">Qty Purchase</th>
                        <th class="text-center">Avg Price</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?
                    while ($Row = DBQM::fetch_assoc($Select)) { ?>
                        <tr>
                            <td><?=$Row['item_name']?></td>
                            <td class="text-center"><?=Utils::format($Row['total_quantity'], 0)?></td>
                            <td class="text-center"><?=Utils::format($Row['total_purchase_price'] / $Row['total_quantity'])?></td>
                        </tr>
                    <? } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
<? UtilsPrint::init($PrintDescription); ?>
<? include_once("../includes/inc_footer.php") ?>