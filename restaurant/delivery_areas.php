<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Delivery Areas';
$_GET['_page_title_text'] = 'Areas the website delivers to, with the charge for each';

include_once("../includes/inc_header.php");

$ActiveTab = 'areas';
include_once(__DIR__ . '/_tabs.php');

function h($Value) { return htmlspecialchars((string)$Value, ENT_QUOTES, 'UTF-8'); }

$Id = (int)($_GET['id'] ?? 0);
$RowDetails = $Id ? RestaurantDeliveryArea::getDetails($Id) : null;
if ($Id && !$RowDetails) { $Id = 0; }

$Areas = [];
$SelectAreas = RestaurantDeliveryArea::getAll();
while ($Row = DBQM::fetch_assoc($SelectAreas)) $Areas[] = $Row;
?>
<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="row">
                <div class="col-sm-8">
                    <h3 class="card-title"><?=$Id ? 'Edit Area' : 'New Area'?></h3>
                </div>
                <? if ($Id) { ?>
                    <div class="col-sm-4 text-right">
                        <a href="delivery_areas.php" class="shadow-sm button btn-sm bg-soft-info font-weight-bold"><i class="mdi mdi-plus"></i> New Area</a>
                    </div>
                <? } ?>
            </div>
            <form method="post" action="op_save_delivery_area.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?=$Id?>">
                <div class="form-group">
                    <label for="name">Area name</label>
                    <input type="text" name="name" id="name" value="<?=h($RowDetails['name'] ?? '')?>" placeholder="Gulberg, DHA, ..." required>
                </div>
                <div class="form-group">
                    <label for="delivery_charge">Delivery charge (Rs.)</label>
                    <input type="number" step="0.01" min="0" name="delivery_charge" id="delivery_charge" value="<?=h($RowDetails['delivery_charge'] ?? '')?>" required>
                </div>
                <div class="form-group">
                    <label for="free_over">Free delivery over (Rs.)</label>
                    <input type="number" step="0.01" min="0" name="free_over" id="free_over" value="<?=h($RowDetails['free_over'] ?? '')?>" placeholder="Leave empty = never free">
                    <small class="text-muted">If the order total before delivery is at least this amount, delivery is free for this area.</small>
                </div>
                <div class="form-group">
                    <label for="sort_order">Sort order</label>
                    <input type="number" name="sort_order" id="sort_order" value="<?=h($RowDetails['sort_order'] ?? 0)?>">
                </div>
                <div class="text-right">
                    <button class="btn button color" type="submit"><i class="mdi mdi-content-save"></i> <?=$Id ? 'Update Area' : 'Save Area'?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <h3 class="card-title">Delivery Areas</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th class="text-right">Charge</th>
                            <th class="text-right">Free over</th>
                            <th class="text-right"><i class="mdi mdi-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? foreach ($Areas as $Area) { ?>
                            <tr>
                                <td><b><?=h($Area['name'])?></b></td>
                                <td class="text-right"><?=number_format($Area['delivery_charge'])?></td>
                                <td class="text-right"><?=$Area['free_over'] === null ? '<span class="text-muted">never</span>' : number_format($Area['free_over'])?></td>
                                <td class="text-right text-nowrap">
                                    <form method="post" action="op_save_delivery_area.php" class="d-inline">
                                        <input type="hidden" name="id" value="<?=$Area['id']?>">
                                        <a href="delivery_areas.php?id=<?=$Area['id']?>" class="btn btn-sm btn-light" title="Edit"><i class="mdi mdi-pencil"></i></a>
                                        <button class="btn btn-sm btn-light text-danger" name="action" value="delete" title="Remove area" onclick="return confirm('Remove this area from the website? Past orders keep it.')"><i class="mdi mdi-trash-can-outline"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <? } ?>
                        <? if (!$Areas) { ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No areas yet. Add the areas you deliver to, with their charge.</td></tr>
                        <? } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php"); ?>
