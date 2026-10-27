<?php
include_once ("../connection/localhost.php");

$oStockReport = new StockReport();
$Select = $oStockReport->getBatchWiseStock($_GET['stock_id']);

$TotalQty = 0;
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <table class="table color table-striped">
                <thead>
                <tr>
                    <th>Voucher Number</th>
                    <th>Voucher Date</th>
                    <th>Batch Number</th>
                    <th class="text-center">Available Qty</th>
                    <th>Batch Cost</th>
                </tr>
                </thead>
                <tbody>
                <? while($Row = DBQM::fetch_assoc($Select)) { $TotalQty += $Row['sum_qty']; ?>
                    <tr>
                        <td>PIV-<?=$Row['invoice_number']?></td>
                        <td><?=DateDecode($Row['voucher_date'], 'l, M d, Y')?></td>
                        <td><?=$Row['batch_number']?></td>
                        <td class="text-center"><?=Utils::format($Row['sum_qty'], 0);;?></td>
                        <td><?=Utils::format($Row['cost'], 0);;?></td>
                    </tr>
                <? } ?>
                </tbody>
                <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-center font-weight-bolder text-primary font-size-14"><?=Utils::format($TotalQty, 0)?></td>
                    <td></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
