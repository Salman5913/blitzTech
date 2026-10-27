<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Item Report';
$_GET['_page_title_text'] = 'View / Edit Items';

include_once("../includes/inc_header.php");

$oStock = new Stock();
$oStockAttribute = new StockAttribute();
$oStockCategory = new StockCategory();

$Select = $oStock->getReport($_GET['stock_id'], $_GET['category_id'], $_GET['brand']);

?>
<section class="voucher-report">
    <form action="" method="get" <? Utils::formValidate(); ?>>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <? $oStock->showVoucherButtonsWithOptions([
                        ["form_type" => $oStock->getFormType(), "additional_param" => "", "form_class" => get_class($oStock)],
                        ["form_type" => $oStockAttribute->getFormType(), "additional_param" => "", "form_class" => get_class($oStockAttribute)],
                        ["form_type" => $oStockCategory->getFormType(), "additional_param" => "", "form_class" => get_class($oStockCategory)]
                    ]); ?>
                    <div class="row filter hidden">
                        <div class="col-xl-6 col-md-6">
                            <div class="form-group">
                                <label for="stock_id">Name</label>
                                <input type="text" name="stock_id" id="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'], '') ?>>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="form-group">
                                <label for="category_id">Category</label>
                                <input type="text" name="category_id" id="category_id" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $_GET['category_id']); ?>>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="form-group">
                                <label for="brand">Brand</label>
                                <input type="text" name="brand" id="brand" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_BRAND, $_GET['brand']); ?>>
                            </div>
                        </div>
                        <div class="col-sm-12 text-right">
                            <button type="submit" class="btn color" name="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Search Results</h3>
                <div class="table-responsive">
                <table id="data-table" class="table data-table-report color table-striped font-weight-bolder font-size-14">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Reorder Level</th>
                            <th class="text-right"> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                        <tr>
                            <td><?=$Row['ac_stock']?></td>
                            <td><?=$Row['category_name']?></td>
                            <td><?=$Row['brand']?></td>
                            <td><?=$Row['reorder_level']?></td>
                            <td class="text-right report-actions">
                                <? $oStock->getReportButtons($Row['id'], 'stock_management.php') ?>
                            </td>
                        </tr>
                        <? } ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</section>
<? include_once("../includes/inc_footer.php") ?>