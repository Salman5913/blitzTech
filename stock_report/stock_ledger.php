<?
$_GET['_page_title'] = 'Stock Ledger Report';
$_GET['_page_title_text'] = 'Detailed Report of Stock';

include_once("../includes/inc_header.php");

$oStockReport = new StockReport();
$Balance = 0;

if (isset($_GET['search']))
{
    list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_GET['stock_id']);

    $oStockReport->setDate($_GET['date_from'], $_GET['date_till']);
    $Select = $oStockReport->getReport($StockId, $AttributeId, $_GET['branch_id']);

    if (!empty($_GET['date_from']))
        $Balance = StockReport::getStockBalance($StockId, $AttributeId, $_GET['date_from'], 'from', $_GET['branch_id'], $_GET['cost_center_id'] ?? '');
}
?>
<form action="" method="get" <? Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label for="stock_id">Item Name</label>
                            <input type="text" name="stock_id" id="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'], ''); Utils::isRequired();?>>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
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
                            <label for="branch_id">Branch</label>
                            <input type="text" name="branch_id" id="branch_id" <? Autocomplete::Search(Autocomplete::TYPE_BRANCH, $_GET['branch_id']);?>>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-lg-4 col-md-6'); ?>
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
                    <h3 class="card-title">Detailed Report of <strong><?=Stock::getName($StockId, $AttributeId) ?><?=!empty($_GET['branch_id']) ? (' in '.Branch::getName($_GET['branch_id'])) : '' ?></strong> -
                        <a href="javascript:void(0)" <? Utils::AjaxLoadedPopup('Bath-Wise Stock Report Of '.Stock::getName($StockId, $AttributeId), ProjectHelper::SiteURL()."stock_report/ajax_get_batch_wise_stock_report.php?stock_id=".$_GET['stock_id']."&cost_center_id=".urlencode($_GET['cost_center_id'] ?? '')) ?>><i class="mdi mdi-view-list"></i> Batch-Wise Stock Report</a>
                    </h3>
                </div>
                <div class="col-auto ml-auto">
                    <? UtilsPrint::button() ?>
                </div>
            </div>
            <table id="data-table" class="table  color table-striped <? UtilsPrint::makePrintable() ?>">
                <thead>
                <tr>
                    <th width="13%">Date</th>
                    <th width="10%">Voucher</th>
                    <th width="20%">&nbsp;</th>
                    <th width="16%">Branch</th>
                    <th width="7%" class="text-right">Qty In</th>
                    <th width="7%" class="text-right">Qty Out</th>
                    <th width="7%" class="text-right">Balance</th>
                    <th width="10%" class="text-right">Cost</th>
                    <th width="10%" class="text-right">Selling Price</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>-</td>
                    <td>-</td>
                    <td>Balance B/F</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td class="text-right"><?=Utils::format($Balance, 0)?></td>
                    <td>-</td>
                    <td>-</td>
                </tr>
                <? while ($Row = DBQM::fetch_assoc($Select)) {
                    $Balance += $Row['qty'];
                    ?>
                    <tr <? if ($Row['qty'] == 0) { ?>class="bg-danger"<? } ?>>
                        <td><?=DateDecode($Row['date_time'], 'M d, Y')?></td>
                        <td title="<?=$Row['voucher_name']?>"><?=$Row['voucher_type']?> - <?=$Row['voucher_number']?> <a href="<?=VoucherType::getLink($Row['voucher_type_id'], $Row['voucher_id'])?>" title="Edit Voucher" target="_blank"><i class="mdi mdi-pencil"></i> </a> </td>
                        <td><?=$Row['customer_name']?></td>
                        <td><?=$Row['branch_name']?></td>
                        <td class="text-right text-success" data-js="qty-in"><? if ($Row['qty'] > 0) { ?><?=Utils::format($Row['qty'], 0)?><? } ?></td>
                        <td class="text-right text-danger" data-js="qty-out"><? if ($Row['qty'] < 0) { ?><?=Utils::format(abs($Row['qty']), 0)?><? } ?></td>
                        <td class="text-right"><?=Utils::format($Balance, 0)?></td>
                        <td class="text-right text-warning"><?=Utils::format($Row['cost'], 0)?></td>
                        <td class="text-right"><?=Utils::format($Row['sp'], 0)?></td>
                    </tr>
                <? } ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="4"></td>
                    <td class="text-right font-weight-bold text-success display-6" <? Utils::sumReportColumn('[data-js="qty-in"]');?>></td>
                    <td class="text-right font-weight-bold text-danger display-6" <? Utils::sumReportColumn('[data-js="qty-out"]');?>></td>
                    <td class="text-right font-weight-bold text-primary display-6" ><?=Utils::format($Balance, 0)?></td>
                    <td colspan="2"></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init("Item Name: ".Stock::getName($StockId, $AttributeId)." (".DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y').") ") ?>
<? include_once("../includes/inc_footer.php") ?>