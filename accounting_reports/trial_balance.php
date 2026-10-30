<?
$_GET['_page_title'] = "Trial Balance";
$_GET['_page_title_text'] = "Trial Balance";

include_once("../includes/inc_header.php");

$oTrialBalance = new TrialBalance();
$Level1Accounts = array();

$TotalOpeningFull = $TotalDebitFull = $TotalCreditFull = $TotalClosingFull = 0;

if (isset($_GET['search']))
{
    $DateFrom = $_GET['date_from'];
    $DateTill = $_GET['date_till'];

    $oTrialBalance->setDate($DateFrom, $DateTill);

    $Level1Accounts = $oTrialBalance->getAllLevel1();
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
                    <div class="col-xl-2 col-md-3">
                        <div class="form-group">
                            <label for="is_groupwise">Grouped?</label>
                            <select name="is_groupwise" id="is_groupwise"><? Utils::getDropDownOptionsFromArray(['N' => 'No', 'Y' => 'Yes'], $_GET['is_groupwise']); ?></select>
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
                <div class="col-md-6">
                    <h3 class="card-title"><i class="fa fa-bar-chart"></i> Report</h3>
                </div>
                <div class="col-auto ml-auto">
                    <? UtilsPrint::button() ?>
                </div>
            </div>
            <table class="table table-striped color font-size-14 <? UtilsPrint::makePrintable() ?>">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="text-right">Opening</th>
                        <th class="text-right">Total Debit</th>
                        <th class="text-right">Total Credit</th>
                        <th class="text-right">Closing</th>
                    </tr>
                </thead>
                <?
                foreach($Level1Accounts as $Level1Code => $Level1)
                {
                    $oTrialBalance->prepareDataNMakeRows($Level1Code, $Level1, $_GET['is_groupwise'] == 'Y');
                }
                ?>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <h3 class="card-title"><i class="fa fa-area-chart"></i> Total</h3>
            <table class="table color">
                <thead>
                    <tr>
                        <th class="text-center">Total Opening</th>
                        <th class="text-center">Total Debit</th>
                        <th class="text-center">Total Credit</th>
                        <th class="text-center">Total Closing</th>
                    </tr>
                </thead>
                <tr class="font-weight-bold text-dark">
                    <td class="text-center"><?=Utils::format($oTrialBalance->TotalOpening)?></td>
                    <td class="text-center"><?=Utils::format($oTrialBalance->TotalDebit)?></td>
                    <td class="text-center"><?=Utils::format($oTrialBalance->TotalCredit)?></td>
                    <td class="text-center"><?=Utils::format($oTrialBalance->TotalClosing)?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init(DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y').AccountingReport::costCenterHeading()) ?>
<? include_once("../includes/inc_footer.php"); ?>