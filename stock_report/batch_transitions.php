<?
$_GET['_page_title'] = 'Stock Batch Transitions Report';
$_GET['_page_title_text'] = 'View / Edit Stock Batch Vouchers';

include_once("../includes/inc_header.php");

$oStockReport = new StockReport();

$Select = false;
$ConvertInvoices = '';

if (isset($_GET['search']))
{
    $Select = $oStockReport->getBatchTransitionStockInStockLedger($_GET['date_from'], $_GET['date_till'], $_GET['stock_id'], $_GET['is_deleted']);
}
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
                <h3 class="card-title"> Filter Options</h3>
                <div class="row">
                    <div class="col-xl-5 col-md-6">
                        <div class="form-group mb-0">
                            <label>Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] =='' ? '' : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] =='' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="form-group">
                            <label for="stock_id">Item Name</label>
                            <input type="text" id="stock_id" name="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'], '') ?>>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="form-group">
                        <label for="is_deleted">Deleted Vouchers</label>
                        <select name="is_deleted" id="is_deleted" ><option value=""></option><? Utils::getDropDownOptionsFromArray(['Y' => 'Yes', 'N' => 'No'], $_GET['is_deleted']); ?></select>
                    </div>
                </div>
                <? CostCenter::designReportFilter('col-xl-3 col-md-6'); ?>
                <div class="form-group row">
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
                <table class="table data-table-report color table-striped">
                    <thead>
                        <tr>
                            <th>Voucher Number</th>
                            <th>Voucher Date</th>
                            <th>Account Name</th>
                            <th>Last Updated Date</th>
                            <th>Last Updated By</th>
                        </tr>
                    </thead>
                    <tbody>
                    <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                        <tr class="<?=$Row['is_deleted'] == 'Y' ? ' bg-soft-danger row-deleted' : ''?>">
                            <td><?=$Row['voucher_name']?></td>
                            <td><?=DateDecode($Row['voucher_date'], 'l, M d, Y')?></td>
                            <td><?=$Row['account_name']?></td>
                            <td><?=DateDecode($Row['updated_date_time'], 'l, M d, Y')?></td>
                            <td><?=$Row['last_updated_by']?></td>
                        </tr>
                        <? } ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</form>
<? include_once("../includes/inc_footer.php") ?>