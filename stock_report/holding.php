<?
$_GET['_page_title'] = 'Stock Holding Report';
$_GET['_page_title_text'] = 'Stock Holding Report';

include_once("../includes/inc_header.php");

$StockReport = new StockReport();

if (isset($_GET['search']))
{
    $Select = $StockReport->getStockHolding($_GET['stock_id'], $_GET['date_till'], $_GET['branch_id'], $_GET['category_id'], $_GET['brand']);
}

$TotalQty = 0;
$TotalAmount = 0;
?>
<section class="voucher-report">
    <form action="" method="get" <? Utils::formValidate(); ?>>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <h3 class="card-title">Filter Options</h3>
                    <div class="row">
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group">
                                <label for="stock_id">Item Code</label>
                                <input type="text" name="stock_id" id="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'], '') ?>>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group">
                                <label for="date_till">Date Till</label>
                                <input type="text" name="date_till" id="date_till" data-type="date" value="<?=$_GET['date_till']?>" data-empty="true">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group">
                                <label for="branch_id">Branch</label>
                                <input type="text" name="branch_id" id="branch_id" <? Autocomplete::Search(Autocomplete::TYPE_BRANCH, $_GET['branch_id']);?>>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group">
                                <label for="brand">Brand</label>
                                <input type="text" name="brand" id="brand" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_BRAND, $_GET['brand']);?>>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group">
                                <label for="category_id">Category</label>
                                <input type="text" name="category_id" id="category_id" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $_GET['category_id']);?>>
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
                        <h3 class="card-title"> Full Report of Stock Value</h3>
                    </div>
                    <div class="col-auto ml-auto">
                        <? UtilsPrint::button() ?>
                    </div>
                </div>
                <table class="table data-table-report color table-striped font-size-14 font-weight-bolder <? UtilsPrint::makePrintable() ?>">
                    <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Value / Qty</th>
                        <th class="text-right">Value</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?
                    while ($Row = DBQM::fetch_assoc($Select)) {
                        $TotalQty += $Row['total_qty'];
                        $TotalAmount += $Row['total_value'];
                        ?>
                        <tr>
                            <td><a href="<?=Reporting::getStockLink($Row['id'], $Row['attribute_id'], '', $_GET['date_till'], $_GET['branch_id'])?>" target="_blank"><?=$Row['name']?></a></td>
                            <td><?=$Row['brand']?></td>
                            <td><?=$Row['category']?></td>
                            <td class="text-center" data-js="qty"><?=Utils::format($Row['total_qty'], 0)?></td>
                            <td class="text-center"><?=Utils::format(($Row['total_value'] / $Row['total_qty']), 0)?></td>
                            <td class="text-right"><?=Utils::format($Row['total_value'], 0)?></td>
                        </tr>
                    <? } ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="text-center font-weight-bold display-6 text-success" <?/* Utils::sumReportColumn('[data-js="qty"]'); */?>><?=Utils::format($TotalQty, 0)?></td>
                        <td>&nbsp;</td>
                        <td class="font-weight-bold text-right display-6 text-primary"><?=Utils::format($TotalAmount, 0)?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<? UtilsPrint::init("Till - ".DateEncode($_GET['date_till'], 'd/m/Y')) ?>
<? include_once("../includes/inc_footer.php") ?>