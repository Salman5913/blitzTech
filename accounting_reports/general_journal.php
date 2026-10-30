<?
$_GET['_page_title'] = "General Journal";
$_GET['_page_title_text'] = "View Ledger Entries of Specified Duration";

include_once("../includes/inc_header.php");

$oGeneralJournal = new GeneralJournal();
$oPaging = new Pagination();

if (isset($_GET['search']))
{
    $DateFrom = $_GET['date_from'];
    $DateTill = $_GET['date_till'];

    $oGeneralJournal->setDate($DateFrom, $DateTill);

    $Select = $oPaging->makeQuery($oGeneralJournal->getReport(), $_GET['page_no']);
}
?>
<form action="" method="get">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Filter Options</h3>
                <div class="row justify-content-lg-start">
                    <div class="col-xl-5 col-md-6">
                        <div class="form-group">
                            <label for="date">Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] =='' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] =='' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-xl-3 col-md-6'); ?>
                    <div class="col-md-12">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-sm-12">
        <?=$oPaging->makePagingSummary()?>
        <? while($Row = DBQM::fetch_assoc($Select)) { ?>
            <div class="card">
                <h3 class="card-title"> <?=DateDecode($Row['date_time'], 'M d, Y')?></h3>
                <table class="table table-striped">
                    <?
                    $i = $TotalDebit = $TotalCredit = 0;
                    $SelectDetails = $oGeneralJournal->getLedgerDetailsByLedgerId($Row['id']);
                    while ($RowDetails = DBQM::fetch_assoc($SelectDetails)) { $VoucherCode = !empty($Row['voucher_code']) ? $Row['voucher_code'] : $Row['default_voucher_code'];
                    ?>
                    <tr>
                        <td width="10%" class="text-info" title="<?=$Row['voucher_name']?>"><?=$i==0 ? $VoucherCode." - ".$Row['voucher_number'] : '&nbsp;'?></td>
                        <td width="30%"><a target="_blank" href="<?=LedgerReport::getLedgerLink($RowDetails['account_id'])?>"> <?=$RowDetails['account_name']?></a></td>
                        <td width="10%" class="text-right"><?=Utils::format($RowDetails['debit']); $TotalDebit += $RowDetails['debit'];?></td>
                        <td width="10%" class="text-right"><?=Utils::format($RowDetails['credit']); $TotalCredit += $RowDetails['credit'];?></td>
                        <td width="40%"><?=$RowDetails['description']?></td>
                    </tr>
                    <? $i++; } ?>

                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td class="text-right font-weight-bold text-info"><?=Utils::format($TotalDebit)?></td>
                        <td class="text-right font-weight-bold text-info"><?=Utils::format($TotalCredit)?></td>
                        <td></td>
                    </tr>
                </table>
            </div>
        <? } ?>
        <?=$oPaging->makePaging()?>
    </div>
</div>

<? include_once("../includes/inc_footer.php"); ?>