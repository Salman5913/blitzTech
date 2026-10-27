<?php
include_once ("../connection/localhost.php");

$oStockReport = new StockReport();
list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_GET['stock_id']);
$Select = $oStockReport->getCustomerWiseProfitBreakup($_GET['date_from'], $_GET['date_till'], $_GET['customer_id'], $StockId, $AttributeId, $_GET['category_id'], $_GET['brand']);

$TotalQty = 0;
$TotalSales = 0;
$TotalCost = 0;
$TotalProfit = 0;
?>

<div class="row">
    <div class="col-sm-12">
        <div>
            <table class="table color table-striped">
                <thead>
                <tr>
                    <th>Voucher Number</th>
                    <th>Voucher Date</th>
                    <th>Item Name</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Selling Price</th>
                    <th class="text-center">Cost Price</th>
                    <th class="text-right bg-soft-warning">Sales</th>
                    <th class="text-right bg-soft-warning">Cost</th>
                    <th class="text-right">Profit</th>
                </tr>
                </thead>
                <tbody>
                <? while($Row = DBQM::fetch_assoc($Select)) {

                    $Sales = $Row['qty'] * $Row['sp'];
                    $Cost = $Row['qty'] * $Row['cost'];
                    $Profit = $Sales - $Cost;

                    $TotalQty += $Row['qty'];
                    $TotalSales += $Sales;
                    $TotalCost += $Cost;
                    $TotalProfit += $Profit;
                    ?>
                    <tr>
                        <td><?=$Row['voucher_name']." - ".$Row['voucher_number']?></td>
                        <td><?=DateDecode($Row['voucher_date'], 'l, M d, Y')?></td>
                        <td><?=$Row['ac_stock']?></td>
                        <td class="text-center"><?=$Row['qty']?></td>
                        <td class="text-center" class="text-center"><?=Utils::format($Row['sp'], 0);;?></td>
                        <td class="text-center"><?=Utils::format($Row['cost'], 0);;?></td>
                        <td class="text-right bg-soft-warning"><?=Utils::format($Sales, 0);;?></td>
                        <td class="text-right bg-soft-warning"><?=Utils::format($Cost, 0);;?></td>
                        <td class="text-right <?=($Profit < 0) ? 'text-danger' : 'text-success'?>"><?=Utils::format($Profit, 0);;?></td>
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
                    <td></td>
                    <td class="text-right text-warning font-weight-bolder"><?=Utils::format($TotalSales, 0);;?></td>
                    <td class="text-right text-warning font-weight-bolder"><?=Utils::format($TotalCost, 0);;?></td>
                    <td class="text-right font-weight-bolder <?=($TotalProfit < 0) ? 'text-danger' : 'text-success'?>">
                        <?=Utils::format($TotalProfit, 0);?><br>
                        <small><?=Utils::format((1 - ($TotalCost / $TotalSales)) * 100, 1)?>%</small>
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
