<?
$_GET['_page_title'] = 'Stock Price List';
$_GET['_page_title_text'] = '';

include_once("../includes/inc_header.php");

$oStockReport = new StockReport();

if (isset($_GET['search']))
    $Select = $oStockReport->getAllStockByCategory($_GET['category_id']);
?>
<form action="" method="get" <? Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-xl-6 col-md-6">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <input type="text" name="category_id" id="category_id" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $_GET['category_id']);?> >
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
                    <h3 class="card-title"> Stock Price List</h3>
                </div>
                <div class="col-auto ml-auto">
                    <? UtilsPrint::button() ?>
                </div>
            </div>
            <table class="table data-table-report color table-striped <? UtilsPrint::makePrintable() ?>">
                <thead>
                <tr>
                    <th>Item Name</th>
                    <th class="text-center">Selling Price</th>
                    <th class="text-right">Available Qty</th>
                </tr>
                </thead>
                <tbody>
                <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                    <tr>
                        <td><a href="<?=Reporting::getStockLink($Row['id'], $Row['attribute_id'], '', $_GET['date_till'], '')?>" target="_blank"><?=$Row['ac_stock']?></a></td>
                        <td class="text-center"><?=Utils::format($Row['selling_price'])?></td>
                        <td data-js="qty" class="text-right"><?=$Row['available_qty']?></td>
                    </tr>
                <? } ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="2">&nbsp;</td>
                    <td class="text-right font-weight-bold display-6" <? Utils::sumReportColumn('[data-js="qty"]');?>></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init('') ?>
<? include_once("../includes/inc_footer.php") ?>