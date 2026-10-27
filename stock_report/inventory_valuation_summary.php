<?
$_GET['_page_title'] = 'Inventory Valuation Summary';
$_GET['_page_title_text'] = 'Inventory Valuation Summary';

include_once("../includes/inc_header.php");

$StockReport = new StockReport();

if (isset($_GET['search']))
{
    $Select = $StockReport->getInventoryValuationSummary($_GET['date_till'], $_GET['branch_id']);
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
    <? if (isset($_GET['search'])) { ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="row">
                    <div class="col-auto mr-auto">
                        <h3 class="card-title"> Inventory Valuation Summary by Category</h3>
                    </div>
                    <div class="col-auto ml-auto">
                        <? UtilsPrint::button() ?>
                    </div>
                </div>
                <table class="table data-table-report color table-striped font-size-14 font-weight-bolder <? UtilsPrint::makePrintable() ?>">
                    <thead>
                    <tr>
                        <th width="40%">Category Name</th>
                        <th class="text-center" width="30%">Total Quantity</th>
                        <th class="text-right" width="30%">Total Value</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?
                    while ($Row = DBQM::fetch_assoc($Select)) {
                        $TotalQty += $Row['total_qty'];
                        $TotalAmount += $Row['total_value'];
                        ?>
                        <tr>
                            <td><?=$Row['category']?></td>
                            <td class="text-center" data-js="qty"><?=Utils::format($Row['total_qty'], 0)?></td>
                            <td class="text-right"><?=Utils::format($Row['total_value'], 0)?></td>
                        </tr>
                    <? } ?>
                    <tr>
                        <td class="text-right font-weight-bold display-6">Grand Total:</td>
                        <td class="text-center font-weight-bold display-6 text-success"><?=Utils::format($TotalQty, 0)?></td>
                        <td class="font-weight-bold text-right display-6 text-primary"><?=Utils::format($TotalAmount, 0)?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <? } ?>
</section>
<? UtilsPrint::init("Till - ".DateEncode($_GET['date_till'], 'd/m/Y')) ?>
<? include_once("../includes/inc_footer.php") ?>
