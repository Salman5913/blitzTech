<?
include_once("../connection/localhost.php");

$oSales          = new Sales();
$oCompany        = new Company();
$oChartOfAccount      = new ChartOfAccount();

$oSales->initForEdit();

$RowDetails = $oSales->getDetails();
$SelectInvoiceData = $oSales->getData(Sales::IND_DATA);
$SelectDeliveryDetails = $oSales->getData(Sales::IND_DELIVERY);
$RowDeliveryDetails = DBQM::fetch_assoc($SelectDeliveryDetails);
$RowCompanyDetails = $oCompany->getDetails(Company::getLoggedInCompanyId());

$RowCustomerBioData = $oChartOfAccount->getAdditionalInfo($RowDetails['customer_account_id']);

$Heading =  'Sales Invoice';

$GrossAmount = 0;
$ExpenseAmount = 0;
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Sales Invoice </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?=ProjectHelper::SiteURL()?>assets/images/favicon.ico">
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/bootstrap.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/app.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/print.css" id="app-style" rel="stylesheet" type="text/css" />
</head>
<body data-layout="detached" data-topbar="colored" id="sales_print">
<style>
    @font-face {
        font-family: font-1;
        src: url(../assets/fonts/FontsFree-Net-Nordique-Pro-Bold.ttf);
    }
    @font-face {
        font-family: font-2;
        src: url(../assets/fonts/neue-radial-c-medium.ttf);
    }
    @font-face {
        font-family: font-3;
        src: url(../assets/fonts/TTChocolatesDemiBold.otf);
    }

    @media print {
        .a5 {
            height: 18cm;
            width: 40cm !important;
            size: A4 landscape !important;
            margin: auto;
            margin-top: -60px;
            font-size: 24px;
        }
    }

    table.table thead tr th {
        color: white;
        background-color: black;
        border: none;
    }
    table.table tbody tr td {
        border: none !important;
        padding: 10px 7px;
    }
    table.table tr th:first-child {
        border-radius: 5px 0 0 5px;
    }
    table.table tr th:last-child {
        border-radius: 0 5px 5px 0;
    }
    table.table thead {
        box-shadow: 5px 0px 9px 2px #d1d1d1;
        border-radius: 5px;
    }
    table.grey-bg {
        background-color: #f9f9f9;
        border-radius: 5px;
    }
    table.grey-bg td {
        padding: 10px 20px !important;
    }
    table.cus-header {
        width: 100% !important;
    }
    table.cus-header tbody {
        display: flex;
        flex-wrap: wrap;
    }
    table.cus-header tbody tr {
        flex-basis: 50%;
    }
    table.cus-header tbody tr td {
        display: block;
    }
    table.cus-header tbody tr:last-child {
        text-align: right;
    }
    table.cus-header tbody tr h1 {
        margin-bottom: 0;
        font-size: 80px;
    }


    table tr td{
        padding: 5px;
    }

    .ab_title {
        font-size: 20px;
        text-transform: uppercase;
        background: midnightblue;
        color: white;
        font-weight: bolder;
        height: 37px;
        width: 240px;
        text-align: center;
        line-height: 18px;
    }
    .ab_address {
        margin-top: 10px;
        font-size: 20px;
        width: 63%;
    }
    .invoice-logo {
        margin-bottom: -25px;
    }
    .invoice-logo>label {
        font-size: 55px;
        font-weight: bold;
        color: midnightblue;
    }
    .invoice-logo>span {
        font-size: 55px;
        color: midnightblue;
    }
    .sign {
        border-top: 1px solid;
        text-align: center;
        width: 229px;
        font-weight: bold;
    }
    .uan {
        font-size: 19px;
        font-weight: bolder;
        color: midnightblue;
    }
    .font-1{
        font-family: font-1 !important;
    }
    .font-2{
        font-family: font-2 !important;
    }
    .font-3{
        font-family: font-3 !important;
    }
</style>
<div class="row justify-content-center print-container">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="a5">
                    <table class="cus-header" style="">
                        <tr>
                            <td>
                                <strong>Company Name</strong>
                                <br>
                                (Company Address, City)
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h1>INVOICE</h1>
                                <p>#<?=$RowDetails['voucher_number']?></p>
                            </td>
                        </tr>

                    </table>
                    <table width="100%">
                        <tr>
                            <td width="15%">Date:</td>
                            <td  class="font-weight-bolder" width="50%"><?=DateDecode($RowDetails['voucher_date'], 'M d, Y')?> <?=date("h:i a", strtotime($RowDetails['voucher_date'])); ?></td>
                            <td>CNIC #:</td>
                            <td class="font-weight-bolder" ><?=$RowCustomerBioData['cnic']?></td>
