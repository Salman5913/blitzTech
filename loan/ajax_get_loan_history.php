<?php
include_once("../connection/localhost.php");

$AccountId = $_GET['account_id'];
$oLoan = new Loan();
list($SelectLoanTaken, $SelectLoanPayBack) = $oLoan->getLoanHistory($AccountId);

$LoanRemaining = 0;
?>
<div class="col-md-12">
    <h3>Loan Taken</h3>
    <table class="table color table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Monthly Installment</th>
                <th>Remaining</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            <? while($Row = DBQM::fetch_assoc($SelectLoanTaken)) { ?>
                <tr class="<?=$Row['is_deleted'] == 'Y' || $Row['is_cancelled,'] == 'Y' ? 'bg-soft-danger' : ''?>">
                    <td><?=DateEncode($Row['date'], 'M d, Y')?></td>
                    <td><?=Utils::format($Row['amount']); $LoanRemaining += $Row['amount'];?></td>
                    <td><?=Utils::format($Row['monthly_installment'])?></td>
                    <td><?=Utils::format($Row['amount'] - ($Row['loan_paid'] + $Row['voucher_paid']))?></td>
                    <td>Loan</td>
                </tr>
            <? } ?>
        </tbody>
    </table>
</div>
<div class="col-md-12">
    <h3>Loan Payback</h3>
    <table class="table color table-striped">
        <thead>
        <tr>
            <th>Date</th>
            <th>Loan</th>
            <th>Deduction</th>
            <th><i class="fa fa-gear"></i> Actions</th>
        </tr>
        </thead>
        <tbody>
        <? while($Row = DBQM::fetch_assoc($SelectLoanPayBack)) { ?>
            <tr class="<?=$Row['is_deleted'] == 'Y' || $Row['is_cancelled,'] == 'Y' ? 'bg-soft-danger' : ''?>">
                <td><?=DateEncode($Row['date_time'], 'M d, Y')?></td>
                <td><?=Utils::format($Row['amount'], 0)?></td>
                <td><?=Utils::format($Row['loan_deduction']); $LoanRemaining -= ($Row['loan_deduction']);?></td>
                <td class="text-right">
                    <a href="javascript:void(0)" <? Utils::AjaxLoadedPopup("Loan Deduction History of ".ChartOfAccount::getName($AccountId), 'ajax_get_loan_deduct_history.php?loan_id='.$Row['loan_id']) ?>><i class="fa fa-history"></i> View Loan Deduction History</a>
                </td>
            </tr>
        <? } ?>
        </tbody>
    </table>
</div>
<div class="clearfix"></div>
<h3 class="text-center">Remaining Loan: <?=Utils::format($LoanRemaining, 0);?></h3>