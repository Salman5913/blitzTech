<?
include_once("../connection/localhost.php");

$oExpendedReport = new ExpendedReport($_GET['coa_id']);

$_GET['_page_title'] = ChartOfAccountCategory::getNameById($_GET['coa_id'])." - Expended Report".AccountingReport::costCenterHeading();
$_GET['_page_title_text'] = $oExpendedReport->getBreadcrumb();

include_once("../includes/inc_header.php");

$oExpendedReport->setDate($_GET['date_from'], $_GET['date_till']);

$SelectCategories = $oExpendedReport->getAllChildCategories();
$SelectAccounts = $oExpendedReport->getAllAccounts();

$Balance = 0;
?>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="card-title">Expended Report</h3>
                </div>
                <div class="col-auto ml-auto">
                    <? UtilsPrint::button() ?>
                </div>
            </div>
            <table class="table color table-striped <? UtilsPrint::makePrintable() ?>">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?
                    while ($Row = DBQM::fetch_assoc($SelectCategories)) {
                        $Balance += $Row['balance'];
                        ?>
                        <tr>
                            <td><i class="mdi mdi-file-tree"></i> <a href="expanded_report.php?date_from=<?=$_GET['date_from']?>&date_till=<?=$_GET['date_till']?>&coa_id=<?=$Row['id']?><?=AccountingReport::costCenterUrlParam()?>"><?=$Row['name']?></a></td>
                            <td class="text-right"><?=Utils::format($Row['balance'])?></td>
                        </tr>
                    <? } ?>
                    <?
                    while ($Row = DBQM::fetch_assoc($SelectAccounts)) {
	                    $Balance += $Row['balance'];
	                    ?>
                        <tr>
                            <td><i class="mdi mdi-dots-horizontal"></i> <a href="ledger.php?date_from=<?=$_GET['date_from']?>&date_till=<?=$_GET['date_till']?>&account_id=<?=$Row['id']?><?=AccountingReport::costCenterUrlParam()?>&search="><?=$Row['name']?></a></td>
                            <td class="text-right"><?=Utils::format($Row['balance'])?></td>
                        </tr>
                    <? } ?>
                </tbody>
                <tfoot>
                    <tr class="font-weight-bolder">
                        <td>&nbsp;</td>
                        <td class="text-right"><?=Utils::format($Balance)?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init("From ".DateEncode($_GET['date_till'], 'M d, Y')." - ".DateEncode($_GET['date_till'], 'M d, Y').AccountingReport::costCenterHeading()) ?>
<? include_once("../includes/inc_footer.php"); ?>