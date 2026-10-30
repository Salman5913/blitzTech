<?
/**
 * Order Invoice Voucher (POS) - receipt print (80mm thermal)
 */
include_once("../connection/localhost.php");

$_GET['voucher_type_id'] = VoucherType::OIV;

$oSales = new Sales();
$oCompany = new Company();

$oSales->initForEdit();

$RowDetails = $oSales->getDetails();
$SelectInvoiceData = $oSales->getData(Sales::IND_DATA);
$RowCompanyDetails = $oCompany->getDetails(Company::getLoggedInCompanyId());

$VoucherName = VoucherType::getCode(VoucherType::OIV)."-".$oSales->getVoucherNumber();

$Items = [];
$GrossAmount = $TotalQty = 0;
while ($RowData = DBQM::fetch_assoc($SelectInvoiceData))
{
    $Qty = $RowData['qty'] - $RowData['qty_return'];
    $Amount = $Qty * ($RowData['selling_price'] - Utils::calcPercentValue($RowData['selling_price'], $RowData['item_disc'], 0));

    $Items[] = [
        'name' => !empty($RowData['stock_id']) ? Stock::getName($RowData['stock_id'], $RowData['attribute_id']) : $RowData['description'],
        'qty' => $Qty,
        'rate' => $RowData['selling_price'],
        'amount' => $Amount,
    ];

    $GrossAmount += $Amount;
    $TotalQty += $Qty;
}

$Discount = (float) $RowDetails['invoice_discount_calc_value'];
$Tax = (float) $RowDetails['gst_calc_value'];
$NetAmount = $GrossAmount - $Discount + $Tax + (float) $RowDetails['transport_amount'];

function posMoney($Value)
{
    return number_format((float) $Value, 2);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Receipt <?=$VoucherName?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?=ProjectHelper::SiteURL()?>assets/css/app-font.css?v=1" rel="stylesheet" type="text/css" />
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f2f4f7; color: #000; font-size: 12px; }
        .receipt { width: 80mm; margin: 12px auto; background: #fff; padding: 10px 12px 16px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .company { font-size: 16px; font-weight: 800; text-transform: uppercase; }
        .muted { font-size: 11px; }
        .dash { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 2px 0; vertical-align: top; }
        th { font-size: 11px; text-align: left; border-bottom: 1px dashed #000; padding-bottom: 4px; }
        /* long item names wrap inside item column, numbers stay aligned */
        table.items { table-layout: fixed; }
        table.items col.c-item { width: 44%; }
        table.items col.c-qty { width: 12%; }
        table.items col.c-rate { width: 20%; }
        table.items col.c-amt { width: 24%; }
        table.items td { padding: 4px 0; border-bottom: 1px dotted #bbb; }
        table.items td + td, table.items th + th { padding-left: 4px; }
        .item-name { font-weight: 600; white-space: normal; word-break: break-word; overflow-wrap: anywhere; line-height: 1.25; padding-right: 4px !important; }
        .grand td { font-size: 15px; font-weight: 800; padding-top: 4px; }
        .actions { width: 80mm; margin: 0 auto 12px; display: flex; gap: 8px; }
        .actions button { flex: 1; padding: 10px; border: 0; border-radius: 6px; font-weight: 700; cursor: pointer; }
        .btn-print { background: #0b3866; color: #fff; }
        .btn-close { background: #e4e7ec; color: #344054; }
        @media print {
            body { background: #fff; }
            .receipt { margin: 0; width: 100%; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="center">
        <div class="company"><?=htmlspecialchars($RowCompanyDetails['name'])?></div>
        <? if (!empty($RowCompanyDetails['address'])) { ?><div class="muted"><?=nl2br(htmlspecialchars($RowCompanyDetails['address']))?></div><? } ?>
        <? if (!empty($RowCompanyDetails['phone']) or !empty($RowCompanyDetails['mob1'])) { ?>
            <div class="muted">Ph: <?=htmlspecialchars(trim($RowCompanyDetails['phone'].' '.$RowCompanyDetails['mob1']))?></div>
        <? } ?>
        <? if (!empty($RowCompanyDetails['ntn'])) { ?><div class="muted">NTN: <?=htmlspecialchars($RowCompanyDetails['ntn'])?></div><? } ?>
    </div>

    <div class="dash"></div>
    <table class="muted">
        <tr><td>Receipt #</td><td class="right"><b><?=$VoucherName?></b></td></tr>
        <tr><td>Date</td><td class="right"><?=DateDecode($RowDetails['voucher_date'], 'd M Y h:i A')?></td></tr>
        <tr><td>Customer</td><td class="right"><?=htmlspecialchars(ChartOfAccount::getName($RowDetails['customer_account_id']))?></td></tr>
        <? if (!empty($RowDetails['mobile_number'])) { ?>
            <tr><td>Mobile</td><td class="right"><?=htmlspecialchars($RowDetails['mobile_number'])?></td></tr>
        <? } ?>
        <tr><td>Warehouse</td><td class="right"><?=htmlspecialchars(Branch::getName($RowDetails['branch_id']))?></td></tr>
    </table>
    <div class="dash"></div>

    <table class="items">
        <colgroup><col class="c-item"><col class="c-qty"><col class="c-rate"><col class="c-amt"></colgroup>
        <thead>
        <tr><th>Item</th><th class="right">Qty</th><th class="right">Rate</th><th class="right">Amount</th></tr>
        </thead>
        <tbody>
        <? foreach ($Items as $Item) { ?>
            <tr>
                <td class="item-name"><?=htmlspecialchars($Item['name'])?></td>
                <td class="right"><?=round($Item['qty'], 2)?></td>
                <td class="right"><?=posMoney($Item['rate'])?></td>
                <td class="right"><?=posMoney($Item['amount'])?></td>
            </tr>
        <? } ?>
        </tbody>
    </table>

    <div class="dash"></div>
    <table>
        <tr><td>Items / Qty</td><td class="right"><?=count($Items)?> / <?=round($TotalQty, 2)?></td></tr>
        <tr><td>Sub Total</td><td class="right"><?=posMoney($GrossAmount)?></td></tr>
        <? if ($Discount > 0) { ?><tr><td>Discount</td><td class="right">-<?=posMoney($Discount)?></td></tr><? } ?>
        <? if ($Tax > 0) { ?><tr><td><?=ProjectHelper::TAX_LABEL?></td><td class="right"><?=posMoney($Tax)?></td></tr><? } ?>
        <? if ($RowDetails['transport_amount'] > 0) { ?><tr><td>Delivery</td><td class="right"><?=posMoney($RowDetails['transport_amount'])?></td></tr><? } ?>
        <tr class="grand"><td>TOTAL <?=ProjectHelper::DEFAULT_CURRENCY?></td><td class="right"><?=posMoney($NetAmount)?></td></tr>
    </table>
    <div class="dash"></div>

    <div class="center muted">Thank you for your order!</div>
</div>

<div class="actions">
    <button type="button" class="btn-print" onclick="window.print()">Print Receipt</button>
    <button type="button" class="btn-close" onclick="window.close()">Cancel</button>
</div>

<? if (($_GET['autoprint'] ?? '') == 'Y') { ?>
<script>
    window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
</script>
<? } ?>
</body>
</html>
