<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Deals Report';
$_GET['_page_title_text'] = 'How the website deals are selling';

include_once("../includes/inc_header.php");

$ActiveTab = 'deals_report';
include_once(__DIR__ . '/_tabs.php');

function h($Value) { return htmlspecialchars((string)$Value, ENT_QUOTES, 'UTF-8'); }

$Deals = [];
$SelectDeals = RestaurantDeal::getDeals();
while ($Row = DBQM::fetch_assoc($SelectDeals)) $Deals[] = $Row;

$TotalOrders = 0; $TotalUnits = 0; $TotalRevenue = 0;
?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Deal</th>
                    <th>Dishes</th>
                    <th class="text-right">Normal</th>
                    <th class="text-right">Deal price</th>
                    <th class="text-right">Customer saves</th>
                    <th>Website</th>
                    <th class="text-right">Orders</th>
                    <th class="text-right">Units sold</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-center"><i class="mdi mdi-eye"></i></th>
                </tr>
            </thead>
            <tbody>
                <? foreach ($Deals as $Deal) {
                    $Units = $Deal['item_count'] > 0 ? (int)round($Deal['lines_qty'] / $Deal['item_count']) : 0;
                    $Saves = (float)$Deal['normal_price'] - (float)$Deal['price'];
                    $TotalOrders += $Deal['order_count']; $TotalUnits += $Units; $TotalRevenue += $Deal['revenue'];
                ?>
                    <tr>
                        <td><b><?=h($Deal['name'])?></b></td>
                        <td><small><?=(int)$Deal['item_count']?> dishes</small></td>
                        <td class="text-right"><?=number_format($Deal['normal_price'])?></td>
                        <td class="text-right"><?=number_format($Deal['price'])?></td>
                        <td class="text-right"><?=$Saves > 0 ? number_format($Saves) : '—'?></td>
                        <td>
                            <? if ($Deal['show_on_website']) { ?><span class="badge badge-soft-success">Shown</span><? } else { ?><span class="badge badge-soft-secondary">Hidden</span><? } ?>
                        </td>
                        <td class="text-right"><?=(int)$Deal['order_count']?></td>
                        <td class="text-right"><?=$Units?></td>
                        <td class="text-right"><?=number_format($Deal['revenue'])?></td>
                        <td class="text-center">
                            <a href="deal_view.php?id=<?=$Deal['id']?>" class="btn btn-sm btn-light" title="View deal"><i class="mdi mdi-eye"></i></a>
                        </td>
                    </tr>
                <? } ?>
                <? if (!$Deals) { ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No deals yet. Create one under the Deals tab.</td></tr>
                <? } else { ?>
                    <tr>
                        <th colspan="6">Total</th>
                        <th class="text-right"><?=$TotalOrders?></th>
                        <th class="text-right"><?=$TotalUnits?></th>
                        <th class="text-right"><?=number_format($TotalRevenue)?></th>
                        <th></th>
                    </tr>
                <? } ?>
            </tbody>
        </table>
    </div>
    <small class="text-muted">Orders and revenue leave out cancelled orders. Units are deal sales (one deal = all its dishes).</small>
</div>
<? include_once("../includes/inc_footer.php"); ?>
