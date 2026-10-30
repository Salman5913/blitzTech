<?
$_GET['_page_title'] = ucwords($_GET['type'])." Aging Report";
$_GET['_page_title_text'] = "View Aging of an Account";

include_once("../includes/inc_header.php");

$oLedgerReport = new LedgerReport();

if (isset($_GET['search']))
    $SelectAccounts = $oLedgerReport->getAllAccountsByCOAId($_GET['coa_id'], $_GET['account_id']);
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
                            <input type="text" id="coa_id" name="coa_id" <? Autocomplete::ACSearchCOA($_GET['coa_id']); ?>>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="account_id">Account Name</label>
                            <input type="text" id="account_id" name="account_id" <? Autocomplete::ACSearchAccount($_GET['account_id']); ?>>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-xl-3 col-md-6'); ?>
                    <div class="col-xl-2 text-right">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn button color form-control" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="type" value="<?=$_GET['type']?>">
</form>
<div class="report_complete">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="row">
                    <div class="col-sm-6"><h3 class="card-title"> Search Results</h3></div>
                    <div class="col-sm-6 text-right"><? UtilsPrint::button(); ?></div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-rep-plugin">
                            <div class="table-responsive mb-0" data-pattern="priority-columns">
                                <table id="tech-companies-1" class="table table-striped color <?UtilsPrint::makePrintable()?>">
                                    <thead>
                                    <tr>
                                        <th >Account Name</th>
                                        <th data-priority="3">0-7</th>
                                        <th data-priority="1">8-14</th>
                                        <th data-priority="3">15-21</th>
                                        <th data-priority="6">22-28</th>
                                        <th data-priority="6">28-45</th>
                                        <th data-priority="6">45-60</th>
                                        <th data-priority="6">60-90</th>
                                        <th data-priority="3">90+ Days</th>
                                        <th data-priority="1" class="text-right">Balance</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <? while($RowAccounts = DBQM::fetch_assoc($SelectAccounts)) {
                                    $oAging = new Aging($RowAccounts['id'], $_GET['type']);

                                    if (!$oAging->hasAgingData()) continue;
                                    ?>
                                    <tr>
                                        <td><a href="ledger.php?date_from=&date_till=&account_id=<?=$RowAccounts['id']?><?=AccountingReport::costCenterUrlParam()?>&search=" target="_blank"><?=ChartOfAccount::getName($RowAccounts['id'])?></a></td>
                                        <td><?=$oAging->getAgingValue(0, 7) ?></td>
                                        <td><?=$oAging->getAgingValue(8, 14) ?></td>
                                        <td><?=$oAging->getAgingValue(15, 21)?></td>
                                        <td><?=$oAging->getAgingValue(22, 28) ?></td>
                                        <td><?=$oAging->getAgingValue(29, 45) ?></td>
                                        <td><?=$oAging->getAgingValue(46, 60) ?></td>
                                        <td><?=$oAging->getAgingValue(61, 90) ?></td>
                                        <td><?=$oAging->getAgingValue(91, 8000) ?></td>
                                        <td class="font-weight-bolder text-right"><?=Utils::format(Aging::getTotalValue(), 0)?></td>
                                    </tr>
                                    <? Aging::setVariableZero(); } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<? UtilsPrint::init(DateEncode(DatabaseHelper::NOW(), 'd/m/Y').AccountingReport::costCenterHeading()) ?>
<? include_once("../includes/inc_footer.php"); ?>