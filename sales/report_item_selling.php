<?
/**
 * Item Selling report: how much of each item was sold on each date, per branch.
 * Access: Sales > Item Selling (sql/2026_11_02_item_selling_report.sql)
 */
include_once("../connection/localhost.php");
SecurityShell::redirectIfNoAccess("Sales", "Item Selling");

$_GET['_page_title'] = 'Item Selling Report';
$_GET['_page_title_text'] = 'Qty of each item sold per date and branch';

// Opens on today
$_GET['date_from'] = $_GET['date_from'] ?? date('m/d/Y');
$_GET['date_till'] = $_GET['date_till'] ?? date('m/d/Y');

include_once("../includes/inc_header.php");

$oSalesReport = new SalesReport();
$Select = $oSalesReport->getItemSelling($_GET['date_from'], $_GET['date_till'], $_GET['stock_id'] ?? '', $_GET['branch_id'] ?? '');

$PrintDescription = "Item Selling Report : ".DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y');
if (!empty($_GET['branch_id']))
    $PrintDescription .= " | Branch: ".Branch::getName($_GET['branch_id']);
if (!empty($_GET['cost_center_id']))
    $PrintDescription .= " | Cost Center: ".CostCenter::getName($_GET['cost_center_id']);

list($FilterStockId, $FilterAttributeId) = array_pad(Stock::splitStockIdAttributeId($_GET['stock_id'] ?? ''), 2, '');
?>
<form action="" method="get" <? Utils::formValidate();?> >
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Filter Options</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] == '' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] == '' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="stock_id">Item</label>
                            <input type="text" id="stock_id" name="stock_id" <? Autocomplete::ACSearchStock($FilterStockId, $FilterAttributeId); ?> >
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="branch_id">Branch</label>
                            <input type="text" id="branch_id" name="branch_id" <? Autocomplete::Search(Autocomplete::TYPE_BRANCH, $_GET['branch_id'] ?? ''); ?>>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-md-4'); ?>
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
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Item Name</th>
                        <th class="text-center">Qty Sold</th>
                        <th class="text-right">Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    <? $TotalQty = $TotalAmount = 0; while ($Row = DBQM::fetch_assoc($Select)) { $TotalQty += $Row['total_quantity']; $TotalAmount += $Row['total_sale_price']; ?>
                        <tr>
                            <td data-order="<?=$Row['sale_date']?>"><?=DateDecode($Row['sale_date'])?></td>
                            <td><?=$Row['branch_name']?></td>
                            <td><?=$Row['ac_stock']?></td>
                            <td class="text-center"><?=Utils::format($Row['total_quantity'], 0)?></td>
                            <td class="text-right"><?=Utils::format($Row['total_sale_price'])?></td>
                        </tr>
                    <? } ?>
                    </tbody>
                    <tfoot>
                    <tr class="font-weight-bold">
                        <td colspan="3">Total</td>
                        <td class="text-center"><?=Utils::format($TotalQty, 0)?></td>
                        <td class="text-right"><?=Utils::format($TotalAmount)?></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</form>
<? UtilsPrint::init($PrintDescription); ?>
<? include_once("../includes/inc_footer.php") ?>
