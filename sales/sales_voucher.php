<?
include_once("../connection/localhost.php");

$oSales = new Sales();
$oBranch = new Branch();
$oChartOfAccount = new ChartOfAccount();

$oSales->initForEdit();

$VoucherTypeId = $oSales->getVoucherType();

$isReturnVoucherType = $VoucherTypeId == VoucherType::SRV;
$isOrderVoucherType = $VoucherTypeId == VoucherType::SOV;
$isHidden = $isOrderVoucherType ? 'style="display: none;"' : '';

$isEdit = $oSales->isEdit();

$SelectInvoiceData = $isEdit ? $oSales->getData(Sales::IND_DATA) : false;
$SelectNIInvoiceData = $isEdit ? $oSales->getData(Sales::IND_NON_INVENTORY) : false;
$SelectPaymentData = $isEdit ? $oSales->getData(Sales::IND_PAYMENT) : false;
$SelectDeliveryDetails = $isEdit ? $oSales->getData(Sales::IND_DELIVERY) : false;


$RowDetails = $isEdit ? $oSales->getDetails() : array();
$RowDeliveryDetails = DBQM::fetch_assoc($SelectDeliveryDetails);


$isVoucherSearched = isset($_GET['order_id']);
if ($isVoucherSearched)
{
    $SelectInvoiceData = $oSales->getRemainingStockFromOrder($_GET['order_id']);
}
if (in_array($VoucherTypeId, VoucherType::getSalesVoucherTypes(false)) and (!empty($_GET['order_id']) or !empty($RowDetails['sales_order_id'])))
{
    $OrderId = $oSales->isEdit() ? $RowDetails['sales_order_id'] : $_GET['order_id'];
    $LinkedTo = VoucherType::getName(VoucherType::SOV)." - ".$oSales->getVoucherNumberById($OrderId);
}

$isReceiptHidden = true;
if ($isEdit && $RowDetails['invoice_type'] == 'I')
    $isReceiptHidden = false;

$DataRows = DatabaseHelper::getRecordCount($SelectInvoiceData) + 1;
$NIDataRows = DatabaseHelper::getRecordCount($SelectNIInvoiceData) + 1;
$PaymentDataRows = DatabaseHelper::getRecordCount($SelectPaymentData) + 1;

$CustomerAccountId = isset($_GET['customer_id']) ? $_GET['customer_id'] : $RowDetails['customer_account_id'];

