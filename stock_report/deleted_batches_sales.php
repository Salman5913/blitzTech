<?
$_GET['_page_title'] = 'Deleted Batches Sales Report';
$_GET['_page_title_text'] = 'View / Edit Deleted Batches Vouchers';

include_once("../includes/inc_header.php");

$oStockReport = new StockReport();
$Select = $oStockReport->getDeletedBatchesSales();
?>
<style>
    .bg-danger td {
        background-color: rgba(255, 0, 0, 0.18);
    }
</style>
<form action="" method="get" <? Utils::formValidate(); ?>>
    <? if (ProjectHelper::HAS_COST_CENTER) { ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <? CostCenter::designReportFilter('col-lg-4 col-md-6'); ?>
                    <div class="col-lg-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn color d-block" name="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <? } ?>
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
                            <th>Customer Name</th>
                            <th>Created By </th>
                            <th>Last Updated Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                        <tr>
                            <td><a href="<?=VoucherType::getLink($Row['voucher_type_id'], $Row['voucher_id'])?>" target="_blank"><?=$Row['voucher_name']?></a></td>
                            <td><?=DateDecode($Row['voucher_date'], 'l, M d, Y')?></td>
                            <td><?=$Row['customer_name']?></td>
                            <td><?=$Row['created_by']?></td>
                            <td><?=DateDecode($Row['updated_date_time'], 'l, M d, Y')?></td>
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