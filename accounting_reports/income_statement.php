<?
$_GET['_page_title'] = 'Income Statement';
$_GET['_page_title_text'] = 'Income Statement';

include_once("../includes/inc_header.php");

// opens on the current month, no search needed
$_GET['date_from'] = ($_GET['date_from'] ?? '') != '' ? $_GET['date_from'] : date('m/01/Y');
$_GET['date_till'] = ($_GET['date_till'] ?? '') != '' ? $_GET['date_till'] : date('m/d/Y');

$oIncomeStatement = new IncomeStatement();
$oIncomeStatement->setDate($_GET['date_from'], $_GET['date_till']);
$isShowZero = ($_GET['show_zero'] ?? '') == 'Y';
$oIncomeStatement->setHideZeroRows(!$isShowZero);

list($OpeningStock, $Purchases, $ClosingStock) = $oIncomeStatement->getCOGS();
$COGS = $OpeningStock + $Purchases - $ClosingStock;

// heads print their own rows; totals are needed first for the summary tiles
ob_start();
$Sales = $oIncomeStatement->getHeads(4, true);
$RevenueRows = ob_get_clean();

ob_start();
$TotalExpense = $oIncomeStatement->getHeads(5, false, [getConstantCategory()->getCostOfGoodSold()]);
$ExpenseRows = ob_get_clean();

$GrossProfit = $Sales - $COGS;
$Profit = $GrossProfit - $TotalExpense;

/* ************* Sales by invoice type (invoice totals) ************* */
$SalesTypes = [
    VoucherType::OIV => ['Order Invoice (OIV)', '#3b82f6'],
    VoucherType::WIV => ['Website Invoice (WIV)', '#16a34a'],
    VoucherType::DIV => ['Dine-in Invoice (DIV)', '#8b5cf6'],
    VoucherType::WHIV => ['WhatsApp Invoice (WHIV)', '#25d366'],
    VoucherType::SIV => ['Sales Invoice (SIV)', '#f59e0b'],
];
$TypeSales = array_fill_keys(array_keys($SalesTypes), 0);
$Returns = 0;
foreach ((new StockReport())->getSalesProfitByPeriod($_GET['date_from'], $_GET['date_till'], 'month') as $Period)
{
    foreach ($Period['sales'] ?? [] as $VoucherTypeId => $Amount)
        $TypeSales[$VoucherTypeId] = ($TypeSales[$VoucherTypeId] ?? 0) + $Amount;
    $Returns += $Period['returns'] ?? 0;
}
$TotalTypeSales = array_sum($TypeSales);
/* ************* Sales by invoice type (invoice totals) ************* */

function percentOfRevenue($Amount, $Sales)
{
    return $Sales != 0 ? number_format($Amount / $Sales * 100, 1).'%' : '-';
}

