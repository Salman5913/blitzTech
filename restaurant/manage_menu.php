<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Website Menu';
$_GET['_page_title_text'] = 'Dishes shown on the restaurant website';

include_once("../includes/inc_header.php");

$ActiveTab = 'menu';
include_once(__DIR__ . '/_tabs.php');

RestaurantMenu::syncFromStock();

$Id = (int)($_GET['id'] ?? 0);
$RowDetails = $Id ? RestaurantMenu::getDetails($Id) : null;
// No id, or a dish of another branch: new dish form
if (!$RowDetails) { $Id = 0; $RowDetails = ['available' => 1, 'is_visible' => 1, 'sort_order' => 0]; }
$SelectItems = RestaurantMenu::getItems();
$SelectCategories = RestaurantMenu::getCategories();

function h($Value) { return htmlspecialchars((string)$Value, ENT_QUOTES, 'UTF-8'); }
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="row">
                <div class="col-sm-8">
                    <h3 class="card-title"><?=$Id ? 'Edit Dish' : 'Add Dish'?></h3>
                </div>
                <? if ($Id) { ?>
                    <div class="col-sm-4 text-right">
                        <a href="manage_menu.php" class="shadow-sm button btn-sm bg-soft-info font-weight-bold"><i class="mdi mdi-plus"></i> Add New</a>
                    </div>
                <? } ?>
            </div>
            <form method="post" action="op_save_menu.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?=$Id?>">
                <div class="row">
                    <div class="col-xl-6">
                        <div class="form-group">
                            <? $StockKey = RestaurantMenu::getStockKey($RowDetails['erp_stock_id'] ?? '', $RowDetails['erp_attribute_id'] ?? ''); ?>
                            <label for="erp_stock">Stock Item</label>
                            <select name="erp_stock" id="erp_stock">
                                <? Utils::getDropDownOptionsFromArray(RestaurantMenu::getStockOptions($StockKey), $StockKey) ?>
                            </select>
                            <small class="text-muted">Linked dishes take their price from the stock item (or its variation).</small>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="form-group">
                            <label for="name">Name on Website</label>
                            <input type="text" name="name" id="name" value="<?=h($RowDetails['name'] ?? '')?>" placeholder="Leave empty to use stock item name">
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="form-group">
                            <label for="category">Website Category</label>
                            <input type="text" name="category" id="category" list="website_categories" value="<?=h($RowDetails['category'] ?? '')?>" required>
                            <datalist id="website_categories">
                                <? foreach (RestaurantMenu::getWebsiteCategories() as $Category) { ?>
                                    <option value="<?=h($Category)?>">
                                <? } ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="form-group">
                            <label for="price">Own Price</label>
                            <input type="number" step="0.01" min="0" name="price" id="price" value="<?=h($RowDetails['price'] ?? '')?>">
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="form-group">
                            <label for="sort_order">Sort Order</label>
                            <input type="number" name="sort_order" id="sort_order" value="<?=h($RowDetails['sort_order'])?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="2"><?=h($RowDetails['description'] ?? '')?></textarea>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="form-group">
                            <label for="image">Image URL</label>
                            <input type="text" name="image" id="image" value="<?=h($RowDetails['image'] ?? '')?>" placeholder="https://... (optional)">
                        </div>
                    </div>
                    <div class="col-xl-3 hidden">
                        <div class="form-group">
                            <label for="emoji">Emoji</label>
                            <input type="text" name="emoji" id="emoji" value="<?=h($RowDetails['emoji'] ?? '')?>" placeholder="🍔">
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="form-group">
                            <label for="tag">Tag</label>
                            <input type="text" name="tag" id="tag" value="<?=h($RowDetails['tag'] ?? '')?>" placeholder="New, Popular">
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="form-group">
                            <label for="available">Availability</label>
                            <select name="available" id="available">
                                <? Utils::getDropDownOptionsFromArray(['Y' => 'Available', 'N' => 'Sold out'], $RowDetails['available'] ? 'Y' : 'N') ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="form-group">
                            <label for="is_visible">On Website</label>
                            <select name="is_visible" id="is_visible">
                                <? Utils::getDropDownOptionsFromArray(['Y' => 'Shown', 'N' => 'Hidden'], $RowDetails['is_visible'] ? 'Y' : 'N') ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col-auto ml-auto">
                        <button class="btn button color" type="submit"><i class="mdi mdi-content-save"></i> <?=$Id ? 'Update Dish' : 'Save Dish'?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5 hidden">
        <div class="card">
            <div class="d-flex align-items-center">
                <h3 class="card-title mb-0">Website Categories</h3>
                <span class="badge badge-soft-success ml-auto" id="category_count"></span>
            </div>
            <p class="text-muted mt-2">Switch a stock category on to sell all its items on the website, including items you add later. Switching it off hides them.</p>
            <form method="post" action="op_save_menu.php">
                <input type="hidden" name="action" value="categories">
                <input type="search" id="category_search" placeholder="Search categories..." class="mb-2" style="width: 100%">
                <div class="list-group list-group-flush" id="category_list">
                    <? while ($Row = DBQM::fetch_assoc($SelectCategories)) { ?>
                        <div class="list-group-item px-0 py-2 js-category" data-name="<?=h(strtolower($Row['name'] . ' ' . $Row['website_name']))?>">
                            <div class="d-flex align-items-center">
                                <div class="custom-control custom-switch" dir="ltr">
                                    <input type="checkbox" class="custom-control-input js-category-switch" id="category_<?=$Row['id']?>" name="on_website[]" value="<?=$Row['id']?>" <?=$Row['on_website'] ? 'checked' : ''?>>
                                    <label class="custom-control-label font-weight-bold" for="category_<?=$Row['id']?>"><?=h($Row['name'])?></label>
                                </div>
                                <small class="text-muted ml-2"><?=$Row['item_count']?> <?=$Row['item_count'] == 1 ? 'item' : 'items'?></small>
                                <? if ($Row['on_website']) { ?>
                                    <a href="<?=h(RestaurantMenu::getWebsiteCategoryUrl($Row['shown_as']))?>" target="_blank" class="ml-auto small text-nowrap" title="Open this category on the website">
                                        <?=$Row['dishes_shown']?> on website <i class="mdi mdi-open-in-new"></i>
                                    </a>
                                <? } ?>
                            </div>
                            <div class="js-category-name mt-1 pl-4" <?=$Row['on_website'] ? '' : 'style="display: none"'?>>
                                <input type="text" name="website_name[<?=$Row['id']?>]" value="<?=h($Row['website_name'])?>" placeholder="Name on website (default: <?=h($Row['name'])?>)" maxlength="60" style="width: 100%">
                            </div>
                        </div>
                    <? } ?>
                </div>
                <div class="text-right mt-2">
                    <button class="btn button color" type="submit"><i class="mdi mdi-content-save"></i> Save Categories</button>
                </div>
            </form>
        </div>
        <script>
            (function () {
                var rows = document.querySelectorAll('#category_list .js-category');

                function updateCount() {
                    var on = document.querySelectorAll('#category_list .js-category-switch:checked').length;
                    document.getElementById('category_count').textContent = on + ' of ' + rows.length + ' on website';
                }

                rows.forEach(function (row) {
                    row.querySelector('.js-category-switch').addEventListener('change', function () {
                        row.querySelector('.js-category-name').style.display = this.checked ? '' : 'none';
                        updateCount();
                    });
                });

                document.getElementById('category_search').addEventListener('input', function () {
                    var text = this.value.trim().toLowerCase();
                    rows.forEach(function (row) {
                        row.style.display = row.dataset.name.indexOf(text) === -1 ? 'none' : '';
                    });
                });

                updateCount();
            })();
        </script>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <h3 class="card-title">Menu Items</h3>
            <div class="table-responsive">
                <table class="table color table-striped">
                    <thead>
                        <tr>
                            <th>Dish</th>
                            <th class="hidden">Stock Item</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Stock (<?=h(Branch::getName(RestaurantMenu::getWebsiteBranchId()))?>)</th>
                            <th>Status</th>
                            <th class="text-right"><i class="mdi mdi-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?
                        // Dishes grouped by website category, so a whole category can be hidden or shown at once
                        $ItemsByCategory = [];
                        while ($Row = DBQM::fetch_assoc($SelectItems))
                            $ItemsByCategory[$Row['category']][] = $Row;

                        foreach ($ItemsByCategory as $Category => $CategoryItems) {
                            $ShownCount = count(array_filter($CategoryItems, fn($Item) => $Item['is_visible']));
                        ?>
                            <tr class="bg-light">
                                <td colspan="4" class="font-weight-bold">
                                    <?=h($Category)?>
                                    <small class="text-muted ml-2"><?=$ShownCount?> of <?=count($CategoryItems)?> shown</small>
                                    <? if (!$ShownCount) { ?><span class="badge badge-soft-secondary ml-1">Category hidden</span><? } ?>
                                </td>
                                <td class="text-right text-nowrap">
                                    <form method="post" action="op_save_menu.php" class="d-inline">
                                        <input type="hidden" name="category" value="<?=h($Category)?>">
                                        <? if ($ShownCount) { ?>
                                            <button class="btn btn-sm btn-light" name="action" value="hide_category" onclick="return confirm('Hide every dish in <?=h(addslashes($Category))?> from the website?')">
                                                <i class="mdi mdi-eye-off-outline"></i> Hide category
                                            </button>
                                        <? } else { ?>
                                            <button class="btn btn-sm btn-light text-success" name="action" value="show_category">
                                                <i class="mdi mdi-eye-outline"></i> Show category
                                            </button>
                                        <? } ?>
                                    </form>
                                </td>
                            </tr>
                        <? foreach ($CategoryItems as $Row) {
                            $isLinked = !empty($Row['erp_stock_id']);
                            $Price = $isLinked && $Row['stock_exists'] ? $Row['stock_price'] : $Row['price'];
                        ?>
                            <tr <?=$Row['is_visible'] ? '' : 'class="text-muted"'?>>
                                <td><?=h($Row['emoji'])?> <?=h($Row['name'])?> <? if ($Row['tag']) { ?><span class="badge badge-soft-primary"><?=h($Row['tag'])?></span><? } ?></td>

                                <td class="hidden">
                                    <? if (!$isLinked) { ?>
                                        <span class="text-muted">—</span>
                                    <? } elseif (!$Row['stock_exists']) { ?>
                                        <span class="text-danger">Not sellable — item or variation removed, edit to relink</span>
                                    <? } else { ?>
                                        <?=h($Row['stock_name'])?> <? if ($Row['is_auto']) { ?><span class="badge badge-soft-info">auto</span><? } ?>
                                    <? } ?>
                                </td>
                                <td class="text-right"><?=number_format($Price)?><?=$isLinked ? '' : ' <small class="text-muted">own</small>'?></td>
                                <td class="text-right">
                                    <? if ($isLinked) { ?>
                                        <span class="<?=$Row['stock_qty'] > 0 ? '' : 'text-danger'?>"><?=(float)$Row['stock_qty']?></span>
                                    <? } else { ?>
                                        <span class="text-muted">n/a</span>
                                    <? } ?>
                                </td>
                                <td>
                                    <? if (!$Row['is_visible']) { ?>
                                        <span class="badge badge-soft-secondary">Hidden</span>
                                    <? } elseif (!$Row['available']) { ?>
                                        <span class="badge badge-soft-warning">Sold out</span>
                                    <? } else { ?>
                                        <span class="badge badge-soft-success">Shown</span>
                                    <? } ?>
                                    <? if ($Row['show_in_hero']) { ?>
                                        <span class="badge badge-soft-info" title="On this branch's home page hero slider (when shown and available)"><i class="mdi mdi-star"></i> Hero</span>
                                    <? } ?>
                                </td>
                                <td class="text-right text-nowrap">
                                    <form method="post" action="op_save_menu.php" class="d-inline">
                                        <input type="hidden" name="id" value="<?=$Row['id']?>">
                                        <a href="manage_menu.php?id=<?=$Row['id']?>" class="btn btn-sm btn-light" title="Edit"><i class="mdi mdi-pencil"></i></a>
                                        <button class="btn btn-sm btn-light" name="action" value="<?=$Row['available'] ? 'sold_out' : 'available'?>" title="<?=$Row['available'] ? 'Mark sold out' : 'Mark available'?>">
                                            <i class="mdi <?=$Row['available'] ? 'mdi-food-off' : 'mdi-food'?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light" name="action" value="<?=$Row['is_visible'] ? 'hide' : 'show'?>" title="<?=$Row['is_visible'] ? 'Hide from website' : 'Show on website'?>">
                                            <i class="mdi <?=$Row['is_visible'] ? 'mdi-eye-off-outline' : 'mdi-eye-outline'?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light <?=$Row['show_in_hero'] ? 'text-info' : ''?>" name="action" value="<?=$Row['show_in_hero'] ? 'hero_off' : 'hero_on'?>" title="<?=$Row['show_in_hero'] ? 'Remove from hero slider' : 'Show on hero slider'?>">
                                            <i class="mdi <?=$Row['show_in_hero'] ? 'mdi-star' : 'mdi-star-outline'?>"></i>
                                        </button>
                                        <? if (!$Row['is_auto']) { ?>
                                            <button class="btn btn-sm btn-light text-danger" name="action" value="delete" title="Delete" onclick="return confirm('Delete this dish from the website menu?')"><i class="mdi mdi-trash-can-outline"></i></button>
                                        <? } ?>
                                    </form>
                                </td>
                            </tr>
                        <? } } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php"); ?>
