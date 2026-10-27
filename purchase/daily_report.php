<?
$_GET['_page_title'] = 'Datewise Purchase Report';
$_GET['_page_title_text'] = 'Complete report for daily Purchases';

include_once("../includes/inc_header.php");

$oPurchase = new PurchaseReport();
if (isset($_GET['search']))
{
    $Select = $oPurchase->getDateWiseReport($_GET['date_from'], $_GET['date_till'], $_GET['supplier_id'], $_GET['stock_id'], $_GET['branch_id'], $_GET['category_id'], $_GET['brand'],  $_GET['invoice_type']);
    $PrintDescription = "Datewise Daily Report : ".DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y');
}
?>
<form action="" method="get" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-xl-5 col-md-6">
                        <div class="form-group mb-0">
                            <label for="date">Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] == '' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] == '' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="supplier_id">Supplier Name</label>
                            <input type="text" name="supplier_id" id="supplier_id" <? Autocomplete::ACSearchAccount($_GET['supplier_id']); ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="stock_id">Stock Item</label>
                            <input type="text" id="stock_id" name="stock_id"  <? Autocomplete::ACSearchStock($_GET['stock_id'], ''); ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <input type="text" id="category_id" name="category_id"  <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $_GET['category_id']); ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <input type="text" id="brand" name="brand"  <? Autocomplete::Search(Autocomplete::TYPE_STOCK_BRAND, $_GET['brand']); ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="branch_id">Warehouse</label>
                            <input type="text" id="branch_id" name="branch_id"  <? Autocomplete::Search(Autocomplete::TYPE_BRANCH, $_GET['branch_id']); ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="invoice_type">Inventory / Non Inventory</label>
                            <select id="invoice_type" name="invoice_type"><? Utils::getDropDownOptionsFromArray(['' => '', 'I' => 'Inventory', 'N' => 'Non Inventory'], $_GET['invoice_type']);?></select>
                        </div>
                    </div>
                    <div class="col-xl-12 text-right">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="row">
                    <div class="col-sm-6"><h3 class="card-title"> Search Results</h3></div>
                    <div class="col-sm-6 text-right"><? UtilsPrint::button(); ?></div>
                </div>
                <table id="datatable" class="table color table-striped table-bordered dt-responsive nowrap <? UtilsPrint::makePrintable(); ?>">
                    <thead>
                        <tr>
                            <th class="text-center">Inv #</th>
                            <th>Date</th>
                            <th>Item Name</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Rate</th>
                            <th class="text-center">Disc. Rate</th>
                            <th class="text-center">Total</th>
                            <th class="text-center"><?=ProjectHelper::TAX_LABEL?></th>
                            <th class="text-center">Total W/Tax</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?
                    $PrevPurchaseId = $PurchaseId = 0;
                    $PrevPurchaseTotal = [];
                    $Items = [];
                    $Total = [];
                    $InvoiceCount = 0;
                    $Count = 0;

                    while ($Row = DBQM::fetch_assoc($Select))
                    {
                        $Qty = $Row['qty'] - $Row['qty_return'];
                        $Rate = $Row['discounted_rate'];

                        $Multiplier = $Row['voucher_type_id'] == VoucherType::PRV ? -1 : 1;
                        $RowTotal = $Rate * $Qty;

                        $GST = $RowTotal * ($Row['invoice_gst_percentage'] / 100);
                        $RowTotalWithGST = $GST + $RowTotal;

                        $PurchaseId = $Row['purchase_id'];
                        $isPartOfPrevPurchase = $PurchaseId == $PrevPurchaseId;

                        $InvoiceNumber = $Row['voucher_prefix'].'-'.$Row['voucher_number'];

                        if (!$isPartOfPrevPurchase and $PrevPurchaseId != 0)
                        {
                            ?>
                            <tr class="font-weight-bolder group-total">
                                <td> </td>
                                <td class="text-right">No. of Items : </td>
                                <td><?=Utils::format($PrevPurchaseTotal['items'], 0)?></td>
                                <td class="text-right"><?=Utils::format($PrevPurchaseTotal['qty'])?></td>
                                <td class="text-right">&nbsp;</td>
                                <td colspan="2" class="text-right"><?=Utils::format($PrevPurchaseTotal['total'])?></td>
                                <td class="text-right"><?=Utils::format($PrevPurchaseTotal['gst'])?></td>
                                <td class="text-right"><?=Utils::format($PrevPurchaseTotal['total_gst'])?></td>
                            </tr>
                            <tr>
                                <td colspan="7">&nbsp;</td>
                            </tr>
                            <?
                            $PrevPurchaseTotal = [];
                        }

                        if (!$isPartOfPrevPurchase)
                            $InvoiceCount++;

                        $PrevPurchaseTotal['items'] += $Multiplier;
                        $PrevPurchaseTotal['qty'] += $Qty;
                        $PrevPurchaseTotal['total'] += $RowTotal;
                        $PrevPurchaseTotal['gst'] += $GST;
                        $PrevPurchaseTotal['total_gst'] += $RowTotalWithGST;

                        $Total['items'] = $Count;
                        $Total['qty'] += $Qty;
                        $Total['total'] += $RowTotal;
                        $Total['gst'] += $GST;
                        $Total['total_gst'] += $RowTotalWithGST;
                        ?>
                        <tr>
                            <td class="text-center"><? if (!$isPartOfPrevPurchase) { ?><?=$InvoiceNumber?> <a href="<?=VoucherType::getLink($Row['voucher_type_id'], $Row['purchase_id'])?>" target="_blank"><i class="mdi mdi-pencil"></i></a><? } ?></td>
                            <td><?=$isPartOfPrevPurchase ? '' : DateDecode($Row['voucher_date'], 'M d, Y')."<br>".$Row['supplier_name']?></td>
                            <td><?=empty($Row['item_name']) ? $Row['item_description'] : $Row['item_name']?></td>
                            <td class="text-right"><?=Utils::format($Qty)?></td>
                            <td class="text-right"><?=Utils::format($Row['rate'])?></td>
                            <td class="text-right"><?=Utils::format($Row['discounted_rate'], 2)?></td>
                            <td class="text-right"><?=Utils::format($RowTotal)?></td>
                            <td class="text-right"><?=Utils::format($GST)?></td>
                            <td class="text-right"><?=Utils::format($RowTotalWithGST)?></td>
                        </tr>
                        <?
                        $PrevPurchaseId = $PurchaseId;

                        if (!in_array(Stock::joinStockIdAttributeId($Row['stock_id'], $Row['attribute_id']), $Items))
                        {
                            $Items[] = Stock::joinStockIdAttributeId($Row['stock_id'], $Row['attribute_id']);
                            $Count += 1;
                            $Total['items'] = $Count;
                        }
                    }
                    ?>
                    <tr class="font-weight-bolder group-total">
                        <td colspan="2" class="text-right">No. of Items : </td>
                        <td><?=Utils::format($PrevPurchaseTotal['items'], 0)?></td>
                        <td class="text-right"><?=Utils::format($PrevPurchaseTotal['qty'])?></td>
                        <td class="text-right">&nbsp;</td>
                        <td colspan="2" class="text-right"><?=Utils::format($PrevPurchaseTotal['total'])?></td>
                        <td class="text-right"><?=Utils::format($PrevPurchaseTotal['gst'])?></td>
                        <td class="text-right"><?=Utils::format($PrevPurchaseTotal['total_gst'])?></td>
                    </tr>
                    <tr>
                        <td colspan="9" class="font-weight-bold bg-success text-white text-center">TOTALS</td>
                    </tr>
                    <tr class="font-weight-bolder group-total">
                        <td class="text-right">No. of Invoices</td>
                        <td><?=$InvoiceCount?></td>
                        <td class="text-right">Total Qty : </td>
                        <td class="text-right"><?=Utils::format($Total['qty'])?></td>
                        <td class="text-right">Total Items : </td>
                        <td class="text-right"><?=Utils::format($Total['items'], 0)?></td>
                        <td class="text-right"><?=Utils::format($Total['total'])?></td>
                        <td class="text-right"><?=Utils::format($Total['gst'])?></td>
                        <td class="text-right"><?=Utils::format($Total['total_gst'])?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
<? UtilsPrint::init($PrintDescription); ?>
<? include_once("../includes/inc_footer.php") ?>