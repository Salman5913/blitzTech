<?
/**
 * Edit a website order on a POS style screen (customer details, cart, delivery fee) while it is still Received.
 * Once the kitchen marks it Preparing it can't be edited (RestaurantOrder::isEditable()).
 */
include_once("../connection/localhost.php");

$Order = RestaurantOrder::getOrder((int)($_GET['id'] ?? 0));

if (!$Order || !RestaurantOrder::isEditable($Order))
{
    ErrorHandler::setError($Order ? $Order['order_code'] . " can't be edited once it is " . RestaurantOrder::getStatusLabel($Order['status'], $Order['order_type']) . "." : "Order not found.");
    Utils::redirect(ProjectHelper::SiteURL() . "restaurant/manage_web_orders.php");
}

function h($Value) { return htmlspecialchars((string)$Value, ENT_QUOTES, 'UTF-8'); }

$isPickup = $Order['order_type'] == 'pickup';

/* ************* Catalogs: dishes of every branch, the screen shows the chosen branch's menu ************* */
$Branches = RestaurantBranch::getOptions();
if (!isset($Branches[$Order['branch_id']]))
    $Branches[$Order['branch_id']] = Branch::getName($Order['branch_id']);

$Catalogs = [];
foreach (array_keys($Branches) as $BranchId)
{
    $Catalogs[$BranchId] = [];
    foreach (RestaurantOrder::getMenuOptions($BranchId) as $Dish)
    {
        $Image = trim((string)$Dish['image']);
        $Emoji = trim((string)$Dish['emoji']);

        $Catalogs[$BranchId][] = [
            'id' => (int)$Dish['id'],
            'name' => $Dish['name'],
            'price' => (float)$Dish['price'],
            'category' => $Dish['category'],
            // only site relative paths or http(s) urls; emoji saved with wrong charset comes as "?" (same as SalesPOS)
            'image' => preg_match('#^(https?://|/)#i', $Image) ? $Image : '',
            'emoji' => str_contains($Emoji, '?') ? '' : $Emoji,
        ];
    }
}

/* ************* Cart: lines of the order ************* */
$Cart = [];
foreach (RestaurantOrder::getItems($Order['id']) as $Item)
{
    $Modifiers = array_map(fn($m) => $m['name'], RestaurantOrder::getModifiers($Item['id']));

    $Cart[] = [
        'key' => 'line-' . $Item['id'],
        'line_id' => (int)$Item['id'],
        'menu_id' => (int)$Item['menu_item_id'],
        'name' => $Item['item_name'],
        'price' => (float)$Item['unit_price'],
        'qty' => (int)$Item['qty'],
        // lines with modifiers or from a deal stay their own line
        'fixed' => !empty($Modifiers) || !empty($Item['deal_id']),
        'note' => trim((!empty($Item['deal_id']) ? 'Deal ' : '') . implode(', ', $Modifiers)),
    ];
}

$JsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

$_GET['_page_title'] = 'Edit ' . $Order['order_code'];
$_GET['_page_title_text'] = 'Change the order before the kitchen starts it';

include_once("../includes/inc_header.php");
?>
<form method="post" action="op_save_web_order_edit.php" id="order_edit_form" autocomplete="off">
    <input type="hidden" name="id" value="<?=(int)$Order['id']?>">

    <div class="card pos-head">
        <div class="row align-items-end">
            <div class="col-12 mb-2">
                <h3 class="card-title mb-0 d-inline-block"><i class="mdi mdi-web"></i> <?=h($Order['order_code'])?></h3>
                <small class="text-muted ml-2"><?=$isPickup ? 'Pickup' : 'Delivery'?> &middot; <?=date('M j, g:i A', strtotime($Order['created_at']))?></small>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="form-group mb-lg-0">
                    <label for="branch_id">Branch</label>
                    <select class="form-control" id="branch_id" name="branch_id">
                        <? foreach ($Branches as $BranchId => $BranchName) { ?>
                            <option value="<?=(int)$BranchId?>" <?=$BranchId == $Order['branch_id'] ? 'selected' : ''?>><?=h($BranchName)?></option>
                        <? } ?>
                    </select>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="form-group mb-lg-0">
                    <label for="customer_name">Customer</label>
                    <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?=h($Order['customer_name'])?>" maxlength="120" required>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="form-group mb-lg-0">
                    <label for="customer_phone">Mobile Number</label>
                    <input type="text" class="form-control" id="customer_phone" name="customer_phone" inputmode="tel" value="<?=h($Order['customer_phone'])?>" maxlength="40" required>
                </div>
            </div>
            <? if (!$isPickup) { ?>
                <div class="col-lg-3 col-md-6">
                    <div class="form-group mb-lg-0">
                        <label for="customer_address">Address<?=$Order['delivery_area'] ? ' (' . h($Order['delivery_area']) . ')' : ''?></label>
                        <input type="text" class="form-control" id="customer_address" name="customer_address" value="<?=h($Order['customer_address'])?>" maxlength="255">
                    </div>
                </div>
            <? } ?>
            <div class="<?=$isPickup ? 'col-lg-6' : 'col-lg-3'?> col-md-6">
                <div class="form-group mb-lg-0">
                    <label for="notes">Notes</label>
                    <input type="text" class="form-control" id="notes" name="notes" value="<?=h($Order['notes'])?>" maxlength="255">
                </div>
            </div>
        </div>
    </div>

    <div id="order_edit_lines"></div>
