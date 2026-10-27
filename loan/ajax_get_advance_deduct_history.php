<?php
include_once("../connection/localhost.php");

$SalaryDataId = $_GET['salary_data_id'];

$oLoan = new Loan();
$SelectAdvanceDeductionHistory = $oLoan->getAdvanceDeductionHistory($SalaryDataId);

$TotalAdvanceDeduction = 0;
?>
<div class="col-sm-12">
    <h3>Loan Deduction</h3>
    <table class="table color table-striped">
        <thead>
        <tr>
            <th>Advance</th>
            <th>Deduction</th>
        </tr>
        </thead>
        <tbody>
        <? while($Row = DBQM::fetch_assoc($SelectAdvanceDeductionHistory)) { ?>
            <tr>
                <td><?=Utils::format($Row['amount'], 0)?></td>
                <td><?=Utils::format($Row['advance_deduction']); $TotalAdvanceDeduction += $Row['advance_deduction'];?></td>
            </tr>
        <? } ?>
        </tbody>
    </table>
</div>
<div class="clearfix"></div>
<h3 class="text-center">Total Loan Deduction: <?=Utils::format($TotalAdvanceDeduction, 0);?></h3>