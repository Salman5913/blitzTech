<?
/**
 * Order Invoice Voucher (POS)
 * Same behaviour as SIV, saved through Sales class (sl_sales / sl_sales_data).
 */
include_once("../connection/localhost.php");

$_GET['voucher_type_id'] = VoucherType::OIV;

$oSales = new Sales();
$oSales->initForEdit();

$isEdit = $oSales->isEdit() && !$oSales->isCopy();
$isLoadData = $oSales->isEdit();
$isView = $oSales->isView() || ($isEdit && !SecurityShell::isVoucherEditAccess(VoucherType::OIV));

$RowDetails = $isLoadData ? $oSales->getDetails() : [];

/* ************* Existing cart (edit / copy) ************* */
$Cart = [];
if ($isLoadData)
{
    $SelectData = $oSales->getData(Sales::IND_DATA);
    while ($RowData = DBQM::fetch_assoc($SelectData))
    {
        if (empty($RowData['stock_id']))
            continue;

        $Cart[] = [
            'id' => Stock::joinStockIdAttributeId($RowData['stock_id'], $RowData['attribute_id']),
            'name' => Stock::getName($RowData['stock_id'], $RowData['attribute_id']),
            'price' => (float) $RowData['selling_price'],
            'qty' => (float) ($RowData['qty'] - $RowData['qty_return']),
        ];
    }
}
/* ************* Existing cart (edit / copy) ************* */

$CustomerAccountId = $isLoadData ? $RowDetails['customer_account_id'] : getConstantAccount()->getCashCustomer();
$BranchId = $isLoadData ? $RowDetails['branch_id'] : $_SESSION['__branch_id'];
$VoucherDate = $isEdit ? date('m/d/Y', strtotime($RowDetails['voucher_date'])) : '';

$Catalog = SalesPOS::getCatalog();

$Categories = [];
foreach ($Catalog as $Item)
    $Categories[$Item['category_id']] = $Item['category'];

$JsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

include_once("../includes/inc_header.php");
?>
<form id="pos_form" onsubmit="return false;" autocomplete="off">
    <? $oSales->showVoucherDeleteNotification(); ?>
    <div class="card pos-head">
        <div class="row align-items-end">
            <div class="col-lg-3 col-md-12 mb-2 mb-lg-0">
                <h3 class="card-title mb-1"><i class="mdi mdi-point-of-sale"></i> Order Invoice</h3>
                <small class="text-muted">
                    <?=$isEdit ? VoucherType::getCode(VoucherType::OIV)."-".$oSales->getVoucherNumber()." &middot; ".DateDecode($RowDetails['voucher_date']) : "New Order &middot; ".date('M j, Y')?>
                </small>
            </div>
            <div class="col-lg-3 col-md-4">
                <div class="form-group mb-lg-0">
                    <label for="customer_id">Customer</label>
                    <input type="text" id="customer_id" name="customer_id" data-auto-select="Y" <? Autocomplete::ACSearchAccount($CustomerAccountId); ?>>
                </div>
            </div>
            <div class="col-lg-2 col-md-3">
                <div class="form-group mb-lg-0">
                    <label for="branch_id">Warehouse</label>
                    <input type="text" id="branch_id" name="branch_id" <? Autocomplete::Search(Autocomplete::TYPE_BRANCH, $BranchId); ?>>
                </div>
            </div>
            <? CostCenter::designVoucherField($RowDetails['cost_center_id'] ?? '', $isEdit, 'col-lg-2 col-md-3', 'mb-lg-0'); ?>
            <div class="col-lg-2 col-md-2">
                <div class="form-group mb-lg-0">
                    <label for="mobile_number">Mobile Number</label>
                    <input type="text" id="mobile_number" name="mobile_number" inputmode="tel" value="<?=htmlspecialchars((string) $RowDetails['mobile_number'])?>">
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="voucher_date" id="voucher_date" value="<?=$VoucherDate?>">
    <input type="hidden" name="voucher_type_id" id="voucher_type_id" value="<?=VoucherType::OIV?>">
    <input type="hidden" name="__voucher_submit_token" id="__voucher_submit_token" value="<?=bin2hex(random_bytes(16))?>">
    <? if ($isEdit) { ?>
        <input type="hidden" name="voucher_id" id="pos_voucher_id" value="<?=$oSales->getVoucherId()?>">
    <? } ?>
</form>

<div class="pos-layout">
    <div class="card pos-catalog">
        <div class="pos-toolbar">
            <div class="pos-categories" id="pos_categories" role="tablist">
                <button type="button" class="pos-cat active" data-cat="">All</button>
                <? foreach ($Categories as $CategoryId => $CategoryName) { ?>
                    <button type="button" class="pos-cat" data-cat="<?=(int) $CategoryId?>"><?=htmlspecialchars($CategoryName)?></button>
                <? } ?>
            </div>
            <div class="pos-search">
                <input type="text" id="pos_search" placeholder="Search item or scan barcode">
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
                <? if (!$isView) { ?>
                    <button type="button" class="pos-cart-clear" id="pos_clear" title="Clear cart"><i class="mdi mdi-delete-sweep-outline"></i></button>
                <? } ?>
                <button type="button" class="pos-cart-close" id="pos_cart_close" aria-label="Close cart"><i class="mdi mdi-close"></i></button>
            </div>
        </div>
        <div class="pos-cart-body" id="pos_cart_lines"></div>
        <div class="pos-cart-foot">
            <div class="pos-sum-row"><span>Items</span><span id="pos_cart_qty">0</span></div>
            <div class="pos-sum-row"><span>Sub Total</span><span id="pos_cart_subtotal">0.00</span></div>
            <div class="pos-sum-total"><span>Total</span><span><small><?=ProjectHelper::DEFAULT_CURRENCY?></small> <b id="pos_cart_total">0.00</b></span></div>
            <? if (!$isView) { ?>
                <button type="button" class="btn color btn-block pos-save" id="pos_save" disabled><i class="mdi mdi-check-circle-outline"></i> <?=$isEdit ? 'Update Order' : 'Save Order'?></button>
                <? if ($isEdit) { ?>
                    <button type="button" class="btn btn-link text-danger btn-block btn-sm mt-1" id="pos_delete"><i class="mdi mdi-trash-can-outline"></i> Delete Order</button>
                <? } ?>
            <? } ?>
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
    window.POS_CONFIG = {
        catalog: <?=json_encode($Catalog, $JsonFlags)?>,
        cart: <?=json_encode($Cart, $JsonFlags)?>,
        isView: <?=$isView ? 'true' : 'false'?>,
        branchId: <?=(int) $BranchId?>,
        menuStatus: <?=json_encode(SalesPOS::getMenuStatus($BranchId), $JsonFlags)?>,
        posUrl: <?=json_encode(VoucherType::getLink(VoucherType::OIV), $JsonFlags)?>
    };
</script>
<script src="<?=ProjectHelper::SiteURL()?>js/pos/pos_voucher.js?v=4"></script>
<? include_once("../includes/inc_footer.php"); ?>
