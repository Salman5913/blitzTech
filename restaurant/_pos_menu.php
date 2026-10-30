<?
/**
 * POS view of manage_menu.php: which categories and items the POS screen (pos/pos_voucher.php) shows on the current branch.
 * Kept apart from the website menu: hiding or marking an item not available here doesn't change the website.
 */
$POSBranchId = RestaurantMenu::getWebsiteBranchId();
$POSSettings = RestaurantMenu::getPOSSettings($POSBranchId);

// Same catalog and categories as the POS screen
$POSItemsByCategory = [];
foreach (SalesPOS::getCatalog() as $Item)
{
    $POSItemsByCategory[$Item['category_id']]['name'] = $Item['category'];
    $POSItemsByCategory[$Item['category_id']]['items'][] = $Item;
}
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="d-flex flex-wrap align-items-center">
                <h3 class="card-title mb-0">POS Menu <small class="text-muted">(<?=h(Branch::getName($POSBranchId))?>)</small></h3>
                <input type="search" id="pos_menu_search" placeholder="Search items..." class="ml-auto" style="max-width: 260px">
            </div>
            <p class="text-muted mt-2">Hide a category or item to remove it from the POS screen of this branch, or mark an item not available so it shows but can't be ordered. The website menu is not affected.</p>
            <div class="table-responsive">
                <table class="table color table-striped" id="pos_menu_table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">Price</th>
                            <th>On POS</th>
                            <th class="text-right"><i class="mdi mdi-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? if (!$POSItemsByCategory) { ?>
                            <tr><td colspan="4" class="text-center text-muted">No stock items found.</td></tr>
                        <? } ?>
                        <? foreach ($POSItemsByCategory as $CategoryId => $Category) {
                            $isCategoryHidden = !empty($POSSettings['categories'][$CategoryId]);
                            $ShownCount = count(array_filter($Category['items'], fn($Item) => empty($POSSettings['hidden'][$Item['id']])));
                        ?>
                            <tr class="bg-light js-pos-category" data-category="<?=(int)$CategoryId?>">
                                <td colspan="3" class="font-weight-bold <?=$isCategoryHidden ? 'text-muted' : ''?>">
                                    <?=h($Category['name'])?>
                                    <small class="text-muted ml-2"><?=$isCategoryHidden ? 0 : $ShownCount?> of <?=count($Category['items'])?> shown</small>
                                    <? if ($isCategoryHidden) { ?><span class="badge badge-soft-secondary ml-1">Category hidden</span><? } ?>
                                </td>
                                <td class="text-right text-nowrap">
                                    <form method="post" action="op_save_menu.php" class="d-inline">
                                        <input type="hidden" name="stock_category_id" value="<?=(int)$CategoryId?>">
                                        <input type="hidden" name="category" value="<?=h($Category['name'])?>">
                                        <? if (!$isCategoryHidden) { ?>
                                            <button class="btn btn-sm btn-light" name="action" value="pos_hide_category" onclick="return confirm('Hide <?=h(addslashes($Category['name']))?> from the POS?')">
                                                <i class="mdi mdi-eye-off-outline"></i> Hide category
                                            </button>
                                        <? } else { ?>
                                            <button class="btn btn-sm btn-light text-success" name="action" value="pos_show_category">
                                                <i class="mdi mdi-eye-outline"></i> Show category
                                            </button>
                                        <? } ?>
                                    </form>
                                </td>
                            </tr>
                            <? foreach ($Category['items'] as $Item) {
                                $isHidden = !empty($POSSettings['hidden'][$Item['id']]);
                                $isAvailable = empty($POSSettings['unavailable'][$Item['id']]);
                            ?>
                                <tr class="js-pos-item <?=$isHidden || $isCategoryHidden ? 'text-muted' : ''?>" data-category="<?=(int)$CategoryId?>" data-name="<?=h(strtolower($Item['name'] . ' ' . $Item['barcode']))?>">
                                    <td><?=h($Item['emoji'])?> <?=h($Item['name'])?></td>
                                    <td class="text-right"><?=number_format($Item['price'])?></td>
                                    <td>
                                        <? if ($isHidden) { ?>
                                            <span class="badge badge-soft-secondary">Hidden</span>
                                        <? } elseif ($isCategoryHidden) { ?>
                                            <span class="badge badge-soft-secondary" title="Shown again when its category is shown">Category hidden</span>
                                        <? } else { ?>
                                            <span class="badge badge-soft-success">Shown</span>
                                        <? } ?>
                                        <? if (!$isAvailable) { ?>
                                            <span class="badge badge-soft-warning">Not available</span>
                                        <? } ?>
                                    </td>
                                    <td class="text-right text-nowrap">
                                        <form method="post" action="op_save_menu.php" class="d-inline">
                                            <input type="hidden" name="stock_key" value="<?=h($Item['id'])?>">
                                            <button class="btn btn-sm btn-light" name="action" value="<?=$isAvailable ? 'pos_sold_out' : 'pos_available'?>" title="<?=$isAvailable ? 'Mark not available' : 'Mark available'?>">
                                                <i class="mdi <?=$isAvailable ? 'mdi-food-off' : 'mdi-food'?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-light" name="action" value="<?=$isHidden ? 'pos_show_item' : 'pos_hide_item'?>" title="<?=$isHidden ? 'Show on POS' : 'Hide from POS'?>">
                                                <i class="mdi <?=$isHidden ? 'mdi-eye-outline' : 'mdi-eye-off-outline'?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <? } ?>
                        <? } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        var table = document.getElementById('pos_menu_table');

        document.getElementById('pos_menu_search').addEventListener('input', function () {
            var text = this.value.trim().toLowerCase();
            var matched = {};

            table.querySelectorAll('.js-pos-item').forEach(function (row) {
                var isMatch = row.dataset.name.indexOf(text) !== -1;
                row.style.display = isMatch ? '' : 'none';
                if (isMatch)
                    matched[row.dataset.category] = true;
            });

            table.querySelectorAll('.js-pos-category').forEach(function (row) {
                row.style.display = matched[row.dataset.category] ? '' : 'none';
            });
        });
    })();
</script>
