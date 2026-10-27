 <?
 $_GET['_page_title'] = "Cash Flow";
 $_GET['_page_title_text'] = "View Cash Flow of All Cash & Bank Accounts";

include_once("../includes/inc_header.php");

$oLedgerReport = new LedgerReport();
$Balance = $TotalDebit = $TotalCredit = 0;
$URL = Utils::GETtoURLParams("month_year");

if (isset($_GET['search']))
{
    if (isset($_GET['month_year']))
    {
        $_GET['date_from'] = date("m/01/Y", strtotime($_GET['month_year']));
	    $_GET['date_till'] = date("m/t/Y", strtotime($_GET['month_year']));
    }

    $DateFrom = $_GET['date_from'];
    $DateTill = $_GET['date_till'];

    $oLedgerReport->setDate($DateFrom, $DateTill);

    $COAId = [getConstantCategory()->getBank(), getConstantCategory()->getCashInHand()];

    $Select = $oLedgerReport->getReport('', $COAId);
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
                <div class="col-auto mr-auto">
                    <h3 class="card-title"> Cash Flow Report</h3>
                </div>
                <div class="col-auto ml-auto">
                    <? UtilsPrint::button() ?>
                </div>
            </div>
            <table class="table color table-striped <? UtilsPrint::makePrintable() ?>">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Voucher</th>
                    <th>Description</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Balance</th>
                    <th width="5%" class="print-hidden"></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td width="10%">-</td>
                    <td width="5%">-</td>
                    <td width="43%">Balance B/D</td>
                    <td width="9%" class="text-right">-</td>
                    <td width="9%" class="text-right">-</td>
                    <td width="9%" class="text-right"><?=Utils::format($Balance);?></td>
                    <td class="print-hidden">&nbsp;</td>
                </tr>
                <?
                while ($Row = DBQM::fetch_assoc($Select)) {
                    $Balance += $Row['debit'] - $Row['credit'];
                    ?>
                    <tr>
                        <td><?=DateDecode($Row['date_time'], 'M d, Y')?></td>
                        <td title="<?=$Row['voucher_name']?>">
                            <a href="javascript:void(0)" <? Utils::AjaxLoadedPopup("Voucher Detail of ".$Row['voucher_code']."-".$Row['voucher_number'], 'ajax_get_voucher_details.php?id='.$Row['ledger_id'])?>><?=$Row['voucher_code']."-".$Row['voucher_number']?></a>
                        </td>
                        <td><?=empty($Row['counter_name']) ? $Row['description'] : ("<span class='print-hidden'><a href='ledger.php?".Utils::GETtoURLParams("account_name")."&account_id=".$Row['counter_account_id']."' target='_blank'>".$Row['counter_name']." - </a></span>".$Row['description'])?></td>
                        <td class="text-right" data-js="debit"><?=Utils::format($Row['debit']);?></td>
                        <td class="text-right" data-js="credit"><?=Utils::format($Row['credit']);?></td>
                        <td class="text-right"><?=Utils::format($Balance);?></td>
                        <td class="text-right print-hidden"><a href="<?=VoucherType::getLink($Row['voucher_type_id'], $Row['voucher_id'])?>" target="_blank">[Edit]</a></td>
                    </tr>
                <? } ?>
                </tbody>
                <tfoot>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="text-right font-weight-bold text-dark" <? Utils::sumReportColumn('[data-js="debit"]')?>></td>
                    <td class="text-right font-weight-bold text-dark" <? Utils::sumReportColumn('[data-js="credit"]')?>></td>
                    <td class="text-right font-weight-bold">&nbsp;</td>
                    <td class="print-hidden">&nbsp;</td>
                </tr>
                <tr class="<?=$Balance >= 0 ? 'text-success' : 'text-danger'?>">
                    <td class="text-right font-weight-bold display-6" colspan="5">Difference Is</td>
                    <td class="text-right font-weight-bold display-6"><?=Utils::format($Balance);?></td>
                    <td class="print-hidden"></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<? UtilsPrint::init(DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y')) ?>
<? include_once("../includes/inc_footer.php"); ?>