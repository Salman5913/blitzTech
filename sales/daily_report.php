<?
$_GET['_page_title'] = 'Datewise Sales Report';
$_GET['_page_title_text'] = 'Complete report for daily sales';

include_once("../includes/inc_header.php");

$oSales = new SalesReport();
if (isset($_GET['search']))
{
    $Select = $oSales->getDateWiseReport($_GET['date_from'], $_GET['date_till'], $_GET['customer_id'], $_GET['stock_id'], $_GET['branch_id'], $_GET['category_id'], $_GET['brand'], $_GET['voucher_type_id'], $_GET['description'], $_GET['salesman_id'], $_GET['invoice_type']);
    $PrintDescription = "Datewise Daily Report : ".DateEncode($_GET['date_from'], 'd/m/Y')." - ".DateEncode($_GET['date_till'], 'd/m/Y');
    if (!empty($_GET['cost_center_id']))
        $PrintDescription .= " | Cost Center: ".CostCenter::getName($_GET['cost_center_id']);
}
?>
<form action="" method="get" <?Utils::formValidate()?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Filter Options</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label>Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] =='' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] =='' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="customer_id">Customer Name</label>
                            <input type="text" name="customer_id" id="customer_id" <? Autocomplete::ACSearchAccount($_GET['customer_id']); ?>>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <input type="text" id="category_id" name="category_id"  <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $_GET['category_id']); ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="stock_id">Stock Item</label>
                            <input type="text" id="stock_id" name="stock_id"  <? Autocomplete::ACSearchStock($_GET['stock_id'], ''); ?>>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <input type="text" id="brand" name="brand"  <? Autocomplete::Search(Autocomplete::TYPE_STOCK_BRAND, $_GET['brand']); ?>>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="branch_id">Warehouse</label>
                            <input type="text" id="branch_id" name="branch_id"  <? Autocomplete::Search(Autocomplete::TYPE_BRANCH, $_GET['branch_id']); ?>>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="salesman_id">Salesman</label>
                            <input type="text" name="salesman_id" id="salesman_id" <? Autocomplete::ACSearchAccountByCOAId([getConstantCategory()->getSalesMan()], $_GET['salesman_id']); ?>>
                        </div>
                    </div>
                    <? CostCenter::designReportFilter('col-md-3'); ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="voucher_type_id">Voucher Type</label>
                            <select id="voucher_type_id" name="voucher_type_id"><? Utils::getDropDownOptionsFromArray(
                                    [
                                        '' => 'ALL',
                                        VoucherType::SIV => 'Sales Invoice Voucher',
                                        VoucherType::SRV => 'Sales Return Voucher'
                                    ], $_GET['voucher_type_id']); ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="description">Reference</label>
                            <textarea id="description" name="description"><?=$_GET['description']?></textarea>
                        </div>
                    </div>
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="fa fa-search"></i> Search</button>
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
                        <th>Customer Name</th>
                        <th>Item Name</th>
                        <th>Warehouse</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Disc. Rate</th>
                        <th>Total</th>
                        <th>Transport</th>
                        <th><?=ProjectHelper::TAX_LABEL?></th>
                        <th>Total W/Tax</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?
                    $PrevSalesId = $SalesId = 0;
                    $PrevSalesTotal = [];
                    $Items = [];
                    $Total = [];
                    $InvoiceCount = 0;
                    $Count = 0;

                    while ($RowSales = DBQM::fetch_assoc($Select))
                    {
                        $SellingPrice = $RowSales['selling_price'];
                        $DiscountedSellingPrice = $RowSales['discounted_rate'];

                        $Qty = $RowSales['qty'] - $RowSales['qty_return'];

                        $Multiplier = $RowSales['voucher_type_id'] == VoucherType::SRV ? -1 : 1;
                        $RowTotal = $DiscountedSellingPrice * $Qty;

                        $GST = $RowTotal * ($RowSales['gst_percentage'] / 100);

                        $RowTotalWithTax = $RowTotal + $GST;

                        $SalesId = $RowSales['sales_id'];
                        $isPartOfPrevSales = $SalesId == $PrevSalesId;

                        $InvoiceNumber = $RowSales['voucher_prefix'].'-'.$RowSales['voucher_number'];

                        if (!$isPartOfPrevSales and $PrevSalesId != 0)
                        {
                            ?>
                            <tr class="font-weight-bolder group-total">
                                <td colspan="3" class="text-right">No. of Items : </td>
                                <td><?=Utils::format($PrevSalesTotal['items'], 0)?></td>
                                <td class="text-right">Qty : </td>
                                <td class="text-right"><?=Utils::format($PrevSalesTotal['qty'])?></td>
                                <td class="text-right" colspan="3"><?=Utils::format($PrevSalesTotal['total'])?></td>
                                <td>-</td>
                                <td class="text-right"><?=Utils::format($PrevSalesTotal['gst'])?></td>
                                <td class="text-right"><?=Utils::format($PrevSalesTotal['total_gst'] + $PrevSalesTotal['transport_amount'])?></td>
                            </tr>
                            <tr>
                                <td colspan="12">&nbsp;</td>
                            </tr>
                            <?
                            $PrevSalesTotal = [];
                        }

                        if (!$isPartOfPrevSales)
                            $InvoiceCount++;

                        $PrevSalesTotal['items'] += $Multiplier;
                        $PrevSalesTotal['qty'] += $Qty;
                        $PrevSalesTotal['total'] += $RowTotal;
                        $PrevSalesTotal['gst'] += $GST;
                        $PrevSalesTotal['total_gst'] += $RowTotalWithTax;
                        $PrevSalesTotal['transport_amount'] = $RowSales['transport_amount'] * $Multiplier;

                        $Total['qty'] += $Qty;
                        $Total['total'] += $RowTotal;
                        $Total['gst'] += $GST;
                        $Total['total_gst'] += $RowTotalWithTax;
                        $Total['transport_amount'] = $RowSales['transport_amount'] * $Multiplier;
                        ?>
                        <tr>
                            <td class="text-center"><? if (!$isPartOfPrevSales) { ?><?=$InvoiceNumber?> <a href="<?=VoucherType::getLink($RowSales['voucher_type_id'], $RowSales['sales_id'])?>" target="_blank"><i class="mdi mdi-pencil"></i></a><? } ?></td>
                            <td><?=$isPartOfPrevSales ? '' : DateDecode($RowSales['voucher_date'], 'M d, Y')?></td>
                            <td><?=$isPartOfPrevSales ? '' : ($RowSales['customer_account_id'] == getConstantAccount()->getCashCustomer() ? $RowSales['customer_name']." - ".$RowSales['cash_name'] : $RowSales['customer_name'])?></td>
                            <td><?=!empty($RowSales['item_name']) ? $RowSales['item_name'] : $RowSales['item_des']?></td>
                            <td><?=$RowSales['branch_name']?></td>
                            <td class="text-right"><?=Utils::format($Qty)?></td>
                            <td class="text-right"><?=Utils::format($RowSales['selling_price'])?></td>
                            <td class="text-right"><?=$RowSales['item_disc'] > 0 ? Utils::format($DiscountedSellingPrice) : 0?></td>
                            <td class="text-right"><?=Utils::format($RowTotal)?></td>
                            <td class="text-right"><?=$isPartOfPrevSales ? '' : Utils::format($RowSales['transport_amount'])?></td>
                            <td class="text-right"><?=Utils::format($GST)?></td>
                            <td class="text-right"><?=Utils::format($RowTotalWithTax)?></td>
                        </tr>
                        <?
                        $PrevSalesId = $SalesId;

                        if (!in_array(Stock::joinStockIdAttributeId($RowSales['stock_id'], $RowSales['attribute_id']), $Items))
                        {
                            $Items[] = Stock::joinStockIdAttributeId($RowSales['stock_id'], $RowSales['attribute_id']);
                            $Count += 1;
                            $Total['items'] = $Count;
                        }
                    }
                    ?>
                    <tr class="font-weight-bolder group-total">
                        <td colspan="3" class="text-right">No. of Items : </td>
                        <td><?=Utils::format($PrevSalesTotal['items'], 0)?></td>
                        <td class="text-right">Qty : </td>
                        <td class="text-right"><?=Utils::format($PrevSalesTotal['qty'])?></td>
                        <td class="text-right" colspan="3"><?=Utils::format($PrevSalesTotal['total'])?></td>
                        <td>-</td>
                        <td class="text-right"><?=Utils::format($PrevSalesTotal['gst'])?></td>
                        <td class="text-right"><?=Utils::format($PrevSalesTotal['total_gst'] + $PrevSalesTotal['transport_amount'])?></td>
                    </tr>
                    <tr>
                        <td colspan="12" class="font-weight-bold bg-success text-white text-center">TOTALS</td>
                    </tr>
                    <tr class="font-weight-bolder group-total">
                        <td colspan="3" class="text-right">No. of Invoices</td>
                        <td><?=$InvoiceCount?></td>
                        <td class="text-right">Total Qty : </td>
                        <td class="text-right"><?=Utils::format($Total['qty'])?></td>
                        <td class="text-right">Total Items : </td>
                        <td class="text-right"><?=Utils::format($Total['items'], 0)?></td>
                        <td class="text-right"><?=Utils::format($Total['total'])?></td>
                        <td>-</td>
                        <td class="text-right"><?=Utils::format($Total['gst'])?></td>
                        <td class="text-right"><?=Utils::format($Total['total_gst'] + $Total['transport_amount'])?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
<? UtilsPrint::init($PrintDescription); ?>
<? include_once("../includes/inc_footer.php") ?>