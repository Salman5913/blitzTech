<?
$_GET['_page_title'] = $_GET['invoice_type'] == 'D' ? 'Pending Delivery Challan Report' : 'Project Invoices Report';
$_GET['_page_title_text'] = $_GET['invoice_type'] == 'D' ? 'View / Edit Pending Delivery Challan' : 'View / Edit Project Sales Invoices';

include_once("../includes/inc_header.php");

$ConvertInvoices = '';
$oSalesVoucher = new Sales();

$oSales = new SalesReport();
$Select = $oSales->getReport($_GET['date_from'], $_GET['date_till'], $_GET['invoice_number'], $_GET['item_name'], $_GET['customer_account_id'], $_GET['description'], $_GET['voucher_type_id'], '', $_GET['invoice_type'], true);

?>
<style>
    .bg-danger td {
        background-color: rgba(255, 0, 0, 0.18);
    }
</style>
<form action="" method="get" <? Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <? $oSalesVoucher->showVoucherButtonsWithOptions([$_GET['voucher_type_id']]); ?>
                <div class="row filter hidden">
                    <div class="col-xl-5 col-md-6">
                        <div class="form-group mb-0">
                            <label>Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] =='' ? date("M d, Y", strtotime("-3 month")) : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] =='' ? date("M d, Y") : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="invoice_number">Voucher Number</label>
                            <input type="text" name="invoice_number" id="invoice_number" data-type="int" value="<?=$_GET['invoice_number']?>">
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="stock_id">Item Name</label>
                            <input type="text" id="stock_id" name="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'], '') ?>>
                        </div>
                    </div>
                    <div class="col-xl-5 col-md-6">
                        <div class="form-group">
                            <label for="customer_account_id">Customer Name</label>
                            <input type="text" name="customer_account_id" id="customer_account_id" <? Autocomplete::ACSearchAccount($_GET['customer_account_id']) ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?=$_GET['description']?></textarea>
                            <input type="hidden" name="invoice_type" value="<?=$_GET['invoice_type']?>">
                            <input type="hidden" id="voucher_type_id" name="voucher_type_id" value="<?=$_GET['voucher_type_id']?>">
                        </div>
                    </div>
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Search Results</h3>
                <div class="table-responsive">
                    <table class="table data-table-report color table-striped persist-area">
                        <thead>
                        <tr class="persist-header">
                            <th>Voucher Number</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th class="text-right">Voucher Total</th>
                            <th class="text-right">Voucher Status</th>
                            <th class="text-right"><i class="fas fa-cog"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <? while ($Row = DBQM::fetch_assoc($Select)) {

                        $AdditionalLink = [];

                        $AdditionalLink["ajax_get_voucher_details.php?id=".$Row['id']] = [ "View Sales Voucher Data of ".$Row['id']." ", '<i class="fa fa-search-plus"></i> View Detail'];
                        $AdditionalLink['print_voucher.php?voucher_type_id=39&id='.$Row['id'].'&no_header_footer=true'] = '<i class="fa fa-print"></i> No HF'
                        ?>

                        <tr class="<? $oSales->highlightDeletedRow($Row); ?>">
                            <td><?=$Row['voucher_prefix'].'-'.$Row['voucher_number']?></td>
                            <td><?=DateDecode($Row['voucher_date'], 'l, M d, Y')?></td>
                            <td><?=$Row['customer_name']?></td>
                            <td class="text-right" data-js="voucher_total"><?=Utils::format($Row['voucher_total'], 2)?></td>
                            <td class="text-right"><? $oSales->getVoucherStatus($Row); ?></td>
                            <td class="text-right report-actions">
                                <? $oSales->getReportButtons($Row['voucher_type_id'], $Row['id'], $AdditionalLink );?>
                            </td>
                        </tr>
                        <? } ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="4" class="text-right font-weight-bolder display-6 text-success" <? Utils::sumReportColumn('[data-js="voucher_total"]');?>></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>
<? include_once("../includes/inc_footer.php") ?>