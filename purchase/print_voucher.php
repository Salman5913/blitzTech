<?
include_once("../connection/localhost.php");

$oPurchase = new Purchase();
$oStock = new Stock();
$oPurchase->initForEdit();

$RowDetails = $oPurchase->getDetails();
$SelectInvoiceData = $oPurchase->getData(Purchase::IND_DATA);

$FinalAmount = 0;

$VoucherName = VoucherType::getName($oPurchase->getVoucherType()).' - #'.$oPurchase->getVoucherNumber();
function emptyVal($Value){
    return empty($Value) ? '&nbsp;' : $Value;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Purchase Invoice </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?=ProjectHelper::SiteURL()?>assets/images/favicon.ico">
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/bootstrap.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/app.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/print.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>assets/css/app-font.css?v=1" rel="stylesheet" type="text/css" />
</head>
<body data-layout="detached" data-topbar="colored" id="sales_print">

<style>
    @page {
        size: portrait;
    }
    @media print {
        body{
            width: 6in;
            height: 8in;
        }
        table, .table{
            font-size: 15px;
            width: 76%;
            margin-left: -22px;
            zoom: 90%;
        }
    }
    .title-cp-name{
        font-size: 42px;
        font-weight: bolder;
        color: #808080;
        margin: 0px;
    }
    .title-cp-desc{
        font-size: 14px;
        font-weight: bolder;
        margin-top: -14px;
    }


    .title-border {
        border: 1px solid;
        padding: 4px 50px 4px 50px;
        font-size: 15px;
        font-weight: bolder;
    }

    .title-info{
        width: 100%;
        text-align: center;
        background: grey;
        color: white;
        font-weight: bolder;
        padding: 2px 0 2px 0;
        border: 1px solid grey;
    }
    td.title-info-sm{
        max-width: 500px !important;
        border: 1px solid grey;
        border-bottom: 1px solid white;
        text-align: center;
        background: grey;
        color: white;
        font-weight: bolder;
        font-size: 15px;
        padding: 3px 0 3px 0;
    }
    td.title-info-bg-default{
        width: 100%;
        border: 1px solid;
        text-align: center;
        font-weight: bolder;
        font-size: 12px;
        padding: 7px 0 3px 0;
    }
    td.bill-to{
        width: 65%;
    }
    td.empty{
        width: 5%;
    }
    td.title-1{
        width: 15%;
    }
    td.title-md{
        width: 12%;
    }
</style>
<div class="row justify-content-center print-container">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <table width="100%">
                    <tr>
                        <td class="text-center">
                            <div class="title-cp-desc"><?=$VoucherName?></div>
                        </td>
                    </tr>
                </table>
                <table width="100%" style="margin-top: 10px;">
                    <tr>
                        <td class="bill-to"><label class="title-info">Bill To</label></td>
                        <td class="empty"></td>
                        <td class="title-1 title-info-sm">Invoice #</td>
                        <td class="title-1 title-info-bg-default"><?=str_pad($RowDetails['voucher_number'],5,'0', STR_PAD_LEFT)?></td>
                    </tr>
                </table>
                <table width="100%">
                    <tr>
                        <td class="title-md">Supplier : </td>
                        <td colspan="3"><?=ChartOfAccount::getName($RowDetails['supplier_account_id'])?></td>
                        <td class="empty"></td>
                        <td class="title-1 title-info-sm">Invoice Date</td>
                        <td class="title-1 title-info-bg-default"><?=DateDecode($RowDetails['voucher_date'])?></td>
                    </tr>
                </table>
                <table width="100%">
                    <tr>
                        <td class="title-md">Reference : </td>
                        <td><?=emptyVal($RowDetails['description'])?></td>
                        <td class="title-md"></td>
                        <td></td>
                        <td class="empty"></td>
                        <td class="title-1 title-info-sm">Godown</td>
                        <td class="title-1 title-info-bg-default"><?=Branch::getName($RowDetails['branch_id'])?></td>
                    </tr>
                </table>
                <table class="table table-nowrap table-condensed table-bordered font-color" style="margin-top: 15px">
                    <thead>
                    <tr>
                        <th width="5%"  class="text-center">#</th>
                        <th width="45%" >Item Name</th>
                        <th width="5%"  class="text-center">Qty</th>
                        <th width="5%"  class="text-center">Rate</th>
                        <th width=10%"  class="text-right">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?
                    $i=1;
                    while ($RowData = DBQM::fetch_assoc($SelectInvoiceData))
                    {
                        $Qty   = $RowData['qty'] - $RowData['qty_return'];
                        $Rate  = $RowData['rate'];

                        $Amount = $Qty * $Rate;
                        $DiscountRate = $RowData['item_disc'] > 0 ? Utils::calcPercentValue($Amount, $RowData['item_disc'],0) : 0;
                        $RowAmount = $Amount - $DiscountRate;
                        $GrossAmount += $RowAmount;
                        ?>
                        <tr>
                            <td class="text-center"><?=$i;?></td>
                            <td><?=Stock::getName($RowData['stock_id'], $RowData['attribute_id'])?></td>
                            <td class="text-center"><?=round($Qty,0)?></td>
                            <td class="text-center"><?=Utils::format($RowData['rate'],0);?></td>
                            <td class="text-right"><?=Utils::format($RowAmount, 0);?></td>
                        </tr>
                        <? ++$i;
                    }
                    $Count = DatabaseHelper::getRecordCount($SelectInvoiceData);
                    for ($i=0; $i<=8-$Count; $i++){
                        ?>
                        <tr>
                            <td>&nbsp;</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?
                    }
                    ?>
                    </tbody>
                </table>
                <?
                $InvoiceAmount = $GrossAmount - $RowDetails['invoice_discount_calc_value'];
                $PreviousBalance = $Balance > 0 ? $Balance : 0;
                $FinalAmount = $InvoiceAmount  + $RowDetails['gst_calc_value'] + $RowDetails['advance_tax_calc_value'] + $PreviousBalance;
                ?>
                <table width="100%">
                    <tr>
                        <td width="60%" rowspan="6">
                            <label class="font-size-14 font-weight-bolder">Note:</label><br/>
                            If invoice is signed and stamped ,  company will not be responsible for any missing/damaged pcs.
                        </td>
                        <td width="24%" class="text-right">Gross Sales</td>
                        <td width="1%">&nbsp;</td>
                        <td width="15%" class="text-right"><?=Utils::format($GrossAmount,0)?></td>
                    </tr>
                    <tr class="hidden">
                        <td class="border-0 text-right">Discount <?=$RowDetails['invoice_discount_percentage'] > 0 ? '('.round($RowDetails['invoice_discount_percentage']).'%)' : ''?></td>
                        <td>&nbsp;</td>
                        <td class="text-right border-0"><?=round($RowDetails['invoice_discount_calc_value'])?></td>
                    </tr>
                    <tr class="<?=$RowDetails['invoice_discount_percentage'] > 0 ? '' : 'hidden'?>">
                        <td class="border-0 text-right">Gross Total</td>
                        <td>&nbsp;</td>
                        <td class="border-0 text-right">
                            <?=Utils::format($InvoiceAmount,0)?>
                        </td>
                    </tr>
                    <tr class="<?=$RowDetails['invoice_gst_percentage'] > 0 ? '': 'hidden'?>">
                        <td class="border-0 text-right">GST <?='('.round($RowDetails['invoice_gst_percentage']).'%)'?></td>
                        <td>&nbsp;</td>
                        <td class="text-right border-0"><?=Utils::format($RowDetails['gst_calc_value'],0)?></td>
                    </tr>
                    <tr class="<?=$RowDetails['advance_tax_percentage'] > 0 ? '': 'hidden'?>">
                        <td class="border-0 text-right">Advance tax <?='('.round($RowDetails['advance_tax_percentage']).'%)'?></td>
                        <td>&nbsp;</td>
                        <td class="text-right border-0"><?=Utils::format($RowDetails['advance_tax_calc_value'],0)?></td>
                    </tr>
                    <tr>
                        <td class="border-0 text-right"><strong>Final Amount</strong></td>
                        <td>&nbsp;</td>
                        <td class="border-0 text-right">
                            <h4 class="m-0 font-color"><?=Utils::format($FinalAmount,0)?></h4>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<? $Content = ob_get_contents(); ob_end_clean(); ?>

<? echo $Content ?>
<? //echo str_replace("<h4>Customer Copy</h4>", "<h4>Office Copy</h4>", $Content); ?>
<!--<script>$(function() { setTimeout(function() {window.print(); window.close();}, 1000); })</script>-->
<script>
    window.print();
    $(function() {
        var page_height = 400;
        $(".spacer-dynamic").height(page_height - $(".print-page:first-child").height());

        setTimeout(function() {
            window.print();
            window.location = '<?=ProjectHelper::SiteURL()."sales/sales_voucher.php?voucher_type_id=".$_GET['voucher_type_id']?>';
        }, 1000);
    });
</script>
</body>
</html>