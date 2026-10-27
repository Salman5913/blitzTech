<?
$_GET['_page_title'] = 'Stock Reorder Report';
$_GET['_page_title_text'] = 'View All Stock Below Reorder Level';

include_once("../includes/inc_header.php");

$oStockReport = new StockReport();
$Select = false;

if (isset($_GET['search']))
{
    $Select = $oStockReport->getReorderReport($_GET['category_id'], $_GET['stock_id']);
}
?>
<form action="" method="get" <? Utils::formValidate();?> >
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="stock_id">Item Name</label>
                            <input type="text" name="stock_id" id="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'], ''); ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <input type="text" name="category_id" id="category_id" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $_GET['category_id']); ?>>
                        </div>
                    </div>
                    <div class="col-lg-4">
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
                    <h3 class="card-title"> Stock Reorder Level</h3>
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
                    <th class="text-center">Avg Cost</th>
                    <th class="text-right">Available Qty</th>
                    <th class="text-right">Reorder Qty</th>
                    <th class="text-right">Next Purchase Qty</th>
                </tr>
                </thead>
                <tbody>
                <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                    <tr>
                        <td><a href="<?=Reporting::getStockLink($Row['id'], $Row['attribute_id'], '', '', '')?>" target="_blank"><?=$Row['ac_stock']?></a></td>
                        <td><?=$Row['category']?></td>
                        <td class="text-center"><?=Utils::format($Row['avg_cost'])?></td>
                        <td class="text-right"><?=Utils::format($Row['available_qty'], 0)?></td>
                        <td class="text-right"><?=Utils::format($Row['reorder_level'], 0)?></td>
                        <td class="text-right text-success font-weight-bold"><?=Utils::format($Row['reorder_level'] - $Row['available_qty'], 0)?></td>
                    </tr>
                <? } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init('')?>
<? include_once("../includes/inc_footer.php") ?>