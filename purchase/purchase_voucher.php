<?
include_once("../connection/localhost.php");

$oPurchase = new Purchase();
$oPurchase->initForEdit();

$isEdit = $oPurchase->isEdit();
$VoucherTypeId = $oPurchase->getVoucherType();

$isPurchaseVoucher = $VoucherTypeId == VoucherType::PIV;
$isReturnVoucher = $VoucherTypeId == VoucherType::PRV;
$isPurchaseOrderVoucher = $VoucherTypeId == VoucherType::POV;
$isOpeningVoucher = $VoucherTypeId == VoucherType::OPS;

$RowDetails = $oPurchase->getDetails();
$SelectData = $oPurchase->getData(Purchase::IND_DATA);
$SelectPaymentData = $oPurchase->getData(Purchase::IND_PAYMENT);

$DataRows = DatabaseHelper::getRecordCount($SelectData) + 1;
$isVoucherSearched = (isset($_GET['requisition_id']) or isset($_GET['order_id']));

if ($isVoucherSearched)
{
    if (isset($_GET['requisition_id']))
        $SelectData = $oPurchase->getRemainingStockFromRequisition($_GET['requisition_id']);
    else
        $SelectData = $oPurchase->getRemainingStockFromOrder($_GET['order_id']);
}

if ($isPurchaseVoucher)
{
    $OrderId = $oPurchase->isEdit() ? $RowDetails['purchase_order_id'] : $_GET['order_id'];

    if (!empty($OrderId))
        $LinkedTo = VoucherType::getName(VoucherType::POV)." - ".$oPurchase->getVoucherNumberById($OrderId);
}
else
{
    // if purchase order
    $oPurchaseRequisition = new PurchaseRequisition();

    $RequisitionId = $oPurchase->isEdit() ? $RowDetails['purchase_requisition_id'] : $_GET['requisition_id'];

    if (!empty($RequisitionId))
        $LinkedTo = VoucherType::getName(VoucherType::PR)." - ".$oPurchaseRequisition->getVoucherNumberById($RequisitionId);
}

$PaymentDataRows = DatabaseHelper::getRecordCount($SelectPaymentData) + 1;
$SupplierAccountId = isset($_GET['supplier_id']) ? $_GET['supplier_id'] : $RowDetails['supplier_account_id'];

$oChartOfAccount = new ChartOfAccount();

/* ******* Rows whose stock is already used in other vouchers, only rate can be changed ******* */
$UsedStockRows = ($isEdit && !$oPurchase->isCopy()) ? $oPurchase->getUsedStockRows($oPurchase->getVoucherId()) : [];
$hasUsedStock = !empty($UsedStockRows);

include_once("../includes/inc_header.php");
?>
<script type="text/javascript" src="../js_live/purchase/purchase.min.js"></script>
<? if ($VoucherTypeId == VoucherType::POV and !$oPurchase->isEdit() and !$isVoucherSearched) { ?>
    <form action="" method="get" <? Utils::formValidate(); ?> class="<?=Purchase::isAvailablePurchaseRequisitions() > 0 ? '' : 'hidden'?>" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-12">
                <div class="card bg-soft-warning">
                    <h3 class="card-title">Select Requisition Voucher</h3>
                    <div class="row">
                        <div class="col-lg-7 hidden">
                            <div class="form-group">
                                <label for="supplier_id">Supplier Name</label>
                                <div class="input-group">
                                    <input type="text" class="form-control col-md-12" id="supplier_id" name="supplier_id" <? Utils::isRequired(); Autocomplete::ACSearchAccount(''); ?>>
                                    <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <a href="../chart_of_account/manage_accounts.php" target="_blank" title="Create New Supplier"><i class="fa fa-user"></i></a>
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="requisition_id">Requisition Voucher #</label>
                                <select name="requisition_id" id="requisition_id"><? $oPurchase->getSelectRequisitionsDropdownOptions($RowDetails['purchase_requisition_id']) ?></select>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>&nbsp</label>
                                <button type="submit" class="btn color form-control" name="search_requisition"><i class="mdi mdi-account-search"></i> Search Data</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <? Utils::GETtoHiddenFields(); ?>
    </form>
<?
}
elseif ($VoucherTypeId == VoucherType::PIV and !$oPurchase->isEdit() and !$isVoucherSearched)
{
?>
    <form action="" method="get" <? Utils::formValidate(); ?> class="<?=Purchase::isAvailablePurchaseOrders() > 0 ? '' : 'hidden'?>">
        <div class="row">
            <div class="col-lg-12">
                <div class="card bg-soft-warning">
                    <h3 class="card-title"> Select Purchase Order Voucher</h3>
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="form-group">
                                <label for="supplier_id">Supplier Name</label>
                                <div class="input-group">
                                    <input type="text" class="form-control col-md-12" id="supplier_id" name="supplier_id" data-js="search_supplier_id" <? Utils::isRequired(); Autocomplete::ACSearchAccount($isEdit ? $RowDetails['supplier_account_id'] : $_GET['supplier_id']); ?>>
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <a href="javascript:void(0)" <? Utils::AjaxLoadedPopup("Save", ProjectHelper::SiteURL()."chart_of_account/ajax_save_account.php");?>><i class="fa fa-user"></i></a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="order_id">Order #</label>
                                <select name="order_id" id="order_id" <? Utils::isRequired() ?>></select>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group">
                                <label>&nbsp</label>
                                <button type="submit" class="btn color form-control" name="search_requisition"><i class="mdi mdi-account-search"></i> Search Data</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <? Utils::GETtoHiddenFields(); ?>
    </form>
<?
}

