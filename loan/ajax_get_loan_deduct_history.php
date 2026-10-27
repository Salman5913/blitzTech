<?php
include_once("../connection/localhost.php");

$LoanId = $_GET['loan_id'];

$oLoan = new Loan();
$SelectLoanDeductionHistory = $oLoan->getLoanDeductionHistory($LoanId);

$TotalLoanDeduction = 0;
?>
<div class="col-sm-12">
    <h3>Loan Deduction</h3>
    <table class="table color table-striped">
        <thead>
        <tr>
            <th>Voucher</th>
            <th>Loan</th>
            <th>Deduction</th>
        </tr>
        </thead>
        <tbody>
        <? while($Row = DBQM::fetch_assoc($SelectLoanDeductionHistory)) { ?>
            <tr class="<?=$Row['is_deleted'] == 'Y' ? 'bg-soft-danger' : ''?>" >
                <td><?=$Row['name']?>-<?=$Row['voucher_number']?></td>
                <td><?=Utils::format($Row['amount'], 0)?></td>
                <td><?=Utils::format($Row['loan_deduction']); $TotalLoanDeduction += $Row['loan_deduction'];?></td>
            </tr>
        <? } ?>
        </tbody>
    </table>
</div>
<div class="clearfix"></div>
<h3 class="text-center">Total Loan Deduction: <?=Utils::format($TotalLoanDeduction, 0);?></h3>