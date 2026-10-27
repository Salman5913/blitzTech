<?php
include_once ("../connection/localhost.php");

/** @var BasicVoucher $oBasicVoucher */

$oBasicVoucher = new $_GET['voucher_class']();
$oBasicVoucher->initForEdit();
$isArchive = isset($_GET['__archive_id']);

if (isset($_GET['__archive_view']))
    $oVoucherPrint = new BasicVoucherArchivePrint($oBasicVoucher, $_GET['__archive_id']);
else
    $oVoucherPrint = new BasicVoucherPrint($oBasicVoucher);

$RowMainTableData = $oVoucherPrint->getMainTableData();
$DataTableData = $oVoucherPrint->getDataTableData();

$MainTableDataAdditionalFormatting = $oVoucherPrint->getMainTableDataAdditionalFormatting();
$DataTableDataAdditionalFormatting = $oVoucherPrint->getDataTableDataAdditionalFormatting();

include_once ("../includes/inc_popup_header.php");
?>
<link rel="stylesheet" href="../assets/css/app.css">
<div class="row voucher_print">
    <div class="col-12 mb-3">
        <div class="row">
            <div class="col-2"><img src="../assets/images/blitztech.png" class="img-fluid" style="max-width: 160px;"></div>
            <div class="col-8 align-content-center text-center"><h3 class="popup-header_voucher"><?=VoucherType::getName($oVoucherPrint->oVoucher->getVoucherType())?></h3></div>
            <div class="col-2">
                <h4><?=VoucherType::getCode($oVoucherPrint->oVoucher->getVoucherType())?>-<?=$oVoucherPrint->oVoucher->getVoucherNumber() ?></h4>
                <div class="date"><?=DateDecode($oVoucherPrint->getDistinctIdData('voucher_date'), 'M d, Y')?></div>
            </div>
        </div>
        <? if ($isArchive) { ?>
            <div class="row">
                <div class="col-12 text-uppercase text-center">
                    <h4 class="mt-1 mb-1">From Archive - Not Actual Voucher</h4>
                </div>
            </div>
        <? } ?>
        <div class="row mt-3 mb-4 main-table-data <?=empty($DataTableData) ? 'main-table-bordered' : '' ?>">
            <?

            foreach ($RowMainTableData as $Key => $Value)
            {
                if (is_array($Value)) continue;

                list($LabelColumnClass, $DataColumnClass) = $oVoucherPrint->oVoucherPrintUtils->getMainColumnClass($Key);
                ?>
                    <div class="<?=empty($LabelColumnClass) ? 'col-2' : $LabelColumnClass;?> <?=$MainTableDataAdditionalFormatting[$Key]?> pt-1 font-weight-bold main-table-label <?=$Key?>_label"><?=$oVoucherPrint->makeReadableColumnName($Key)?></div>
                    <div class="<?=empty($DataColumnClass) ? 'col-4' : $DataColumnClass;?> pt-1 main-table-data <?=$Key?>_data"><?=$oVoucherPrint->makeReadableColumnValue($Key, $Value)?></div>
                <?
            } ?>
        </div>
        <? foreach ($DataTableData as $TableKey => $TableName) { ?>
            <div class="row">
                <table class="table table-striped table-condensed table-bordered">
                    <thead>
                        <tr>
                            <? foreach ($TableName[0] as $Column => $Value) { if (is_array($Value)) continue;?>
                                <th <? $oVoucherPrint->getColumnWidth($Column) ?> class="font-weight-bold <?=$oVoucherPrint->getClassByColumnDataType($Column)?>"><?=$oVoucherPrint->makeReadableColumnName($Column)?></th>
                            <? } ?>
                        </tr>
                    </thead>
                    <? foreach ($TableName as $Key => $RowValues) { ?>
                        <tr>
                            <? foreach ($RowValues as $Column => $Value) {  if (is_array($Value)) continue; ?>
                                <td <? $oVoucherPrint->isTotalRequired($Column); ?> class="<?=$oVoucherPrint->getClassByColumnDataType($Column)?> <?=$DataTableDataAdditionalFormatting[$TableKey][$Key][$Column]?>"><?=$oVoucherPrint->makeReadableColumnValue($Column, $Value)?></td>
                            <? } ?>
                        </tr>
                    <? } ?>
                    <tfoot class="font-weight-semibold">
                        <tr>
                            <? foreach ($TableName[0] as $Column => $Value) { if (is_array($Value)) continue;?>
                                <td <? $oVoucherPrint->isTotalRequired($Column, false); ?> class="text-center"></td>
                            <? } ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <? } ?>
    </div>
    <? if ($oVoucherPrint->oVoucherPrintUtils->isSignatureRequire()) { ?>
        <div class="col-12 mb-3">
            <div class="row">
                <div class="col-8"></div>
                <div class="col-4 text-center">
                    <hr class="mt-5" style="border-color: black">
                    Signature
                </div>
            </div>
        </div>
    <? } ?>
    <? $oVoucherPrint->footer(); ?>
    <div class="popup-header hidden"><?=$oVoucherPrint->getPageTitle()?></div>
</div>
<script>
$(function () {
    $("[data-total]").each(function () {
        var column_name = $(this).attr("data-total");

        var total = 0;
        $("[data-js='"+column_name+"']").each(function () {
            var value = $(this).html();

            if (value === "-")
                value = "0";

            value = value.replaceAll(",", "");
            if (value.indexOf("(") !== -1)
            {
                value = value.replace("(", "");
                value = value.replace(")", "");
                value = "-" + value;
            }

            //var value = AutoNumeric.unformat($(this)[0]);
            total += (value === '' ? 0 : parseFloat(value));
        });

        $(this).html(NumberHelper.thousandSep(total));
    });

    setTimeout(function () {
        window.print()
    }, 500);
});

function round(value)
{
    var n = parseFloat(value);
    if (!isNaN(n))
        return n.toFixed(2);
    else
        return 0.00;
}

function thousandSep(input)
{
    var output = round(input);
    var isLessThanZero;

    if (parseFloat(input)) {
        isLessThanZero = input < 0;
        input = Math.abs(input);

        input = input.toString();
        var parts = input.split(".");
        parts[0] = parts[0].split("").reverse().join("").replace(/(\d{3})(?!$)/g, "$1,").split("").reverse().join("");
        output = parts.join(".");
    }

    return isLessThanZero ? "("+output+")" : output;
}

//window.print();
</script>
<?php
include_once ("../includes/inc_popup_footer.php");