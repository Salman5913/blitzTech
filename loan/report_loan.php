<?
include_once("../connection/localhost.php");
SecurityShell::redirectIfNoAccess("HR Management",	"Loan");

$oLoan = new Loan();
$Type = $_GET['type'];

$_GET['_page_title'] = $Type == "A" ? "Advance" : "Loan";
$_GET['_page_title_text'] = "View / Edit ".$Type == "A" ? "Advances" : "Loans";

if ($Type == "A")
    $SelectAdvance = $oLoan->getAdvanceReport($_GET['account_id']);
else
    $Select = $oLoan->getReport($_GET['account_id']);

$Advances = [];

$TotalAmount = 0;
$TotalRemaining = 0;

$AdditionalLinksForNewPageOptions = $Type == 'A' ? [["page_url" => "loan/bulk_advances.php?voucher_type_id=44", "page_name" => "Bulk Advance Sheet"]] : [];

include_once("../includes/inc_header.php");
?>
<div class="row">
    <div class="col-md-12">
        <form action="" method="get" <?Utils::formValidate()?>>
            <div class="card">
                <? $oLoan->showVoucherButtonsWithOptions([VoucherType::LV], [], [VoucherType::LV => "&type=".$Type], $AdditionalLinksForNewPageOptions); ?>
                <div class="row filter hidden">
                    <div class="col-md-6">
                        <label for="account_id">Account</label>
                        <input type="text" id="account_id" name="account_id" <?Autocomplete::ACSearchAccount($_GET['account_id']) ?>>
                    </div>
                    <? CostCenter::designReportFilter('col-md-6'); ?>
                    <div class="col-md-12 mt-3">
                        <input type="hidden" name="type" id="type" value="<?=$Type?>">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="fa fa-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
	<div class="col-md-12 <?=$Type == 'L' ? '' : 'hidden'?>">
		<div class="card">
			<h3 class="card-title"> Search Results</h3>
            <div class="table-responsive">
                <table class="table color table-striped data-table-report">
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="check_all" value="Y"> </th>
                        <th>Date</th>
                        <th>Account</th>
                        <th>Amount</th>
                        <th>Installment</th>
                        <th>Remaining</th>
                        <th class="text-right">Voucher Status</th>
                        <th><i class="fa fa-gear"></i> Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <? while ($RowLoan = DBQM::fetch_assoc($Select)) {
                        $TotalAmount += $RowLoan['amount'];
                        $TotalRemaining += ($RowLoan['amount'] - $RowLoan['sum_amt']);

                        $canDelete = $RowLoan['sum_amt'] > 0 ? 'N' : 'Y';

                        $AdditionalLink = array();
                        $AdditionalLink[VoucherType::getLink(VoucherType::CRV).'&loan_id='.$RowLoan['id']] = '<i class="mdi mdi-cash"></i> CRV';
                        $AdditionalLink[VoucherType::getLink(VoucherType::BRV).'&loan_id='.$RowLoan['id']] = '<i class="mdi mdi-bank"></i> BRV';
                        $AdditionalLink['ajax_get_loan_history.php?account_id='.$RowLoan['account_id']] = [
                            "Loan History of ".$RowLoan['emp_name'],
                            '<i class="mdi mdi-history"></i> View Loan History'
                        ];

                        if ($canDelete == 'Y')
                            $AdditionalLink['op_cancel_loan.php?id='.$RowLoan['id']] = '<i class="mdi mdi-cash-remove"></i> Cancel';
                        ?>
                        <tr class="<? $oLoan->highlightDeletedRow($RowLoan); ?>">
                            <td><label><input type="checkbox" name="distinct_id[<?=$RowLoan['distinct_id']?>]" data-js="distinct_id" id="distinct_id" value="<?=$RowLoan['distinct_id']?>"></label></td>
                            <td class="text-center"><?=DateDecode($RowLoan['voucher_date'], 'M d, Y')?></td>
                            <td><?=$RowLoan['emp_name']?></td>
                            <td class="text-center"><?=Utils::format($RowLoan['amount'], 0)?></td>
                            <td class="text-center"><?=Utils::format($RowLoan['monthly_installment'], 0)?></td>
                            <td class="text-center"><?=Utils::format($RowLoan['amount'] - $RowLoan['sum_amt'], 0)?></td>
                            <td class="text-right"><? $oLoan->getVoucherStatus($RowLoan); ?></td>
                            <td class="text-right report-actions">
                                <? $oLoan->getReportButtons(VoucherType::LV, $RowLoan['id'], $AdditionalLink, '')?>
                            </td>
                        </tr>
                    <? } ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-center font-weight-bold"><?=Utils::format($TotalAmount, 0)?></td>
                        <td></td>
                        <td class="text-center font-weight-bold"><?=Utils::format($TotalRemaining, 0)?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
		</div>
	</div>
    <div class="col-md-12 <?=$Type == 'A' ? '' : 'hidden'?>">
        <div class="card">
            <h3 class="card-title"> Search Results</h3>
            <div class="table-responsive">
                <table class="table color table-striped data-table-report">
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="check_all" value="Y"> </th>
                        <th>Date</th>
                        <th>Account</th>
                        <th>Amount</th>
                        <th>Installment</th>
                        <th>Remaining</th>
                        <th class="text-right">Voucher Status</th>
                        <th><i class="fa fa-gear"></i> Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <? while ($Row = DBQM::fetch_assoc($SelectAdvance)) {

                        $TotalAmount += $Row['is_deleted'] == 'Y' ? 0 : $Row['amount'];
                        $TotalRemaining += $Row['is_deleted'] == 'Y' ? 0 : ($Row['amount'] - $Row['sum_amt']);
                        $canDelete = $Row['sum_amt'] > 0 ? 'N' : 'Y';

                        $AdditionalLink = array();
                        $AdditionalLink[VoucherType::getLink(VoucherType::CRV).'&loan_id='.$Row['id']] = '<i class="mdi mdi-cash"></i> CRV';
                        $AdditionalLink[VoucherType::getLink(VoucherType::BRV).'&loan_id='.$Row['id']] = '<i class="mdi mdi-bank"></i> BRV';
                        $AdditionalLink['ajax_get_advance_history.php?account_id='.$Row['account_id']] = [
                            "Loan History of ".$Row['emp_name'],
                            '<i class="mdi mdi-history"></i> View Advance History'
                        ];

                        if ($canDelete == 'Y')
                            $AdditionalLink['op_cancel_loan.php?id='.$Row['id']] = '<i class="mdi mdi-cash-remove"></i> Cancel';
                        ?>
                        <tr class="<? $oLoan->highlightDeletedRow($Row); ?>">
                            <td><label><input type="checkbox" name="distinct_id[<?=$Row['distinct_id']?>]" data-js="distinct_id" id="distinct_id" value="<?=$Row['distinct_id']?>"></label></td>
                            <td class="text-center"><?=DateDecode($Row['voucher_date'], 'M d, Y')?></td>
                            <td><?=$Row['emp_name']?></td>
                            <td class="text-center"><?=Utils::format($Row['amount'], 0)?></td>
                            <td class="text-center"><?=Utils::format($Row['monthly_installment'], 0)?></td>
                            <td class="text-center"><?=Utils::format($Row['amount'] - $Row['sum_amt'], 0)?></td>
                            <td class="text-right"><? $oLoan->getVoucherStatus($Row); ?></td>
                            <td class="text-right report-actions">
                                <? $oLoan->getReportButtons(VoucherType::LV, $Row['id'], $AdditionalLink, '', '&can_delete='.$canDelete)?>
                            </td>
                        </tr>
                    <? } ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-center font-weight-bold"><?=Utils::format($TotalAmount, 0)?></td>
                        <td></td>
                        <td class="text-center font-weight-bold"><?=Utils::format($TotalRemaining, 0)?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php") ?>