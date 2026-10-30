<?
include_once("../connection/localhost.php");

$Heading = ChartOfAccount::getName($_GET['account_id'])." - Ledger".AccountingReport::costCenterHeading();

$_GET['_page_title'] = $Heading;
$_GET['_page_title_text'] = $Heading;

include_once("../includes/inc_header.php");

$oLedgerReport = new LedgerReport();
$Balance = 0;
$PostChequesBalance = 0;
$URL = Utils::GETtoURLParams("account_id");

if (isset($_GET['search']))
{
    $DateFrom = $_GET['date_from'];
    $DateTill = $_GET['date_till'];

    $oLedgerReport->setDate($DateFrom, $DateTill);

    $AccountId = $_GET['account_id'];

    $Select = $oLedgerReport->getReport($_GET['account_id'], [], $_GET['highlight_reconciled'] == 'Y');

    $Balance = $PostChequesBalance = 0;
    if (!empty($DateFrom))
    {
        $Balance = LedgerReport::getAccountBalance($_GET['account_id'], DateEncode($DateFrom), 'from', true);
        $PostChequesBalance = $oLedgerReport->getPostChequesAmount($_GET['account_id']);
    }
}
?>
<form action="" method="get" <? Utils::formValidate() ?> >
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group mb-0">
                            <label for="date_from">Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] =='' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] =='' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="account_id">Account Name</label>
                            <input type="text" id="account_id" name="account_id" <? Autocomplete::ACSearchAccount($_GET['account_id']); ?> required>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <div class="form-group">
                            <label for="highlight_reconciled">Highlight Reconciled?</label>
                            <select id="highlight_reconciled" name="highlight_reconciled"><? Utils::getDropDownOptionsFromArray(['N' => 'No', 'Y' => 'Yes'], $_GET['highlight_reconciled']); ?></select>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-xl-2 col-md-6'); ?>
                    <div class="col-xl-2 col-md-6 text-right">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn button color form-control" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<form action="" method="get">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="row">
                    <div class="col-auto mr-auto">
                        <h3 class="card-title"> Ledger Report</h3>
                    </div>
                    <div class="col-auto ml-auto">
                        <? UtilsPrint::button() ?>
                    </div>
                </div>
                <table class="table color table-striped <? UtilsPrint::makePrintable() ?> ledger-table">
                    <thead>
                        <tr>
                            <th class="print-hidden"><input type="checkbox" id="checkAll"></th>
                            <th>Date</th>
                            <th>Voucher</th>
                            <th>Description</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Balance</th>
                            <th class="print-hidden"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td width="5%" class="print-hidden">&nbsp;</td>
                            <td width="13%">-</td>
                            <td width="10%">-</td>
                            <td width="35%">Balance B/D</td>
                            <td width="9%" class="text-right">-</td>
                            <td width="9%" class="text-right">-</td>
                            <td width="9%" class="text-right"><?=Utils::format($Balance, 0);?></td>
                            <td width="10%" class="print-hidden">&nbsp;</td>
                        </tr>
                    <?
                    while ($Row = DBQM::fetch_assoc($Select)) {
                        $Balance += $Row['debit'] - $Row['credit'];

                        $Decimal = 0;
                        ?>
                        <tr class="<?=!empty($Row['reconciled_id']) ? 'bg-soft-success' : '' ?>">
                            <td class="print-hidden"><input type="checkbox" name="ledger_data_id[]" value="<?=$Row['ledger_data_id']?>"></td>
                            <td><?=DateDecode($Row['date_time'], 'M d, Y')?></td>
                            <td title="<?=$Row['voucher_name']?>">
                                <a href="javascript:void(0)" <? Utils::AjaxLoadedPopup("Voucher Detail of ".$Row['voucher_code']."-".$Row['voucher_number'], 'ajax_get_voucher_details.php?id='.$Row['ledger_id'])?>><?=$Row['voucher_code']."-".$Row['voucher_number']?></a>
                            </td>
                            <td><?=empty($Row['counter_name']) ? $Row['description'] : ("<span class='print-hidden'><a href='ledger.php?".Utils::GETtoURLParams("account_id")."&account_id=".$Row['counter_account_id']."' target='_blank'>".$Row['counter_name']." - </a></span>".$Row['description'])?></td>
                            <td class="text-right" data-js="debit"><?=Utils::format($Row['debit'], $Decimal);?></td>
                            <td class="text-right" data-js="credit"><?=Utils::format($Row['credit'], $Decimal);?></td>
                            <td class="text-right"><?=Utils::format($Balance, $Decimal);?></td>
                            <td class="text-right print-hidden"><a href="<?=VoucherType::getLink($Row['voucher_type_id'], $Row['voucher_id'])?>" target="_blank">[Edit]</a></td>
                        </tr>
                    <? } ?>
                    <tr>
                        <td class="print-hidden">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="text-right font-weight-bold text-dark" <? Utils::sumReportColumn('[data-js="debit"]')?>></td>
                        <td class="text-right font-weight-bold text-dark" <? Utils::sumReportColumn('[data-js="credit"]')?>></td>
                        <td class="text-right font-weight-bold">&nbsp;</td>
                        <td class="print-hidden">&nbsp;</td>
                    </tr>
                    <? if (!empty($PostChequesBalance)) { ?>
                        <tr class="text-primary">
                            <td class="print-hidden"></td>
                            <td class="text-right font-weight-bold" colspan="5">Post Dated Cheques</td>
                            <td class="text-right font-weight-bold"><?=Utils::format($PostChequesBalance, 0);?></td>
                            <td class="print-hidden"></td>
                        </tr>
                    <? } ?>
                    <tr class="text-success">
                        <td class="print-hidden"></td>
                        <td class="text-right font-weight-bold display-6" colspan="5">Final Balance</td>
                        <td class="text-right font-weight-bold display-6"><?=Utils::format($Balance + $PostChequesBalance, 0);?></td>
                        <td class="print-hidden"></td>
                    </tr>
                    </tbody>
                </table>

                <input type="hidden" name="account_id" value="<?=$_GET['account_id']?>">
                <div class="row">
                    <div class="col-md-3">

                    </div>
                    <div class="offset-md-3 col-md-3">
                        <? if ($_GET['highlight_reconciled'] == 'Y') { ?>
                            <button formaction="op_reconcile_ledger_data.php" class="btn btn-warning w-100" onclick="return confirm('Are you sure you want to mark these entries as reconciled?');"><i class="mdi mdi-check-all"></i> Mark as Reconciled</button>
                        <? } ?>
                    </div>
                    <div class="col-md-3">
                        <? if ($_GET['highlight_reconciled'] == 'Y') { ?>
                            <button formaction="op_reconcile_cancel_ledger_data.php" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to cancel reconciliation?');"><i class="mdi mdi-cancel"></i> Cancel Reconciled</button>
                        <? } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
$(function () {
    $("#checkAll").click(function(){
        $('.ledger-table input:checkbox').not(this).prop('checked', this.checked);
    });
})
</script>
<? UtilsPrint::init(DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y').AccountingReport::costCenterHeading(), false, false) ?>
<? include_once("../includes/inc_footer.php"); ?>