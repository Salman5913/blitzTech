<?
$_GET['_page_title'] = "Balance Sheet";
$_GET['_page_title_text'] = "Balance Sheet";

include_once("../includes/inc_header.php");

$IndentLevel2 = str_repeat("&nbsp; ", 1);
$IndentLevel3 = str_repeat("&nbsp; ", 3);

$oBalanceSheet = new BalanceSheet();
$Level1Accounts = array();
$ProfitForTheYear = 0;

$Assets = $Liability = $Equity = 0;

if (isset($_GET['search']))
{
    $DateTill = $_GET['date_till'];
    $oBalanceSheet->setDate('', $DateTill);
    $oBalanceSheet->init();

    $Level1Accounts = $oBalanceSheet->getAllLevel1();
    $ProfitForTheYear = $oBalanceSheet->getProfitForTheYear();
}
?>
<form action="" method="get">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-xl-5 col-md-6">
                        <div class="form-group">
                            <label>As at Date</label>
                            <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] =='' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn button color form-control" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
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
                <div class="col-md-6"><h3 class="card-title"><i class="fa fa-bar-chart"></i> Report</h3></div>
                <div class="col-auto ml-auto"><? UtilsPrint::button() ?></div>
            </div>
            <table class="table table-striped <? UtilsPrint::makePrintable() ?>">
                <?
                foreach($Level1Accounts as $Level1Code => $Level1)
                {
                    $Level1Total = 0;
                    ?>
                    <tr class="bg-color">
                        <td colspan="2"><?=$Level1?></td>
                    </tr>
                    <?

                    $Level1Total = $oBalanceSheet->prepareDataNMakeRows($Level1Code);

                    if ($Level1Code == 3)
                    {
                        $Level1Total += $ProfitForTheYear;
                        ?>
                        <tr>
                            <td width="75%"><?=$IndentLevel2?><i class="mdi mdi-cash"></i> Profit For The Period</td>
                            <td width="10%" class="text-right"><?=Utils::format($ProfitForTheYear)?></td>
                        </tr>
                        <?
                    }
                    ?>

                    <tr class="success">
                        <td class="font-weight-bold text-dark"><i class="mdi mdi-file-tree"></i> <?=$Level1?> Total</td>
                        <td class="text-right font-weight-bold text-dark"><?=Utils::format($Level1Total)?></td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <?
                    switch($Level1Code)
                    {
                        case 1:
                            $Assets += $Level1Total;
                            break;
                        case 2:
                            $Liability += $Level1Total;
                            break;
                        case 3:
                            $Equity += $Level1Total;
                            break;
                    }
                } ?>
            </table>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <h3 class="card-title"><i class="fa fa-money"></i> Totals</h3>
            <table class="table color">
                <thead>
                    <tr>
                        <th width="33%" class="text-center">Asset</th>
                        <th width="33%" class="text-center">Liability &amp; Equity</th>
                        <th width="34%" class="text-center">Difference</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center"><?=Utils::format($Assets)?></td>
                        <td class="text-center"><?=Utils::format($Liability + $Equity)?></td>
                        <td class="text-center"><?=Utils::format($Assets - ($Liability + $Equity))?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init("Ending At ".DateEncode($_GET['date_till'], 'd/m/Y')) ?>
<? include_once("../includes/inc_footer.php"); ?>