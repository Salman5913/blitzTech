<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Deals';
$_GET['_page_title_text'] = 'Combo deals: several items for one price';

include_once("../includes/inc_header.php");

$ActiveTab = 'deals';
include_once(__DIR__ . '/_tabs.php');

function h($Value) { return htmlspecialchars((string)$Value, ENT_QUOTES, 'UTF-8'); }

$Id = (int)($_GET['id'] ?? 0);
$RowDetails = $Id ? RestaurantDeal::getDetails($Id) : null;
if ($Id && !$RowDetails) { $Id = 0; }
$SelectedKeys = $Id ? RestaurantDeal::getComponentKeys($Id) : [];   // [stock id, attribute id] of each item

$Deals = [];
$SelectDeals = RestaurantDeal::getDeals();
while ($Row = DBQM::fetch_assoc($SelectDeals)) $Deals[] = $Row;

// Existing items first, then 3 empty rows to add more
$ItemRows = $SelectedKeys;
for ($n = 0; $n < 3; $n++) $ItemRows[] = [null, null];
?>
<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="row">
                <div class="col-sm-8">
                    <h3 class="card-title"><?=$Id ? 'Edit Deal' : 'New Deal'?></h3>
                </div>
                <? if ($Id) { ?>
                    <div class="col-sm-4 text-right">
                        <a href="deals.php" class="shadow-sm button btn-sm bg-soft-info font-weight-bold"><i class="mdi mdi-plus"></i> New Deal</a>
                    </div>
                <? } ?>
            </div>
            <form method="post" action="op_save_deal.php" id="deal_form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?=$Id?>">
                <input type="hidden" name="branch_id" value="<?=RestaurantMenu::getWebsiteBranchId()?>">
                <div class="form-group">
                    <label for="name">Deal name</label>
                    <input type="text" name="name" id="name" value="<?=h($RowDetails['name'] ?? '')?>" placeholder="Burger Duo Deal" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" name="description" id="description" value="<?=h($RowDetails['description'] ?? '')?>" placeholder="2 burgers + 2 drinks">
                </div>
                <div class="form-group">
                    <label for="deal_price">Deal price (total for all items)</label>
                    <input type="number" step="0.01" min="0" name="price" id="deal_price" value="<?=h($RowDetails['price'] ?? '')?>" required>
                </div>
                <div class="form-group custom-control custom-switch mb-3" dir="ltr">
                    <input type="checkbox" class="custom-control-input" id="show_on_website" name="show_on_website" value="Y" <?=!empty($RowDetails['show_on_website']) ? 'checked' : ''?>>
                    <label class="custom-control-label" for="show_on_website">Show on website</label>
                    <small class="form-text text-muted">Hidden deals are kept here but not sold on the website.</small>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <div class="card">
                            <h3 class="card-title">Items</h3>
                            <table class="table color dynamic stock table-bordered">
                                <thead>
                                <tr>
                                    <th>Item Name</th>
                                </tr>
                                </thead>
                                <tbody>
                                <? foreach ($ItemRows as $ItemRow) { ?>
                                    <tr>
                                        <td><label><input type="text" name="stock_id[]" id="stock_id" <? Autocomplete::ACSearchStockRow($ItemRow[0], $ItemRow[1], '[name="branch_id"]', '[data-js="qty-available"]', '[data-js="qty"]', '[data-js="rate"]', false) ?>></label></td>
                                    </tr>
                                <? } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <small class="text-muted d-block mb-2">Each item must be on the website menu. Normal price and savings are shown after saving.</small>

                <div class="text-right">
                    <button class="btn button color" type="submit"><i class="mdi mdi-content-save"></i> <?=$Id ? 'Update Deal' : 'Save Deal'?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <h3 class="card-title">All Deals</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Deal</th>
                            <th>Items</th>
                            <th class="text-right">Normal</th>
                            <th class="text-right">Deal price</th>
                            <th>Website</th>
                            <th class="text-right"><i class="mdi mdi-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? foreach ($Deals as $Deal) {
                            $Saved = (float)$Deal['normal_price'] - (float)$Deal['price'];
                        ?>
                            <tr>
                                <td>
                                    <b><?=h($Deal['name'])?></b>
                                    <? if ($Saved > 0 && $Deal['normal_price'] > 0) { ?><span class="badge badge-soft-success">Save <?=round($Saved / $Deal['normal_price'] * 100)?>%</span><? } ?>
                                </td>
                                <td><small><?=(int)$Deal['item_count']?> items</small></td>
                                <td class="text-right"><?=number_format($Deal['normal_price'])?></td>
                                <td class="text-right"><b><?=number_format($Deal['price'])?></b></td>
                                <td>
                                    <? if ($Deal['show_on_website']) { ?>
                                        <span class="badge badge-soft-success">Shown</span>
                                    <? } else { ?>
                                        <span class="badge badge-soft-secondary">Hidden</span>
                                    <? } ?>
                                </td>
                                <td class="text-right text-nowrap">
                                    <form method="post" action="op_save_deal.php" class="d-inline">
                                        <input type="hidden" name="id" value="<?=$Deal['id']?>">
                                        <a href="deals.php?id=<?=$Deal['id']?>" class="btn btn-sm btn-light" title="Edit"><i class="mdi mdi-pencil"></i></a>
                                        <button class="btn btn-sm btn-light" name="action" value="<?=$Deal['show_on_website'] ? 'hide' : 'show'?>" title="<?=$Deal['show_on_website'] ? 'Hide from website' : 'Show on website'?>">
                                            <i class="mdi <?=$Deal['show_on_website'] ? 'mdi-eye-off-outline' : 'mdi-eye-outline'?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light text-danger" name="action" value="delete" title="Remove deal" onclick="return confirm('Remove this deal from the website and the ERP?')"><i class="mdi mdi-trash-can-outline"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <? } ?>
                        <? if (!$Deals) { ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No deals yet. Add 2 or more items and set the deal price.</td></tr>
                        <? } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
// The item boxes show names, but the server needs "stock::attribute" (the autocomplete keeps it in data-ac-id).
// Voucher forms do this swap on submit; this form does it too, so the ids are posted.
document.getElementById('deal_form').addEventListener('submit', function () {
    this.querySelectorAll('[data-ac-id]').forEach(function (input) {
        input.value = input.getAttribute('data-ac-id');
    });
});
</script>
<? include_once("../includes/inc_footer.php"); ?>