</form>

<div class="pos-layout">
    <div class="card pos-catalog">
        <div class="pos-toolbar">
            <div class="pos-categories" id="pos_categories" role="tablist">
            </div>
            <div class="pos-search">
                <input type="text" id="pos_search" placeholder="Search dish">
            </div>
        </div>
        <div id="pos_items"></div>
    </div>

    <!-- ************* Side popup cart (opens from "View Cart" button) ************* -->
    <div class="pos-cart-backdrop" id="pos_cart_backdrop"></div>
    <aside class="pos-cart" id="pos_cart" aria-label="Cart">
        <div class="pos-cart-head">
            <div>
                <h3 class="mb-0">Current Order</h3>
                <small id="pos_cart_sub">No items</small>
            </div>
            <div class="d-flex align-items-center">
                <button type="button" class="pos-cart-close" id="pos_cart_close" aria-label="Close cart"><i class="mdi mdi-close"></i></button>
            </div>
        </div>
        <div class="pos-cart-body" id="pos_cart_lines"></div>
        <div class="pos-cart-foot">
            <div class="pos-sum-row"><span>Items</span><span id="pos_cart_qty">0</span></div>
            <div class="pos-sum-row"><span>Sub Total</span><span id="pos_cart_subtotal">0.00</span></div>
            <? if (!$isPickup) { ?>
                <div class="pos-sum-row align-items-center">
                    <span>Delivery</span>
                    <input type="number" class="form-control form-control-sm text-right" style="max-width: 110px" id="delivery_fee" name="delivery_fee" form="order_edit_form" value="<?=(float)$Order['delivery_fee']?>" min="0" step="any">
                </div>
            <? } ?>
            <div class="pos-sum-total"><span>Total</span><span><small><?=ProjectHelper::DEFAULT_CURRENCY?></small> <b id="pos_cart_total">0.00</b></span></div>
            <button type="button" class="btn color btn-block pos-save" id="pos_save"><i class="mdi mdi-check-circle-outline"></i> Update Order</button>
            <a href="manage_web_orders.php" class="btn btn-link btn-block btn-sm mt-1">Back to Web Orders</a>
        </div>
    </aside>
</div>

<button type="button" class="pos-cart-fab" id="pos_cart_fab" aria-label="Open cart">
    <span class="pos-fab-icon"><i class="mdi mdi-cart-outline"></i><span class="pos-fab-count" id="pos_fab_count">0</span></span>
    <span>View Cart</span>
    <span class="pos-fab-total" id="pos_fab_total">0.00</span>
</button>

<link href="<?=ProjectHelper::SiteURL()?>assets/css/pos.css?v=1" rel="stylesheet" type="text/css" />
<script>
    window.ORDER_EDIT_CONFIG = {
        catalogs: <?=json_encode($Catalogs, $JsonFlags)?>,
        cart: <?=json_encode($Cart, $JsonFlags)?>
    };
</script>
<script src="<?=ProjectHelper::SiteURL()?>js/restaurant/web_order_edit.js?v=2"></script>
<? include_once("../includes/inc_footer.php"); ?>