include_once("../includes/inc_header.php");
?>
<script>
var is_sales_voucher = <?=in_array($VoucherTypeId, VoucherType::getSalesVoucherTypes(false)) ? 'true' : 'false' ?>;
</script>
<script type="text/javascript" src="../js/sales/sales.js"></script>
<?php
if (in_array($VoucherTypeId, VoucherType::getSalesVoucherTypes(false)) and !$oSales->isEdit() and !$isVoucherSearched)
{
    ?>
    <form action="" method="get" <? Utils::formValidate(); ?> class="<?=Sales::isAvailableSalesOrders() > 0 ? '' : 'hidden'?>">
        <div class="row">
            <div class="col-lg-12">
                <div class="card bg-soft-warning">
                    <h3 class="card-title"> Select Sales Order Voucher</h3>
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="form-group">
                                <label for="supplier_id">Customer</label>
                                <div class="input-group">
                                    <input type="text" class="form-control col-md-12" id="customer_id" name="customer_id" data-js="search_customer_id" <? Utils::isRequired(); Autocomplete::ACSearchAccount($_GET['customer_id']); ?>>
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <a href="../chart_of_account/manage_accounts.php" target="_blank" title="Create New Customer"><i class="fa fa-user"></i></a>
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
                                <button type="submit" class="btn color form-control" name="search_requisition"><i class="mdi mdi-account-search"></i> Search</button>
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
<form method="post" id="sales" action="op_save_sales.php" <?Utils::formValidate();?>>
	<? $oSales->showVoucherDeleteNotification(); ?>
    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <h3 class="card-title"> Basic Details</h3>
                <div class="row main-details">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="customer_id">Customer</label>
                            <div class="input-group">
                                <input type="text" class="form-control col-md-12" id="customer_id" data-js="customer_id" name="customer_id" data-auto-select="Y" <? Utils::isRequired(); Autocomplete::ACSearchAccount($CustomerAccountId); ?>>
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <a href="javascript:void(0)" <? Utils::AjaxLoadedPopup("New Customer", ProjectHelper::SiteURL()."chart_of_account/ajax_save_account.php");?>><i class="fa fa-user"></i></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="voucher_date">Date</label>
                            <input type="text" name="voucher_date" id="voucher_date" data-type="date" value="<?=DateDecode($RowDetails['voucher_date'])?>" <? Utils::isRequired() ?>>
                        </div>
                    </div>
                    <div class="col-lg-2 hidden">
                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input type="text" name="due_date" id="due_date" data-type="date" value="<?=DateDecode($RowDetails['due_date'])?>" <? Utils::isRequired() ?>>
                        </div>
                    </div>
                    <div class="col-lg-2 hidden">
                        <div class="form-group">
                            <label for="delivery_date">Delivery Date</label>
                            <input type="text" name="delivery_date" id="delivery_date" data-type="date" value="<?=DateDecode($RowDetails['delivery_date'])?>" <? Utils::isRequired() ?>>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" value="<?=$RowDetails['name']?>" placeholder="Name">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="mobile_number">Mobile Number</label>
                            <input type="text" name="mobile_number" id="mobile_number" value="<?=$RowDetails['mobile_number']?>">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label for="sales_man_id">Salesman</label>
                            <input type="text" name="sales_man_id" id="sales_man_id" <? Autocomplete::ACSearchAccountByCOAId([getConstantCategory()->getSalesMan()], $RowDetails['sales_man_id']) ?> placeholder="Sales Man Account">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="_project_id">Project</label>
                            <input type="text" name="_project_id" id="_project_id" <? Autocomplete::Search(Autocomplete::TYPE_APPROVED_PROJECT, $RowDetails['project_id']); ?> >
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?=$RowDetails['description']?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card">
                <h3 class="card-title"> Invoice Status</h3>
                <div class="row">
                    <div class="col-12">
                        <? if (!empty($LinkedTo)) { ?>
                            <div class="form-group">
                                <label for="linked_to" class="font-weight-bold">Linked To</label>
                                <input type="text" id="linked_to" class="text-success font-weight-bold" readonly value="<?=$LinkedTo?>">
                            </div>
                        <? } ?>
                        <?php
                        $isHidden = $isOrderVoucherType ? 'style="display: none;"' : '';
                        ?>

                        <div class="form-group" <?= $isHidden ?>>
                            <label for="invoice_type">Invoice Type</label>
                            <select name="invoice_type" id="invoice_type" <?= Utils::isRequired() ?>>
                                <?php Utils::getDropDownOptionsFromArray([
                                    Sales::TYPE_INVOICE=> 'Delivered & Invoiced',
                                    Sales::TYPE_DELIVERY => 'Delivered Only'
                                ], $RowDetails['invoice_type']) ?>
                            </select>
                        </div>

                        <div class="form-group" <?= $isHidden ?>>
                            <label for="branch_id">Warehouse</label>
                            <input type="text" name="branch_id" id="branch_id" data-js="branch_id"
                                <?php Autocomplete::Search(Autocomplete::TYPE_BRANCH, $RowDetails['branch_id']); ?>
                                <?= Utils::isRequired() ?>>
                        </div>

                        <div class="form-group" <?= $isHidden ?>>
                            <label for="print_dc">Print with DC?</label>
                            <select name="print_dc" id="print_dc">
                                <?php Utils::getDropDownOptionsFromArray(['N' => 'No', 'Y' => 'Yes'], '') ?>
                            </select>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row hidden">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Non Inventory</h3>
                <table class="table color dynamic non_inventory table-bordered">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <? if (!$isReturnVoucherType) { ?>
                                <th class="text-center">Qty&nbsp;</th>
                            <? } else { ?>
                                <th class="text-center">Qty Return</th>
                            <? } ?>
                            <th class="text-center">Selling Price&nbsp;&nbsp;</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td>&nbsp;</td>
                            <? if (!$isReturnVoucherType) { ?>
                                <td class="font-weight-bolder text-center" <? Utils::sumInputs('[data-js="ni_qty"]') ?>>&nbsp;</td>
                            <? } else { ?>
                                <td class="font-weight-bolder text-center" <? Utils::sumInputs('[data-js="ni_qty_return"]') ?>>&nbsp;</td>
                            <? } ?>
                            <td>&nbsp;</td>
                            <td class="font-weight-bolder text-right ni_total_rs" <? Utils::sumInputs('.ni_total_row', false) ?>>&nbsp;</td>
                        </tr>
                    </tfoot>
                    <tbody>
                    <? $i=0; while ($RowNIData = DBQM::fetch_assoc($SelectNIInvoiceData) or $i < $NIDataRows)
                    {
                        ?>
                            <tr>
                                <td><label><textarea id="item_description" name="item_description[]" data-js="item_description"><?=$RowNIData['item_description']?></textarea></label></td>
                                <? if (!$isReturnVoucherType) { ?>
                                    <td><label><input type="text" class="form-control" size="5" name="ni_qty[]" data-type="float" data-js="ni_qty" value="<?=$RowNIData['qty']?>" /></label></td>
                                <? } else { ?>
                                    <td><label><input type="text" class="form-control" size="5" name="ni_qty_return[]" data-type="float" data-js="ni_qty_return" value="<?=$RowNIData['qty_return']?>" /></label></td>
                                <? } ?>
                                <td><label><input type="text" class="form-control" size="5" name="ni_selling_price[]" data-js="ni_rate" data-type="float" value="<?=$RowNIData['selling_price']?>"/></label></td>
                                <td class="text ni_total_row text-right"></td>
                            </tr>
                        <? $i++; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Items</h3>
                <table class="table color dynamic stock table-bordered">
                    <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Description</th>
                        <th>Available Qty</th>
                        <? if (!$isReturnVoucherType) { ?>
                            <th class="text-center">Qty&nbsp;</th>
                        <? } else { ?>
                            <th class="text-center">Qty Return</th>
                        <? } ?>
                        <th class="text-center">Selling Price&nbsp;&nbsp;</th>
                        <th class="text-center">Item Disc. %</th>
                        <th class="text-right">Total</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <? if (!$isReturnVoucherType) { ?>
                            <td class="font-weight-bolder text-center" <? Utils::sumInputs('[data-js="qty"]') ?>>&nbsp;</td>
                        <? } else { ?>
                            <td class="font-weight-bolder text-center" <? Utils::sumInputs('[data-js="qty_return"]') ?>>&nbsp;</td>
                        <? } ?>
                        <td>&nbsp;</td>
                        <td class="">&nbsp;</td>
                        <td class="font-weight-bolder text-right total_rs" <? Utils::sumInputs('.total_row', false) ?>>&nbsp;</td>
                    </tr>
                    </tfoot>
                    <tbody>
                    <? $i=0; while ($RowData = DBQM::fetch_assoc($SelectInvoiceData) or $i < $DataRows)
                    {
                        ?>
                        <tr>
                            <td><label><input type="text" name="stock_id[]" id="stock_id" <? Autocomplete::ACSearchStockRow($RowData['stock_id'], $RowData['attribute_id'],'[name="branch_id"]', '[data-js="qty-available"]', '[data-js="qty"]', '[data-js="rate"]',false) ?>></label></td>
                            <td><label><textarea id="item_detail" name="item_detail[]" data-js="item_detail"><?=$RowData['description']?></textarea></label></td>
                            <td data-js="qty-available" class="text text-center"> </td>
                            <? if (!$isReturnVoucherType) { ?>
                                <td><label><input type="text" class="form-control" size="5" name="qty[]" data-type="float" data-js="qty" value="<?=$RowData['qty']?>" /></label></td>
                            <? } else { ?>
                                <td><label><input type="text" class="form-control" size="5" name="qty_return[]" data-type="float" data-js="qty_return" value="<?=$RowData['qty_return']?>" /></label></td>
                            <? } ?>
                            <td><label><input type="text" class="form-control" size="5" name="selling_price[]" data-js="rate" data-type="float" value="<?=$RowData['selling_price']?>"/></label></td>
                            <td><label><input type="text" class="form-control" size="5" name="discount_percentage[]" data-type="float" data-js="discount_percentage" value="<?=$RowData['item_disc']?>" /></label></td>
                            <td class="text total_row text-right"></td>
                        </tr>
                        <? $i++; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <div class="card <?=VoucherType::SRV == $VoucherTypeId ? 'hidden' : ''?>">
                <h3 class="card-title"> Delivery Details</h3>
                <div class="row">
                    <div class="col-xl-6 col-lg-6">
                        <div class="form-group">
                            <label for="delivery_name">Name</label>
                            <input type="text" name="delivery_name" id="delivery_name" value="<?=$RowDeliveryDetails['contact_name']?>">
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6">
                        <div class="form-group">
                            <label for="delivery_phone">Phone</label>
                            <input type="text" name="delivery_phone" id="delivery_phone" value="<?=$RowDeliveryDetails['phone']?>">
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6">
                        <div class="form-group">
                            <label for="delivery_address">Address</label>
                            <textarea name="delivery_address" id="delivery_address"><?=$RowDeliveryDetails['address']?></textarea>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6">
                        <div class="form-group">
                            <label for="delivery_desc">Delivery Reference</label>
                            <textarea name="delivery_desc" id="delivery_desc"><?=$RowDeliveryDetails['description']?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="card sales_calc">
                <div class="row">
                    <div class="col-auto mr-auto">
                        <h3 class="card-title"> Sales Details</h3>
                    </div>
                    <div class="col-auto ml-auto"></div>
                </div>
                <div class="row">
                    <label class="col-auto col-form-label">Total Non Inventory</label>
                    <div class="col-auto col-form-label ml-auto total_non_inventory">0.00</div>
                </div>
                <div class="row">
                    <label class="col-auto col-form-label">Total Inventory</label>
                    <div class="col-auto col-form-label ml-auto total_inventory">0.00</div>
                </div>
                <div class="row">
                    <label class="col-auto col-form-label font-weight-bold">Gross Sales</label>
                    <div class="col-auto col-form-label ml-auto total_gross_sales font-weight-bold">0.00</div>
                </div>
                <div class="row border-top pt-1 pb-3 pb-md-1">
                    <label class="col-md-4 col-form-label" for="invoice_discount_amount">Invoice Discount</label>
                    <div class="col-8 col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control col-8" size="5" data-type="float" name="invoice_discount_amount" id="invoice_discount_amount" value="<?=$RowDetails['invoice_discount_amount']?>">
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
                            <input type="text" class="form-control col-8" data-type="float" name="invoice_discount_percentage" id="invoice_discount_percentage" size="5" value="<?=$RowDetails['invoice_discount_percentage']?>">
                            <div class="input-group-prepend">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row border-top pt-1 pb-3 pb-md-1">
                    <label class="col-md-4 col-form-label" for="gst_percentage"><?=ProjectHelper::TAX_LABEL?></label>
                    <div class="col-8 col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control col-8" data-type="float" size="5" name="gst_percentage" id="gst_percentage" value="<?=$RowDetails['gst_percentage']?>">
                            <div class="input-group-prepend">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 text-right col-form-label col-md-4 ml-auto total_gst_tax">0.00</div>
                </div>
                <div class="row border-top pt-1 pb-3 pb-md-1">
                    <label class="col-md-4 col-form-label" for="transport_amount">Transport Amount</label>
                    <div class="col-8 col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control col-8" data-type="float" size="5" name="transport_amount" id="transport_amount" value="<?=$RowDetails['transport_amount']?>">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><?=ProjectHelper::DEFAULT_CURRENCY?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 text-right col-form-label col-md-4 ml-auto total_transport_amount">0.00</div>
                </div>
                <div class="row border-top bg-success text-white">
                    <label class="col-auto col-form-label font-weight-bold">Invoice Total</label>
                    <div class="col-auto col-form-label ml-auto font-weight-bold total_sales">0.00</div>
                </div>
                <div class="row border-top">
                    <label class="col-auto col-form-label">Previous Balance</label>
                    <div class="col-auto col-form-label ml-auto previous_balance">0.00</div>
                </div>
                <div class="row border-top">
                    <label class="col-auto col-form-label">Total Receipts</label>
                    <div class="col-auto col-form-label ml-auto total_receipts">0.00</div>
                </div>
                <div class="row border-top bg-light">
                    <label class="col-auto col-form-label font-weight-bold">Remaining Balance</label>
                    <div class="col-auto col-form-label ml-auto font-weight-bold remaining_balance">0.00</div>
                </div>
                <div class="row border-top text-danger">
                    <label class="col-auto col-form-label font-weight-bold">Credit Limit</label>
                    <div class="col-auto col-form-label ml-auto font-weight-bold credit_limit">0.00</div>
                </div>
            </div>
        </div>
    </div>
    <div class="row receipt_panel <?=$isReceiptHidden ? 'hidden' : ''?> <?php /*=in_array($VoucherTypeId, VoucherType::getSalesVoucherTypes(false)) ? '' : 'hidden'*/?>">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Receipts</h3>
                <table class="table color dynamic payment table-bordered">
                    <thead>
                    <tr>
                        <th width="25%">Cash / Bank Name</th>
                        <th width="15%" class="text-center">Amount Received</th>
                        <th width="15%">Cheque Number</th>
                        <th width="15%">Cheque Date</th>
                        <th width="30%">Description</th>
                        <td width="10%">Total</td>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <td>&nbsp;</td>
                        <td class="font-weight-bolder text-center">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="text-bold text-center" <? Utils::sumInputs('.total_payment_row', false); ?>>&nbsp;</td>
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
                            <td class="text-right font-weight-bold text total_payment_row"></td>
                        </tr>
                        <? $i++; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <? $oSales->showVoucherButtons(); ?>

    <input type="hidden" name="sales_order_id" id="sales_order_id" value="<?=$OrderId?>">
    <input type="hidden" id="prev_balance" value="0">
    <input type="hidden" id="total_invoice_amount" name="total_invoice_amount" value="">
    <input type="hidden" id="calc_discount" name="calc_discount" value="">
    <input type="hidden" id="calc_gst" name="calc_gst" value="">
    <input type="hidden" id="calc_advance_tax" name="calc_advance_tax" value="">
    <input type="hidden" id="credit_limit" value="">
</form>
<? include_once("../includes/inc_footer.php"); ?>