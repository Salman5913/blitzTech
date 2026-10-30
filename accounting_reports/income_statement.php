<?
$_GET['_page_title'] = 'Income Statement';
$_GET['_page_title_text'] = 'Income Statement';

include_once("../includes/inc_header.php");

$oIncomeStatement = new IncomeStatement();

$Sales = 0;
$COGS = 0;
$TotalExpense = 0;

if (isset($_GET['search']))
{
    $DateFrom = $_GET['date_from'];
    $DateTill = $_GET['date_till'];

    $oIncomeStatement->setDate($DateFrom, $DateTill);

    list($OpeningStock, $Purchases, $ClosingStock) = $oIncomeStatement->getCOGS();
    $COGS = $OpeningStock + $Purchases - $ClosingStock;
}
?>
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
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] =='' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] =='' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-xl-3 col-md-6'); ?>
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
    <div class="col-sm-12">
        <div class="card">
            <div class="row">
                <div class="col-md-6"><h3 class="card-title">Statement</h3></div>
                <div class="col-md-6 text-right"><? UtilsPrint::button() ?></div>
            </div>
            <table class="table table-striped <? UtilsPrint::makePrintable() ?>">
                <? $Sales = $oIncomeStatement->getHeads(4, true) ?>
                <tr class="bg-soft-warning">
                    <td>Total Revenue</td>
                    <td class="text-right"><?=Utils::format($Sales)?></td>
                </tr>
                <tr>
                    <td><a href="expanded_report.php?date_from=<?=$_GET['date_from']?>&date_till=<?=$_GET['date_till']?>&coa_id=<?=getConstantCategory()->getCostOfGoodSold()?><?=AccountingReport::costCenterUrlParam()?>">Cost of Goods Sold</a></td>
                    <td class="text-right"><?=Utils::format($COGS)?></td>
                </tr>
                <tr>
                    <td class="text-right"><small>Opening Stock</small></td>
                    <td class="text-right"><small><?=Utils::format($OpeningStock)?></small></td>
                </tr>
                <tr>
                    <td class="text-right"><small>+ Purchases</small></td>
                    <td class="text-right"><small><?=Utils::format($Purchases)?></small></td>
                </tr>
                <tr>
                    <td class="text-right"><small>- Closing Stock</small></td>
                    <td class="text-right"><small><?=Utils::format($ClosingStock)?></small></td>
                </tr>
                <tr class="bg-dark text-white">
                    <td class="font-weight-bold">Gross Profit</td>
                    <td class="text-right font-weight-bold"><?=Utils::format($Sales - $COGS)?></td>
                </tr>
                <? $TotalExpense = $oIncomeStatement->getHeads(5, false, [getConstantCategory()->getCostOfGoodSold()]) ?>
                <tr class="bg-soft-danger">
                    <td>Total Expense</td>
                    <td class="text-right"><?=Utils::format($TotalExpense)?></td>
                </tr>
                <?
                $Profit = $Sales - $COGS - $TotalExpense;
                ?>
                <tr class="text-white font-weight-bold <?=$Profit <= 0 ? 'bg-danger' : 'bg-success'?>">
                    <td class="font-weight-bold">Net Profit / Loss</td>
                    <td class="text-right font-weight-bold"><?=Utils::format($Profit)?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init(DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y').AccountingReport::costCenterHeading()) ?>
<? include_once("../includes/inc_footer.php"); ?>