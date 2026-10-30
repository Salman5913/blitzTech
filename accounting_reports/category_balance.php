<?
include_once("../connection/localhost.php");

$oCategoryBalanceReport = new CategoryBalanceReport();
$Balance               = 0;
$oLedgerReport = new LedgerReport();
$PostDatedCheques = 0;
$TotalDebit = 0;
$TotalCredit = 0;
$AccountType = '';

$Heading = "Current Balances";
if (isset($_GET['search']))
{
    $DateTill = $_GET['date_till'];

    $oCategoryBalanceReport->setDate('', $DateTill);

    $Select = $oCategoryBalanceReport->getReport($_GET['coa_id']);

    $Heading = ChartOfAccountCategory::getNameById($_GET['coa_id'])." - Current Balances".AccountingReport::costCenterHeading();

	$oLedgerReport->setDate('', $DateTill);
}

$_GET['_page_title'] = $Heading;
$_GET['_page_title_text'] = "View Ledger Balances of a Category";

include_once("../includes/inc_header.php");
?>
<form action="" method="get" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="coa_id">COA Category</label>
                            <input type="text" id="coa_id" name="coa_id" <? Autocomplete::ACSearchCOA($_GET['coa_id']); ?> required>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="date_till">As at Date</label>
                            <input type="text" class="form-control" name="date_till" id="date_till" data-type="date" value="<?=$_GET['date_till'] == '' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-xl-3 col-md-6'); ?>
                    <div class="col-xl-2 col-md-6">
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
                <div class="col-md-6"><h3 class="card-title text-uppercase">Current Balance Report</h3></div>
                <div class="col-auto ml-auto"><? UtilsPrint::button() ?></div>
            </div>
            <table class="table table-striped data-table-report color <? UtilsPrint::makePrintable() ?>">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Post Dated Cheque</th>
                    </tr>
                </thead>
                <tbody>
                    <?
                    while ($Row = DBQM::fetch_assoc($Select)) {
                        $Balance += $Row['balance'];

                        if ($Row['balance'] == 0) continue;

                        $TotalCredit += $Row['balance'] < 0 ? $Row['balance'] : 0;
                        $TotalDebit += $Row['balance'] > 0 ? $Row['balance'] : 0;

	                    $PostChequesBalance = $oLedgerReport->getPostChequesAmount($Row['id']);
	                    $PostDatedCheques += $PostChequesBalance;
                    ?>
                    <tr>
                        <td><a href="<?=LedgerReport::getLedgerLink($Row['id'])?>" target="_blank"><?=$Row['name']?></a></td>
                        <td class="text-right" data-js="debit"><?=Utils::format($Row['balance'] > 0 ? $Row['balance'] : 0);?></td>
                        <td class="text-right" data-js="credit"><?=Utils::format($Row['balance'] < 0 ? abs($Row['balance']) : 0);?></td>
                        <td class="text-right" data-js="pdc"><?=Utils::format($PostChequesBalance)?></td>
                    </tr>
                    <? } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td>&nbsp;</td>
                        <td class="text-right font-weight-bold display-6" <?/* Utils::sumReportColumn('[data-js="debit"]'); */?>><?=Utils::format($TotalDebit)?></td>
                        <td class="text-right font-weight-bold display-6" <?/* Utils::sumReportColumn('[data-js="credit"]'); */?>><?=Utils::format($TotalCredit)?></td>
                        <td class="text-right font-weight-bold display-6" <? Utils::sumReportColumn('[data-js="pdc"]'); ?>></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init("As on - ".DateEncode($_GET['date_till'], 'M d, Y').AccountingReport::costCenterHeading()) ?>
<? include_once("../includes/inc_footer.php"); ?>