<?
/**
 * Sales Profit: purchases are raw material, sales are finished goods (no stock posted on sale),
 * raw material is taken out with Stock Use vouchers. Profit = sales invoices less returns less Stock Use cost, by day or month.
 */
$_GET['_page_title'] = 'Sales Profit Report';
$_GET['_page_title_text'] = 'Sales less raw material used (Stock Use)';

include_once("../includes/inc_header.php");

SecurityShell::redirectIfNoAccess(CompanyModule::STOCK, "Sales Profit");

$_GET['date_from'] = $_GET['date_from'] ?? date('m/01/Y');
$_GET['date_till'] = $_GET['date_till'] ?? date('m/d/Y');
$GroupBy = ($_GET['group_by'] ?? '') == 'month' ? 'month' : 'day';

$oStockReport = new StockReport();
$Periods = $oStockReport->getSalesProfitByPeriod($_GET['date_from'], $_GET['date_till'], $GroupBy);

$SalesTypes = [
    VoucherType::OIV => 'Order (OIV)',
    VoucherType::WIV => 'Website (WIV)',
    VoucherType::DIV => 'Dine-in (DIV)',
    VoucherType::WHIV => 'WhatsApp (WHIV)',
    VoucherType::SIV => 'Sales (SIV)',
];

$Totals = ['sales' => array_fill_keys(array_keys($SalesTypes), 0), 'returns' => 0, 'net' => 0, 'material' => 0, 'profit' => 0];

function profitPercent($Profit, $Sales)
{
    return $Sales != 0 ? number_format($Profit / $Sales * 100, 1) : '-';
}
?>
<form action="" method="get" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-xl-5 col-md-6">
                        <div class="form-group mb-0">
                            <label>Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] == '' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] == '' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="group_by">Show</label>
                            <select id="group_by" name="group_by"><? Utils::getDropDownOptionsFromArray(['day' => 'Day wise', 'month' => 'Month wise'], $GroupBy); ?></select>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-xl-4 col-md-6'); ?>
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
                        <h3 class="card-title"> Sales Profit (Sales less Raw Material Used)</h3>
                    </div>
                    <div class="col-auto ml-auto">
                        <? UtilsPrint::button() ?>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table data-table-report color table-striped <? UtilsPrint::makePrintable() ?>">
                        <thead>
                        <tr>
                            <th><?=$GroupBy == 'month' ? 'Month' : 'Date'?></th>
                            <? foreach ($SalesTypes as $Name) { ?>
                                <th class="text-right"><?=$Name?></th>
                            <? } ?>
                            <th class="text-right">Returns</th>
                            <th class="text-right">Net Sales</th>
                            <th class="text-right">Raw Material Used</th>
                            <th class="text-right">Profit</th>
                            <th class="text-center">Profit %</th>
                        </tr>
                        </thead>
                        <tbody>
                        <? foreach ($Periods as $Period => $Row) {
                            $Returns = $Row['returns'] ?? 0;
                            $Material = $Row['material'] ?? 0;
                            $NetSales = array_sum($Row['sales'] ?? []) - $Returns;
                            $Profit = $NetSales - $Material;

                            foreach ($SalesTypes as $VoucherTypeId => $Name)
                                $Totals['sales'][$VoucherTypeId] += $Row['sales'][$VoucherTypeId] ?? 0;
                            $Totals['returns'] += $Returns;
                            $Totals['net'] += $NetSales;
                            $Totals['material'] += $Material;
                            $Totals['profit'] += $Profit;
                            ?>
                            <tr>
                                <td data-order="<?=$Period?>"><?=date($GroupBy == 'month' ? 'M Y' : 'd M Y', strtotime($GroupBy == 'month' ? $Period.'-01' : $Period))?></td>
                                <? foreach ($SalesTypes as $VoucherTypeId => $Name) { ?>
                                    <td class="text-right"><?=Utils::format($Row['sales'][$VoucherTypeId] ?? 0, 2)?></td>
                                <? } ?>
                                <td class="text-right"><?=Utils::format($Returns, 2)?></td>
                                <td class="text-right font-weight-bold"><?=Utils::format($NetSales, 2)?></td>
                                <td class="text-right"><?=Utils::format($Material, 2)?></td>
                                <td class="text-right <? Utils::highlightText($Profit); ?>"><?=Utils::format($Profit, 2)?></td>
                                <td class="text-center <? Utils::highlightText($Profit); ?>"><?=profitPercent($Profit, $NetSales)?></td>
                            </tr>
                        <? } ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td class="font-weight-bolder">Total</td>
                            <? foreach ($SalesTypes as $VoucherTypeId => $Name) { ?>
                                <td class="font-weight-bolder text-right"><?=Utils::format($Totals['sales'][$VoucherTypeId], 2)?></td>
                            <? } ?>
                            <td class="font-weight-bolder text-right"><?=Utils::format($Totals['returns'], 2)?></td>
                            <td class="font-weight-bolder text-right"><?=Utils::format($Totals['net'], 2)?></td>
                            <td class="font-weight-bolder text-right"><?=Utils::format($Totals['material'], 2)?></td>
                            <td class="font-weight-bolder text-right <? Utils::highlightText($Totals['profit']); ?>"><?=Utils::format($Totals['profit'], 2)?></td>
                            <td class="font-weight-bolder text-center"><?=profitPercent($Totals['profit'], $Totals['net'])?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                <small class="text-muted">Sales are invoice totals (incl. delivery fee, after discount). Raw Material Used is Stock Use vouchers at purchase cost.</small>
            </div>
        </div>
    </div>
</form>
<? UtilsPrint::init(DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y')) ?>
<? include_once("../includes/inc_footer.php"); ?>