?>
<form method="post" action="op_save_purchase.php" <? Utils::formValidate(); ?>>
    <? $oPurchase->showVoucherDeleteNotification() ?>
    <? if ($hasUsedStock) { ?>
        <div class="row">
            <div class="col-sm-12">
                <div class="card ">
                    <h3 class=" text-warning"><i class="mdi mdi-lock-outline"></i> Stock Already Used</h3>
                    <p class="mb-0 text-warning">Some items of this voucher are already used in other vouchers (sales, returns, transfers etc.). For highlighted items only <b>Rate / Disc. %</b> can be changed. Qty, Item, Warehouse are locked and voucher can't be deleted.</p>
                </div>
            </div>
        </div>
    <? } ?>
    <div class="row">
        <div class="col-lg-9">
            <div class="card <?=$VoucherTypeId == VoucherType::OPS ? 'hidden' : ''?>">
                <h3 class="card-title"> Basic Details</h3>
                <div class="row main-details">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="supplier_id">Supplier Name</label>
                            <div class="input-group">
                                <input type="text" class="form-control col-md-12" id="supplier_id" name="supplier_id" data-auto-select="Y" <? Utils::isRequired(); Autocomplete::ACSearchAccount($SupplierAccountId); ?>>
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <a href="javascript:void(0)" <? Utils::AjaxLoadedPopup('Save', ProjectHelper::SiteURL()."chart_of_account/ajax_save_account.php");?>><i class="fa fa-user"></i></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="voucher_date">Date</label>
                            <input type="text" name="voucher_date" id="voucher_date" data-type="date" value="<?=DateDecode($VoucherTypeId == VoucherType::OPS ?  Settings::getOpeningDateTime() : $RowDetails['voucher_date'])?>" <? Utils::isRequired() ?>>
                        </div>
                    </div>
                    <? CostCenter::designVoucherField($RowDetails['cost_center_id'] ?? '', $isEdit, 'col-lg-3'); ?>
                    <? if ($isPurchaseVoucher) { ?>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="due_date">Due Date</label>
                                <input type="text" name="due_date" id="due_date" data-type="date" value="<?=DateDecode($RowDetails['due_date'])?>" <? Utils::isRequired() ?>>
                            </div>
                        </div>
                    <? } ?>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="_project_id">Project Name</label>
                            <input type="text" name="_project_id" id="_project_id" <? Autocomplete::Search(Autocomplete::TYPE_APPROVED_PROJECT, $RowDetails['project_id']); ?> >
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="description">Reference</label>
                            <textarea id="description" name="description"><?=$RowDetails['description']?></textarea>
                        </div>
                    </div>
                    <div class="col-lg-4 hidden">
                        <div class="form-group">
                            <label for="vouchers_date"><? if ($isPurchaseVoucher) { ?>GRN<? } ?> Date</label>
                            <input type="text" name="vouchers_date" id="vouchers_date" data-type="date" value="<?=DateDecode($VoucherTypeId == VoucherType::OPS ?  Settings::getOpeningDateTime() : $RowDetails['voucher_date'])?>" <? Utils::isRequired() ?>>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card">
                <h3 class="card-title"> invoice Status</h3>
                <div class="row">
                    <div class="col-12">
                        <? if (!empty($LinkedTo)) { ?>
                            <div class="form-group">
                                <label for="linked_to" class="font-weight-bold">Linked To</label>
                                <input type="text" id="linked_to" class="text-success font-weight-bold" readonly value="<?=$LinkedTo?>">
                            </div>
                        <? } ?>
                        <? if ($isPurchaseVoucher or $isOpeningVoucher or $isReturnVoucher) { ?>
                            <div class="form-group">
                                <label for="branch_id">Warehouse</label>
                                <input type="text" name="branch_id" id="branch_id" data-js="branch_id" <? Autocomplete::Search(Autocomplete::TYPE_BRANCH, $RowDetails['branch_id']); ?> <? Utils::isRequired() ?> <?=$hasUsedStock ? 'readonly class="used-stock-locked" title="Stock already used, warehouse is locked"' : ''?>>
                            </div>
                            <div class="form-group">
                                <label for="invoice_type">Invoice Type</label>
                                <select name="invoice_type" id="invoice_type" <? Utils::isRequired() ?>>
                                    <? Utils::getDropDownOptionsFromArray([Purchase::TYPE_INVOICE => 'Delivered & Invoiced', Purchase::TYPE_DELIVERY => 'Delivered Only'], $RowDetails['invoice_type'])?>
                                </select>
                            </div>
                        <? } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="card-title"> Purchase Items</h3>
                    </div>
                    <div class="col-sm-6 text-right <?=$oPurchase->isEdit() ? 'hidden' : ''?>">
                        <a href="../stock/stock_management.php" class="shadow-sm button btn-sm bg-soft-purple font-weight-bold" target="_blank"><i class="mdi mdi-plus"></i> New Item</a>
                    </div>
                </div>

                <div class="purchase-items-wrap">
                <table class="table color dynamic stock table-bordered">
                    <thead>
                    <tr>
                        <th width="30%">Item Name</th>
                        <th width="30%">Description</th>
                        <th width="10%">available Qty</th>
                        <th width="10%" class="text-center <?=$isReturnVoucher ? 'hidden' : '' ?>">Qty</th>
                        <th width="10%" class="text-center <?=!$isReturnVoucher || $VoucherTypeId == VoucherType::OPS ? 'hidden' : '' ?>">Qty Return</th>
                        <th width="10%" class="text-center">Rate</th>
                        <th width="10%" class="text-center">Disc. %</th>
                        <th width="10%" class="text-right">Total</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-center total_qty font-weight-bold <?=$isReturnVoucher ? 'hidden' : '' ?>" <? Utils::sumInputs('[name="qty[]"]'); ?>></td>
                        <td class="text-center total_qty_return font-weight-bold <?=!$isReturnVoucher || $VoucherTypeId == VoucherType::OPS ? 'hidden' : '' ?>" <? Utils::sumInputs('[name="qty_return[]"]'); ?>></td>
                        <td class="text-right">&nbsp;</td>
                        <td class="text-right">&nbsp;</td>
                        <td class="total_rs font-weight-bolder text-right" <? Utils::sumInputs(".total_data"); ?>>&nbsp;</td>
                    </tr>
                    </tfoot>
                    <tbody>
                    <? $i=0; while ($RowData = DBQM::fetch_assoc($SelectData) or $i < $DataRows) {
                        $isUsedRow = !empty($RowData['id']) && isset($UsedStockRows[$RowData['id']]);
                        $LockedAttr = $isUsedRow ? ' readonly data-used-locked="Y" ' : '';
                    ?>
                        <tr <?=$isUsedRow ? 'data-used-locked-row="Y" data-used-qty="'.round($UsedStockRows[$RowData['id']]['used_qty'], 4).'"' : ''?>>
                            <td><label><input type="text" name="stock_id[]" data-label="Stock" <?=$LockedAttr?> <? Autocomplete::ACSearchStockRow($RowData['stock_id'], $RowData['attribute_id'], '[name="branch_id"]', '[data-js="qty-available"]', '[data-js="stock_qty_return"]', '', $isReturnVoucher); ?> /></label></td>
                            <td><label><textarea id="item_detail" name="item_detail[]" data-js="item_detail"><?=$RowData['item_description']?></textarea></label></td>
                            <td data-js="qty-available" class="text text-center"> </td>
                            <td class="text-center <?=$isReturnVoucher ? 'hidden' : '' ?>"><label><input type="text" name="qty[]" size="4" data-js="stock_qty" data-type="float" value="<?=$RowData['qty']?>" <?=$LockedAttr?> /></label></td>
                            <td class="text-center <?=!$isReturnVoucher || $VoucherTypeId == VoucherType::OPS ? 'hidden' : '' ?>"><label><input type="text" name="qty_return[]" size="4" data-js="stock_qty_return" data-type="float" value="<?=$RowData['qty_return']?>" /></label></td>
                            <td class="text-right"><label><input type="text" name="rate[]" size="6" data-js="stock_rate" data-type="float" value="<?=$RowData['rate']?>" /></label></td>
                            <td class="text-right"><label><input type="text" name="discount_percentage[]" data-js="stock_disc_percent" size="6" data-type="float" value="<?=$RowData['item_dis']?>" /></label></td>
                            <td class="text total_row text-right">0</td>
                        </tr>
                        <? $i++; } ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    <? if ($isPurchaseVoucher or $isReturnVoucher) { ?>
        <div class="row">
            <div class="offset-lg-6 col-lg-6">
                <div class="card">
                    <div class="row">
                        <div class="col-auto mr-auto">
                            <h3 class="card-title"> Purchase Details</h3>
                        </div>
                        <div class="col-auto ml-auto"></div>
                    </div>
                    <div class="row">
                        <label class="col-auto col-form-label">Gross Purchase</label>
                        <div class="col-auto col-form-label ml-auto total_gross_sales">0.00</div>
                    </div>
                    <div class="row border-top pt-1 pb-3 pb-md-1">
                        <label class="col-md-4 col-form-label" for="invoice_discount_amount">Invoice Discount</label>
                        <div class="col-8 col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control col-8" data-type="float" name="invoice_discount_amount" id="invoice_discount_amount" size="6" value="<?=$RowDetails['invoice_discount_amount']?>">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><?=ProjectHelper::DEFAULT_CURRENCY?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 text-right col-form-label col-md-4 ml-auto total_discount">0.00</div>
                    </div>
                    <div class="row pt-1 pb-3 pb-md-1">
                        <label class="d-none d-md-flex col-md-4 col-form-label" for="invoice_discount_percentage">&nbsp;</label>
                        <div class="col-8 col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control col-8" data-type="float" name="invoice_discount_percentage" id="invoice_discount_percentage" size="6" value="<?=$RowDetails['invoice_discount_per']?>">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row border-top bg-light ">
                        <label class="col-auto col-form-label font-weight-bold">Gross Total</label>
                        <div class="col-auto col-form-label ml-auto font-weight-bold gross_total">0.00</div>
                    </div>
                    <div class="row border-top pt-1 pb-3 pb-md-1">
                        <label class="col-md-4 col-form-label" for="invoice_gst_percentage"><?=ProjectHelper::TAX_LABEL?></label>
                        <div class="col-8 col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control col-8" data-type="float" name="invoice_gst_percentage" id="invoice_gst_percentage" size="6" value="<?=$RowDetails['invoice_gst_percentage']?>">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 text-right col-form-label col-md-4 ml-auto total_gst">0.00</div>
                    </div>
                    <div class="row border-top bg-success text-white">
                        <label class="col-auto col-form-label font-weight-bold">Invoice Total</label>
                        <div class="col-auto col-form-label ml-auto font-weight-bold final_amount">0.00</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row <?/*=in_array($VoucherTypeId, VoucherType::getSalesVoucherTypes(false)) ? '' : 'hidden'*/?> hidden">
            <div class="col-sm-12">
                <div class="card">
                    <h3 class="card-title"> Payments</h3>
                    <table class="table color dynamic payment table-bordered">
                        <thead>
                        <tr>
                            <th width="25%">Cash / Bank Name</th>
                            <th width="15%" class="text-center">Amount Paid</th>
                            <th width="15%">Cheque Number</th>
                            <th width="15%">Cheque Date</th>
                            <th width="30%">Description</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <td>&nbsp;</td>
                            <td class="font-weight-bolder text-center" <? Utils::sumInputs('[name="payment_amount[]"]'); ?>>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                        </tfoot>
                        <tbody>
                        <? $i=0; while ($RowPaymentData = DBQM::fetch_assoc($SelectPaymentData) or $i < $PaymentDataRows) { ?>
                            <tr>
                                <td><label><input type="text" id="cash_bank_id" name="cash_bank_id[]" <? Autocomplete::ACSearchAccountByCOAId([getConstantCategory()->getCashInHand(), getConstantCategory()->getBank()], $RowPaymentData['cash_bank_id']); ?>></label></td>
                                <td><label><input type="text" id="payment_amount" name="payment_amount[]" class="large_amount" data-js='payment_amount' data-type="float" value="<?=$RowPaymentData['amount']?>"></label></td>
                                <td><label><input type="text" id="cheque_number" name="cheque_number[]" value="<?=$RowPaymentData['cheque_no']?>"></label></td>
                                <td><label><input type="text" id="cheque_date" data-type="date" name="cheque_date[]" value="<?=$RowPaymentData['cheque_date']?>"></label></td>
                                <td><label><textarea id="payment_desc" name="payment_desc[]"><?=$RowPaymentData['description']?></textarea></label></td>
                            </tr>
                            <? $i++; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <? } ?>
    <input type="hidden" name="purchase_requisition_id" id="purchase_requisition_id" value="<?=$RequisitionId?>">
    <input type="hidden" name="purchase_order_id" id="purchase_order_id" value="<?=$OrderId?>">
    <input type="hidden" id="total_invoice_amount" name="total_invoice_amount" value="">
    <input type="hidden" id="calc_discount" name="calc_discount" value="">
    <input type="hidden" id="calc_gst" name="calc_gst" value="">
    <? $oPurchase->showVoucherButtons() ?>
</form>
<style>
    /* ************ Mobile responsive ************ */
    .purchase-items-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    @media (max-width: 991.98px) {
        .purchase-items-wrap > table.dynamic { min-width: 900px; }
        .purchase-items-wrap > table.dynamic textarea { min-width: 150px; }
    }
    @media (max-width: 575.98px) {
        .voucher-buttons > div { flex: 0 0 100%; max-width: 100%; margin-left: 0 !important; margin-right: 0 !important; }
        .voucher-buttons #btn_delete { width: 100%; margin: 0 0 10px 0; }
        .voucher-buttons .btn-group { display: flex; width: 100%; }
        .voucher-buttons .btn-group > .btn:first-child { flex: 1 1 auto; }
        .card-title { font-size: 1rem; }
    }

    /* ************ Stock already used, locked fields ************ */
    tr[data-used-locked-row="Y"] { background: rgba(255, 193, 7, .12); }
    tr[data-used-locked-row="Y"] .dt-del { display: none !important; }
    input.used-stock-locked, input[data-used-locked="Y"] { background-color: #eef0f3 !important; cursor: not-allowed; }
    .used-qty-note { display: block; font-size: 11px; white-space: nowrap; }
</style>
<? if ($hasUsedStock) { ?>
<script>
    $(function () {
        var _stock_table = $("table.dynamic.stock");

        function applyUsedStockLock()
        {
            // new rows are cloned from first row, remove lock from them
            _stock_table.find('tbody tr:not([data-used-locked-row="Y"])').find('[data-used-locked="Y"]').prop("readonly", false).removeAttr("data-used-locked readonly");
            _stock_table.find('tbody tr:not([data-used-locked-row="Y"]) .used-qty-note').remove();

            _stock_table.find('tbody tr[data-used-locked-row="Y"]').each(function () {
                var _tr = $(this);
                var _stock = _tr.find('[data-type="search_stock_row"]');

                _tr.find('[data-used-locked="Y"]').prop("readonly", true).attr("title", "Stock already used in other vouchers, only rate can be changed");

                if (_stock.data("ui-autocomplete"))
                    _stock.autocomplete("disable");

                if (!_tr.find(".used-qty-note").length)
                    _tr.find('[data-js="stock_qty"]').after('<small class="used-qty-note text-danger"><i class="mdi mdi-lock-outline"></i> Used: ' + _tr.attr("data-used-qty") + '</small>');
            });
        }

        function disableBranchAutocomplete()
        {
            $('input[name="branch_id"].used-stock-locked').each(function () {
                if ($(this).data("ui-autocomplete"))
                    $(this).autocomplete("disable");
            });
        }

        applyUsedStockLock();
        disableBranchAutocomplete();
        _stock_table.on("rowUpdated", applyUsedStockLock);

        // autocomplete plugins may initialize after this script
        $(window).on("load", function () {
            applyUsedStockLock();
            disableBranchAutocomplete();
        });

        // voucher can't be deleted / drafted when stock is used
        $("#btn_delete").remove();
        $('button[name="btn_draft"]').remove();
    });
</script>
<? } ?>
<? include_once("../includes/inc_footer.php"); ?>