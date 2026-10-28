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

$RowCustomerBioData = $oChartOfAccount->getAdditionalInfo($RowDetails['id']);
$HeadingSupplierSignature =  'Sales Invoice';
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
    <link href="<?=ProjectHelper::SiteURL()?>assets/css/app-font.css?v=1" rel="stylesheet" type="text/css" />
</head>
<body data-layout="detached" data-topbar="colored" id="sales_print">
<style>
    table tr td{
        padding: 5px;
    }
</style>
<div class="row justify-content-center print-container">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <table align="center" width="100%">
                            <tr>
                                <td class="text-center">
                                    <h2><?=$HeadingSupplierSignature?></h2>
                                    <div class="font-weight-bolder font-size-14">Delivery Challan</div>
                                </td>
                            </tr>
                        </table>
                        <table width="100%">
                            <tr>
                                <td width="15%">Date:</td>
                                <td  class="font-weight-bolder" width="50%"><?=DateDecode($RowDetails['voucher_date'], 'M d, Y')?> <?=date("h:i a", strtotime($RowDetails['voucher_date'])); ?></td>
                                <td width="15%">Invoice Number:</td>
                                <td  class="font-weight-bolder" width="20%"><?=$RowDetails['voucher_number']?></td>
                            </tr>
                            <tr>
                                <td>Customer:</td>
                                <td class="font-weight-bolder" ><?=ChartOfAccount::getName($RowDetails['customer_account_id'])?>
                                    <? if(!empty($RowDetails['name'])){ ?>(<?=$RowDetails['name']?>) <? }?>
                                </td>
                                <td >Mobile:</td>
                                <td class="font-weight-bolder"><?=$RowDetails['mobile_number']?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="py-2 mt-3">
                            <h3 class="font-size-16 font-weight-bold font-color">Stock Items</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-nowrap table-condensed font-color">
                                <thead>
                                <tr>
                                    <th class="text-center">No.</th>
                                    <th>Stock Item</th>
                                    <th class="text-center">Warehouse</th>
                                    <th class="text-center">Qty</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?
                                $i=0;
                                $TotalQty = 0;
                                while ($RowStock = DBQM::fetch_assoc($SelectInvoiceData)) {
                                    $Qty = $RowStock['qty'] - $RowStock['qty_return'];
                                    $TotalQty += $Qty;
                                    ?>
                                    <tr>
                                        <td class="text-center"><?=++$i?></td>
                                        <td><?=Stock::getName($RowStock['stock_id'], $RowStock['attribute_id'])?></td>
                                        <td class="text-center"><?=Branch::getName($RowDetails['branch_id'])?></td>
                                        <td class="text-center"><?=Utils::format($Qty, 2)?></td>
                                    </tr>
                                    <? } ?>
                                    <tr>
                                        <td colspan="3" class="text-right font-weight-bolder">Total Qty</td>
                                        <td class="text-center font-weight-bolder font-size-14"><?=Utils::format($TotalQty, 2)?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
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