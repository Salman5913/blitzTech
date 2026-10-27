<?
include_once ("../connection/localhost.php");

$oStockCategory = new StockCategory();
$oStock = new Stock();
$oStock->initForEdit();

$oStockAttribute = new StockAttribute();

$StockId = $oStock->getVoucherId();
$RowDetails = $oStock->getDetails();

$SelectVariableAttributes = $oStock->getAllAvailableAttributesByStockId($StockId);
$DataRows = DatabaseHelper::getRecordCount($SelectVariableAttributes) + 1;

$RowWebsite = $StockId ? RestaurantMenu::getByStockId($StockId) : null;

include_once("../includes/inc_header.php");
?>
<form action="op_save_stock.php" method="post" <? Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="card">
                <h3 class="card-title">Basic Details</h3>
                <div class="row">
                    <div class="col-12 hidden">
                        <div class="form-group">
                            <label for="item_code">Code</label>
                            <input type="text" name="item_code" id="item_code" value="<?=$RowDetails['code']?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="item_name">Name</label>
                            <input type="text" name="item_name" id="item_name" <? UniquenessChecker::checkForUnique('st_stock', "name", $RowDetails['name'])?> value="<?=$RowDetails['name']?>" <? Utils::isRequired() ?>>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <div class="input-group">
                                <input type="text" class="form-control col-md-12" name="category_id" id="category_id" data-auto-select="Y" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $RowDetails['category_id']); ?>>
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <a href="javascript:void(0)" <? Utils::AjaxLoadedPopup('New Category', ProjectHelper::SiteURL()."stock_category/ajax_save_category.php");?>><i class="fa fa-edit"></i></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <input type="text" name="brand" id="brand" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_BRAND, $RowDetails['brand']); ?>>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="stock_unit">Unit</label>
                            <input type="text" name="stock_unit" id="stock_unit" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_UNIT, $RowDetails['unit']); ?>>
                        </div>
                    </div>
                    <div class="col-12 hidden">
                        <div class="form-group custom-switch mb-2" dir="ltr">
                            <input type="checkbox" class="custom-control-input" id="is_track" name="is_track" value="Y" checked>
                            <label class="custom-control-label" for="is_track">I Track This Item</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6">
            <div class="card">
                <h3 class="card-title">Optional Details</h3>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="reorder">Reorder Level</label>
                            <input type="text" name="reorder" id="reorder" data-type="float" value="<?=$RowDetails['reorder_level']?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="sp">Selling Price</label>
                            <input type="text" data-type="float" name="sp" id="sp" value="<?=$RowDetails['sp']?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="barcode">Barcode</label>
                            <input type="text" data-type="float" name="main_barcode" id="barcode" value="<?=$RowDetails['barcode']?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <h3 class="card-title">Restaurant Website <small class="text-muted">— <?=htmlspecialchars(Branch::getName(RestaurantBranch::getCurrentId()))?> menu</small></h3>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group custom-control custom-switch mb-2" dir="ltr">
                            <input type="checkbox" class="custom-control-input" id="show_on_website" name="show_on_website" value="Y" <?=!empty($RowWebsite['is_visible']) ? 'checked' : ''?>>
                            <label class="custom-control-label" for="show_on_website">Show on website menu</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group custom-control custom-switch mb-2" dir="ltr">
                            <input type="checkbox" class="custom-control-input" id="show_on_hero" name="show_on_hero" value="Y" <?=!empty($RowWebsite['show_in_hero']) ? 'checked' : ''?>>
                            <label class="custom-control-label" for="show_on_hero">Show on hero slider (homepage banner)</label>
                            <small class="form-text text-muted">The dish must also be on the website menu.</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="website_category">Website Category</label>
                            <input type="text" name="website_category" id="website_category" list="website_categories" value="<?=htmlspecialchars($RowWebsite['category'] ?? '')?>" placeholder="Burgers, Drinks... (empty = stock category)">
                            <datalist id="website_categories">
                                <? foreach (RestaurantMenu::getWebsiteCategories() as $WebsiteCategory) { ?>
                                    <option value="<?=htmlspecialchars($WebsiteCategory)?>">
                                <? } ?>
                            </datalist>
                            <? if ($RowWebsite) { ?>
                                <small><a href="../restaurant/manage_menu.php?id=<?=$RowWebsite['id']?>">Photo, description &amp; more on Website Menu</a></small>
                            <? } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="card">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="card-title">Stock Variations</h3>
                    </div>
                    <div class="col-sm-6 text-right <?=$oStock->isEdit() ? 'hidden' : ''?>">
                        <a href="../stock_attribute/stock_attr_manager.php" class="shadow-sm button btn-sm bg-soft-purple font-weight-bold" target="_blank"><i class="mdi mdi-plus"></i> New Attribute</a>
                    </div>
                </div>
                <div class="table-responsive">
                <table class="table color dynamic">
                    <thead>
                    <tr>
                        <th>Attribute</th>
                        <th class="bg-dark">Barcode</th>
                        <th class="bg-dark">SP</th>
                        <th class="bg-dark">Reorder level</th>
                        <th class="bg-dark">is Active?</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    </tfoot>
                    <tbody>
                        <? $i=0; while ($RowData = DBQM::fetch_assoc($SelectVariableAttributes) or $i < $DataRows) { ?>
                            <tr>
                                <td><label><input type="text" name="attribute_id[]" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_ATTRIBUTE_ALL, $RowData['attribute_id']); ?> /></label></td>
                                <td><label><input type="text" name="barcode[]" value="<?=$RowData['barcode']?>" /></label></td>
                                <td><label><input type="text" name="selling_price[]" value="<?=$RowData['selling_price']?>" /></label></td>
                                <td><label><input type="text" name="reorder_level[]" value="<?=$RowData['reorder_level']?>" /></label></td>
                                <td><label><select name="is_inactive[]"><? Utils::getDropDownOptionsFromArray(['N' => 'No', 'Y' => 'Yes'], $RowData['is_inactive']); ?></select></label></td>
                            </tr>
                        <? $i++; } ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    <? $oStock->showVoucherButtons() ?>
</form>
<? include_once("../includes/inc_footer.php"); ?>
