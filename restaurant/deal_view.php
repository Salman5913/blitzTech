<?
include_once("../connection/localhost.php");

$Id = (int)($_GET['id'] ?? 0);
$RowDeal = RestaurantDeal::getDetails($Id);
if (!$RowDeal)
    ErrorHandler::setError("Deal not found.");

$_GET['_page_title'] = 'Deal Details';
$_GET['_page_title_text'] = $RowDeal ? $RowDeal['name'] : 'Deal not found';

include_once("../includes/inc_header.php");

function h($Value) { return htmlspecialchars((string)$Value, ENT_QUOTES, 'UTF-8'); }

$ActiveTab = 'deals_report';
include_once(__DIR__ . '/_tabs.php');

if ($RowDeal) {
    $Items = RestaurantDeal::getItems($Id);
    $Orders = [];
    $SelectOrders = RestaurantDeal::getRecentOrders($Id);
    while ($Row = DBQM::fetch_assoc($SelectOrders)) $Orders[] = $Row;

    $NormalPrice = 0;
    foreach ($Items as $Item) $NormalPrice += (float)$Item['price'];
    $Saves = $NormalPrice - (float)$RowDeal['price'];
}
?>
<? if ($RowDeal) { ?>
<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="row">
                <div class="col-sm-8">
                    <h3 class="card-title"><?=h($RowDeal['name'])?></h3>
                    <p class="text-muted mb-2"><?=h($RowDeal['description'])?></p>
                </div>
                <div class="col-sm-4 text-right">
                    <a href="deals.php?id=<?=$Id?>" class="btn btn-sm btn-light" title="Edit"><i class="mdi mdi-pencil"></i> Edit</a>
                </div>
            </div>
            <table class="table table-sm">
                <tr><th>Normal price</th><td class="text-right"><?=number_format($NormalPrice)?></td></tr>
                <tr><th>Deal price</th><td class="text-right"><b><?=number_format($RowDeal['price'])?></b></td></tr>
                <tr><th>Customer saves</th><td class="text-right"><?=$Saves > 0 ? number_format($Saves) . ' (' . round($Saves / $NormalPrice * 100) . '%)' : '—'?></td></tr>
                <tr><th>Website</th><td class="text-right"><?=$RowDeal['show_on_website'] ? '<span class="badge badge-soft-success">Shown</span>' : '<span class="badge badge-soft-secondary">Hidden</span>'?></td></tr>
            </table>
        </div>
        <div class="card">
            <h3 class="card-title">Dishes</h3>
            <table class="table table-sm mb-0">
                <? foreach ($Items as $Item) { ?>
                    <tr>
                        <td><?=h($Item['name'])?> <small class="text-muted">(<?=h($Item['category'])?>)</small>
                            <? if (!$Item['sellable']) { ?><span class="badge badge-soft-warning">not sellable now</span><? } ?>
                        </td>
                        <td class="text-right"><?=number_format($Item['price'])?></td>
                    </tr>
                <? } ?>
            </table>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <h3 class="card-title">Recent Orders</h3>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th class="text-right">Deals</th>
                            <th class="text-right">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? foreach ($Orders as $Order) { ?>
                            <tr>
                                <td>
                                    <a href="manage_web_orders.php?filter=<?=h($Order['status'])?>"><?=h($Order['order_code'])?></a>
                                    <? if ($Order['erp_voucher_number']) { ?><small class="text-muted">· SIV #<?=(int)$Order['erp_voucher_number']?></small><? } ?>
                                </td>
                                <td><small><?=date('M j, g:i A', strtotime($Order['created_at']))?></small></td>
                                <td class="text-right"><?=(int)$Order['deal_qty']?></td>
                                <td class="text-right"><?=number_format($Order['amount'])?></td>
                                <td><?=h(RestaurantOrder::getStatusLabel($Order['status'], $Order['order_type']))?></td>
                            </tr>
                        <? } ?>
                        <? if (!$Orders) { ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No orders for this deal yet.</td></tr>
                        <? } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<? } else { ?>
<div class="card text-center text-muted py-5">
    <a href="deals_report.php">Back to Deals Report</a>
</div>
<? } ?>
<? include_once("../includes/inc_footer.php"); ?>
