<?
/**
 * Order Invoice (OIV), Dine-in Invoice (DIV, with Table No) and WhatsApp Invoice (WHIV) - POS screen
 * Same behaviour as SIV, saved through Sales class (sl_sales / sl_sales_data).
 * Website Invoices (WIV) open here too, view only: they are made from website orders (restaurant/manage_web_orders.php).
 */
include_once("../connection/localhost.php");

$VoucherTypeId = ($_GET['voucher_type_id'] ?? '') == VoucherType::WIV ? VoucherType::WIV : SalesPOS::getVoucherTypeId($_GET['voucher_type_id'] ?? '');
$isWebsiteInvoice = $VoucherTypeId == VoucherType::WIV;
$isDineIn = $VoucherTypeId == VoucherType::DIV;
$_GET['voucher_type_id'] = $VoucherTypeId;

$oSales = new Sales();
$oSales->initForEdit();

$isEdit = $oSales->isEdit() && !$oSales->isCopy();
$isLoadData = $oSales->isEdit();
$isView = $oSales->isView() || ($isEdit && !SecurityShell::isVoucherEditAccess($VoucherTypeId)) || $isWebsiteInvoice;

// No new Website Invoice from POS
if ($isWebsiteInvoice && !$isEdit)
    Utils::redirect(ProjectHelper::SiteURL()."sales/report_sales.php?voucher_type_id=".VoucherType::WIV);

$RowDetails = $isLoadData ? $oSales->getDetails() : [];

// POS has its own new order flow: no previous / next / new buttons in the page heading, only print for a saved invoice
$_GET['_page_title_text'] = $isEdit ? '<a href="'.ProjectHelper::SiteURL().'pos/print_voucher.php?id='.$oSales->getVoucherId().'&voucher_type_id='.$VoucherTypeId.'" target="_blank" class="btn btn-sm btn-white" title="Print receipt"><i class="mdi mdi-printer"></i></a>' : '';

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

    // Website dishes not linked to stock are saved as non inventory lines
    $SelectData = $oSales->getData(Sales::IND_NON_INVENTORY);
    while ($RowData = DBQM::fetch_assoc($SelectData))
    {
        $Cart[] = [
            'id' => 'ni-'.$RowData['id'],
            'name' => $RowData['item_description'],
            'price' => (float) $RowData['selling_price'],
            'qty' => (float) ($RowData['qty'] - $RowData['qty_return']),
        ];
    }
}
/* ************* Existing cart (edit / copy) ************* */

$CustomerAccountId = $isLoadData ? $RowDetails['customer_account_id'] : getConstantAccount()->getCashCustomer();
$BranchId = $isLoadData ? $RowDetails['branch_id'] : $_SESSION['__branch_id'];
$VoucherDate = $isEdit ? date('m/d/Y', strtotime($RowDetails['voucher_date'])) : '';
$TableNo = $isDineIn && $isLoadData ? SalesPOS::getTableNo($oSales->getVoucherId()) : '';

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
            <div class="<?=$isDineIn ? 'col-lg-2' : 'col-lg-3'?> col-md-12 mb-2 mb-lg-0">
                <h3 class="card-title mb-1"><i class="mdi mdi-<?=SalesPOS::getIcon($VoucherTypeId)?>"></i> <?=SalesPOS::getTitle($VoucherTypeId)?></h3>
                <small class="text-muted">
                    <?=$isEdit ? VoucherType::getCode($VoucherTypeId)."-".$oSales->getVoucherNumber()." &middot; ".DateDecode($RowDetails['voucher_date']) : "New Order &middot; ".date('M j, Y')?>
                </small>
            </div>
            <? if ($isDineIn) { ?>
                <div class="col-lg-2 col-md-3">
                    <div class="form-group mb-lg-0">
                        <label for="table_no">Table No</label>
                        <input type="text" id="table_no" name="table_no" maxlength="20" placeholder="e.g. T-5" value="<?=htmlspecialchars((string) $TableNo)?>">
                    </div>
                </div>
            <? } ?>
            <div class="<?=$isDineIn ? 'col-lg-2' : 'col-lg-3'?> col-md-4">
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
    <input type="hidden" name="voucher_type_id" id="voucher_type_id" value="<?=$VoucherTypeId?>">
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
            <? if ((float) ($RowDetails['transport_amount'] ?? 0) > 0) { ?>
                <div class="pos-sum-row"><span>Delivery</span><span><?=Utils::format($RowDetails['transport_amount'], 2)?></span></div>
            <? } ?>
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

<link href="<?=ProjectHelper::SiteURL()?>assets/css/pos.css?v=2" rel="stylesheet" type="text/css" />
<script>
    window.POS_CONFIG = {
        catalog: <?=json_encode($Catalog, $JsonFlags)?>,
        cart: <?=json_encode($Cart, $JsonFlags)?>,
        isView: <?=$isView ? 'true' : 'false'?>,
        deliveryFee: <?=(float) ($RowDetails['transport_amount'] ?? 0)?>,
        branchId: <?=(int) $BranchId?>,
        menuStatus: <?=json_encode(SalesPOS::getMenuStatus($BranchId), $JsonFlags)?>,
        isDineIn: <?=$isDineIn ? 'true' : 'false'?>,
        posUrl: <?=json_encode(VoucherType::getLink($isWebsiteInvoice ? VoucherType::OIV : $VoucherTypeId), $JsonFlags)?>
    };
</script>
<script src="<?=ProjectHelper::SiteURL()?>js/pos/pos_voucher.js?v=8"></script>
<? include_once("../includes/inc_footer.php"); ?>
