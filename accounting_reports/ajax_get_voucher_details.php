<?php
include_once("../connection/localhost.php");

$LedgerId = $_GET['id'];

$oLedgerReport = new LedgerReport();
$SelectDetails = $oLedgerReport->getVoucherItemsDetail($LedgerId);

?>
<table class="table color table-striped table-bordered">
    <thead>
        <tr>
            <th>Account Name</th>
            <th class="text-right">Debit</th>
            <th class="text-right">Credit</th>
            <th class="text-right">Rate</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <? while($Row = DBQM::fetch_assoc($SelectDetails)) { ?>
        <tr>
            <td><?=$Row['name']?></td>
            <td class="text-right"><?=Utils::format($Row['debit'])?></td>
            <td class="text-right"><?=Utils::format($Row['credit'])?></td>
            <td class="text-right"><?=round($Row['currency_rate'], 3)?></td>
            <td><?=$Row['description']?></td>
        </tr>
        <? } ?>
    </tbody>
</table>