$DateUrl = "date_from=".urlencode($_GET['date_from'])."&date_till=".urlencode($_GET['date_till']).AccountingReport::costCenterUrlParam();
?>
<style>
    .is-tile { border-radius: 12px; padding: 1rem 1.1rem; margin-bottom: 1.25rem; border-left: 4px solid var(--tile); }
    .is-tile small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #475467; }
    .is-tile h3 { margin: .25rem 0 0; font-size: 1.35rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .is-tile .is-note { font-size: .75rem; color: #667085; }
    .is-table td { vertical-align: middle; }
    .is-table .is-section td { background: #f2f7f6; font-weight: 700; text-transform: uppercase; font-size: .78rem; letter-spacing: .05em; color: #0b3866; }
    .is-table .is-total td { font-weight: 700; border-top: 2px solid #d4e3e1; }
    .is-table .is-pct { color: #667085; font-size: .8rem; width: 110px; }
    .is-channel { margin-bottom: .9rem; }
    .is-channel .is-bar { height: 8px; border-radius: 4px; background: #eef2f6; overflow: hidden; margin-top: .3rem; }
    .is-channel .is-bar span { display: block; height: 100%; border-radius: 4px; }
</style>
<form action="" method="get">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Filter Options</h3>
                <div class="row">
                    <div class="col-xl-5 col-md-6">
                        <div class="form-group">
                            <label>Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-xl-3 col-md-6'); ?>
                    <div class="col-xl-2 col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="show_zero" name="show_zero" value="Y" <?=$isShowZero ? 'checked' : ''?>>
                                <label class="custom-control-label" for="show_zero">Show zero balances</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn button color form-control" name="search" id="search"><i class="fa fa-search"></i> Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="row">
    <div class="col-xl col-md-4 col-6">
        <div class="card is-tile" style="--tile: #f59e0b;"><small>Revenue</small><h3><?=Utils::format($Sales)?></h3><span class="is-note">Income from sales</span></div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="card is-tile" style="--tile: #64748b;"><small>Cost of Goods Sold</small><h3><?=Utils::format($COGS)?></h3><span class="is-note">Raw material used</span></div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="card is-tile" style="--tile: #0b3866;"><small>Gross Profit</small><h3><?=Utils::format($GrossProfit)?></h3><span class="is-note"><?=percentOfRevenue($GrossProfit, $Sales)?> of revenue</span></div>
    </div>
    <div class="col-xl col-md-6 col-6">
        <div class="card is-tile" style="--tile: #e11d48;"><small>Expenses</small><h3><?=Utils::format($TotalExpense)?></h3><span class="is-note"><?=percentOfRevenue($TotalExpense, $Sales)?> of revenue</span></div>
    </div>
    <div class="col-xl col-md-6 col-12">
        <div class="card is-tile" style="--tile: <?=$Profit < 0 ? '#dc2626' : '#16a34a'?>;"><small>Net <?=$Profit < 0 ? 'Loss' : 'Profit'?></small><h3 class="<?=$Profit < 0 ? 'text-danger' : 'text-success'?>"><?=Utils::format($Profit)?></h3><span class="is-note"><?=percentOfRevenue($Profit, $Sales)?> of revenue</span></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="row">
                <div class="col-md-6"><h3 class="card-title">Statement</h3></div>
                <div class="col-md-6 text-right"><? UtilsPrint::button() ?></div>
            </div>
            <table class="table is-table <? UtilsPrint::makePrintable() ?>">
                <tr class="is-section"><td>Revenue</td><td class="text-right">Amount</td></tr>
                <?=$RevenueRows?>
                <tr class="is-total bg-soft-warning">
                    <td>Total Revenue</td>
                    <td class="text-right"><?=Utils::format($Sales)?></td>
                </tr>

                <tr class="is-section"><td>Cost of Goods Sold <small class="text-muted text-lowercase">(raw material used)</small></td><td></td></tr>
                <tr>
                    <td><small>Opening Stock</small></td>
                    <td class="text-right"><small><?=Utils::format($OpeningStock)?></small></td>
                </tr>
                <tr>
                    <td><small>+ Purchases</small></td>
                    <td class="text-right"><small><?=Utils::format($Purchases)?></small></td>
                </tr>
                <tr>
                    <td><small>- Closing Stock</small></td>
                    <td class="text-right"><small><?=Utils::format($ClosingStock)?></small></td>
                </tr>
                <tr class="is-total">
                    <td><a href="expanded_report.php?<?=$DateUrl?>&coa_id=<?=getConstantCategory()->getCostOfGoodSold()?>">Cost of Goods Sold</a> <span class="is-pct">(<?=percentOfRevenue($COGS, $Sales)?> of revenue)</span></td>
                    <td class="text-right"><?=Utils::format($COGS)?></td>
                </tr>
                <tr class="bg-dark text-white">
                    <td class="font-weight-bold">Gross Profit <small>(<?=percentOfRevenue($GrossProfit, $Sales)?> of revenue)</small></td>
                    <td class="text-right font-weight-bold"><?=Utils::format($GrossProfit)?></td>
                </tr>

                <tr class="is-section"><td>Expenses</td><td></td></tr>
                <?=$ExpenseRows?>
                <tr class="is-total bg-soft-danger">
                    <td>Total Expense <span class="is-pct">(<?=percentOfRevenue($TotalExpense, $Sales)?> of revenue)</span></td>
                    <td class="text-right"><?=Utils::format($TotalExpense)?></td>
                </tr>

                <tr class="text-white font-weight-bold <?=$Profit <= 0 ? 'bg-danger' : 'bg-success'?>">
                    <td class="font-weight-bold">Net Profit / Loss <small>(<?=percentOfRevenue($Profit, $Sales)?> of revenue)</small></td>
                    <td class="text-right font-weight-bold"><?=Utils::format($Profit)?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <h3 class="card-title">Sales by Invoice Type</h3>
            <? foreach ($SalesTypes as $VoucherTypeId => list($Name, $Color)) {
                $Amount = $TypeSales[$VoucherTypeId];
                $Share = $TotalTypeSales != 0 ? $Amount / $TotalTypeSales * 100 : 0;
                ?>
                <div class="is-channel">
                    <div class="d-flex justify-content-between">
                        <a href="<?=ProjectHelper::SiteURL()?>sales/report_sales.php?voucher_type_id=<?=$VoucherTypeId?>&date_from=<?=urlencode($_GET['date_from'])?>&date_till=<?=urlencode($_GET['date_till'])?>&search=" target="_blank"><?=$Name?></a>
                        <span><b><?=Utils::format($Amount)?></b> <small class="text-muted"><?=Utils::format($Share, 1)?>%</small></span>
                    </div>
                    <div class="is-bar"><span style="width: <?=round($Share, 1)?>%; background: <?=$Color?>;"></span></div>
                </div>
            <? } ?>
            <div class="d-flex justify-content-between border-top pt-2">
                <span>Less Returns (SRV)</span><span class="text-danger"><?=Utils::format(-$Returns)?></span>
            </div>
            <div class="d-flex justify-content-between font-weight-bold pt-1">
                <span>Net Sales</span><span><?=Utils::format($TotalTypeSales - $Returns)?></span>
            </div>
            <small class="text-muted d-block mt-2">Invoice totals (incl. delivery fee); can differ from Revenue when other income is posted to revenue accounts.</small>
        </div>
    </div>
</div>
<? UtilsPrint::init(DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y').AccountingReport::costCenterHeading()) ?>
<? include_once("../includes/inc_footer.php"); ?>