<!--                            <td width="15%">Invoice Number:</td>-->
<!--                            <td  class="font-weight-bolder" width="20%">--><?//=$RowDetails['voucher_number']?><!--</td>-->
                        </tr>
                        <tr>
                            <td>Customer:</td>
                            <td class="font-weight-bolder">
                                <?=ChartOfAccount::getName($RowDetails['customer_account_id'])?>
                                <? if(!empty($RowDetails['name'])){ ?>(<?=$RowDetails['name']?>) <? }?>
                            </td>
                            <td >Mobile:</td>
                            <td class="font-weight-bolder"><?=$RowDetails['mobile_number']?></td>
                        </tr>
                    </table>
                    <table width="100%">
                        <tr>
                            <td><h3 class="font-size-16 font-weight-bold font-color">Stock Items</h3></td>
                        </tr>
                    </table>
                    <table class="table table-nowrap table-condensed table-bordered font-color">
                        <thead>
                        <tr>
                            <th class="text-center">No.</th>
                            <th>Stock Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Rate</th>
                            <th class="text-center">Disc. (%)</th>
                            <th class="text-right">Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?
                        $i=0;
                        while ($RowStock = DBQM::fetch_assoc($SelectInvoiceData)) {
                            $Qty = $RowStock['qty'] - $RowStock['qty_return'];
                            $Amount = $Qty * $RowStock['selling_price'];
                            $Discount = $Amount * $RowStock['item_disc'] / 100;
                            $DiscountWithAmount = $Amount - $Discount;
                            $GrossAmount += $DiscountWithAmount;
                            ?>
                            <tr>
                                <td class="text-center"><?=++$i?></td>
                                <td><?=!empty($RowStock['stock_id']) ? Stock::getName($RowStock['stock_id'], $RowStock['attribute_id']) : $RowStock['description']?></td>
                                <td class="text-center"><?=Utils::format($Qty, 2)?></td>
                                <td class="text-center"><?=Utils::format($RowStock['selling_price'], 0)?></td>
                                <td class="text-center"><?=Utils::format($RowStock['item_disc'], 0)?></td>
                                <td class="text-right"><?=Utils::format($DiscountWithAmount, 0); ?></td>
                            </tr>
                            <?
                        }
                        ?>
                        </tbody>
                    </table>
                    <?
                    $InvoiceAmount = $GrossAmount-$RowDetails['invoice_discount_calc_value']+$RowDetails['gst_calc_value'];
                    $TotalReceipt = 0;
                    $PreviousBalance = LedgerReport::getBalance($RowDetails['customer_account_id'], $RowDetails['voucher_date'], $RowDetails['voucher_type_id'],$RowDetails['voucher_number']);
                    $RemainingAmount = $InvoiceAmount + $TotalReceipt + $PreviousBalance;
                    ?>
                    <table class="table-sm grey-bg" width="100%">
                        <tr>
                            <td width="67%" rowspan="8">
                                <div class="note">
                                    <div>Note:</div>
                                    In case of any Incident during the delivery / transportation, the seller is not responsible for any claim.
                                </div>
                            </td>
                            <td width="20%" class="text-right">Gross Sales</td>
                            <td width="3%">&nbsp;</td>
                            <td width="10%" class="text-right"><?=Utils::format($GrossAmount,0)?></td>
                        </tr>
                        <tr class="<?=$RowDetails['invoice_discount_calc_value'] > 0 ? '' : 'hidden'?>">
                            <td class="border-0 text-right">Discount <?=$RowDetails['invoice_discount_percentage'] > 0 ? '('.round($RowDetails['invoice_discount_percentage']).'%)' : ''?></td>
                            <td>&nbsp;</td>
                            <td class="text-right border-0"><?=round($RowDetails['invoice_discount_calc_value'])?></td>
                        </tr>
                        <tr class="<?=$RowDetails['gst_calc_value'] > 0 ? '' : 'hidden'?>">
                            <td class="border-0 text-right"><?=ProjectHelper::TAX_LABEL?> <?='('.round($RowDetails['gst_percentage']).'%)'?></td>
                            <td>&nbsp;</td>
                            <td class="text-right border-0"><?=Utils::format($RowDetails['gst_calc_value'],0)?></td>
                        </tr>
                        <tr class="<?=$RowDetails['transport_amount'] > 0 ? '' : 'hidden'?>">
                            <td class="text-right border-0">Transport</td>
                            <td>&nbsp;</td>
                            <td class="text-right border-0"><?=Utils::format($RowDetails['transport_amount'],0)?></td>
                        </tr>
                        <tr class="<?=$InvoiceAmount > 0 ? '' : 'hidden'?>">
                            <td class="border-0 text-right">Invoice Total</td>
                            <td>&nbsp;</td>
                            <td class="border-0 text-right">
                                <?=Utils::format($InvoiceAmount,0)?>
                            </td>
                        </tr>
                        <tr class="<?=$PreviousBalance > 0 ? '' : 'hidden'?>">
                            <td class=" border-0 text-right">Previous Balance</td>
                            <td>&nbsp;</td>
                            <td class="text-right border-0"><?=Utils::format($PreviousBalance,0)?></td>
                        </tr>
                        <tr class="<?=$TotalReceipt > 0 ? '' : 'hidden'?>">
                            <td class="border-0 text-right"> Total Receipt</td>
                            <td>&nbsp;</td>
                            <td class="border-0 text-right"><?=Utils::format($TotalReceipt,0)?></td>
                        </tr>
                        <tr class="<?=$RemainingAmount > 0 ? '' : 'hidden'?>">
                            <td class="border-0 text-right"><strong>Remaining Balance</strong></td>
                            <td>&nbsp;</td>
                            <td class="border-0 text-right">
                                <h4 class="m-0 font-color"><?=Utils::format($RemainingAmount,0)?></h4>
                            </td>
                        </tr>
                    </table>
                    <table width="100%" class="footer-bottom" style="margin-top: 40px">
                        <tr><td></td></tr>
                        <tr align="right">
                            <td><div class="sign">Signature</div></td>
                        </tr>

                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function() {
        var page_height = 400;

        $(".spacer-dynamic").height(page_height - $(".print-page:first-child").height());
        window.print();
        setTimeout(function() {
            window.print();
            //window.location = '<?//=ProjectHelper::SiteURL()."sales/sales_voucher.php?voucher_type_id=".$_GET['voucher_type_id']?>//';
        }, 1000);
    });
</script>
</body>
</html>