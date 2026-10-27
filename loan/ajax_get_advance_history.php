<?php
include_once("../connection/localhost.php");

$AccountId = $_GET['account_id'];

$oLoan = new Loan();
list($SelectAdvanceTaken, $SelectAdvancePayBack) = $oLoan->getAdvanceHistory($AccountId);

$AdvanceRemaining = 0;
?>
<div class="col-sm-12">
    <h3>Remaining Advance</h3>
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
            <? while($Row = DBQM::fetch_assoc($SelectAdvanceTaken)) { if ($Row['amount'] - $Row['advance_paid'] == 0) continue; ?>
                <tr class="<?=$Row['is_deleted'] == 'Y' || $Row['is_cancelled,'] == 'Y' ? 'bg-soft-danger' : ''?>">
                    <td><?=DateEncode($Row['date'], 'M d, Y')?></td>
                    <td><?=Utils::format($Row['amount']); $AdvanceRemaining += $Row['amount'];?></td>
                    <td><?=Utils::format($Row['monthly_installment'])?></td>
                    <td><?=Utils::format($Row['amount'] - $Row['advance_paid'])?></td>
                    <td>Monthly Advance</td>
                </tr>
            <? } ?>
        </tbody>
    </table>
</div>
<div class="clearfix"></div>
<h3 class="text-center">Remaining Advance: <?=Utils::format($AdvanceRemaining, 0);?></h3>
<div class="col-sm-6 hidden">
    <h3>Advance Payback</h3>
    <table class="table color table-striped">
        <thead>
        <tr>
            <th>Date</th>
            <th>Advance</th>
            <th>Deduction</th>
        </tr>
        </thead>
        <tbody>
        <? while($Row = DBQM::fetch_assoc($SelectAdvancePayBack)) { ?>
            <tr class="<?=$Row['is_deleted'] == 'Y' || $Row['is_cancelled,'] == 'Y' ? 'bg-soft-danger' : ''?>">
                <td><?=DateEncode($Row['date_time'], 'M d, Y')?></td>
                <td><?=Utils::format($Row['amount'], 0);?></td>
                <td><?=Utils::format($Row['advance_deduction']); $AdvanceRemaining -= $Row['advance_deduction'];?></td>
            </tr>
        <? } ?>
        </tbody>
    </table>
</div>