<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Website Branches';
$_GET['_page_title_text'] = 'Branches customers can order from on the restaurant website';

include_once("../includes/inc_header.php");

$ActiveTab = 'branches';
include_once(__DIR__ . '/_tabs.php');

function h($Value) { return htmlspecialchars((string)$Value, ENT_QUOTES, 'UTF-8'); }

$Branches = [];
$SelectBranches = RestaurantBranch::getAll();
while ($Row = DBQM::fetch_assoc($SelectBranches)) $Branches[] = $Row;

$BranchNames = [];
foreach ($Branches as $Branch) $BranchNames[$Branch['id']] = $Branch['name'];
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <h3 class="card-title">Branches</h3>
            <p class="text-muted">Every branch has its own menu, deals, delivery areas and web orders. The website asks customers which branch to order from; only branches switched on here are offered.</p>
            <form method="post" action="op_save_branches.php">
                <input type="hidden" name="action" value="save">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Address / Phone <small class="text-muted">(shown on website)</small></th>
                                <th class="text-right">Dishes</th>
                                <th class="text-right">Areas</th>
                                <th style="width: 90px">Order</th>
                                <th>On website</th>
                            </tr>
                        </thead>
                        <tbody>
                            <? foreach ($Branches as $Branch) { ?>
                                <tr>
                                    <td><b><?=h($Branch['name'])?></b> <small class="text-muted">#<?=(int)$Branch['id']?></small></td>
                                    <td><small><?=h($Branch['address']) ?: '<span class="text-muted">—</span>'?><?=$Branch['phone'] ? '<br>' . h($Branch['phone']) : ''?></small></td>
                                    <td class="text-right"><?=(int)$Branch['dish_count']?></td>
                                    <td class="text-right"><?=(int)$Branch['area_count']?></td>
                                    <td><input type="number" name="sort_order[<?=(int)$Branch['id']?>]" value="<?=(int)$Branch['sort_order']?>" style="width: 70px"></td>
                                    <td>
                                        <div class="custom-control custom-switch" dir="ltr">
                                            <input type="checkbox" class="custom-control-input" id="on_website_<?=(int)$Branch['id']?>" name="on_website[]" value="<?=(int)$Branch['id']?>" <?=$Branch['show_on_website'] ? 'checked' : ''?>>
                                            <label class="custom-control-label" for="on_website_<?=(int)$Branch['id']?>"><?=$Branch['show_on_website'] ? 'Shown' : 'Hidden'?></label>
                                        </div>
                                    </td>
                                </tr>
                            <? } ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted d-block mb-2">Address and phone come from the branch in Branch Manager.</small>
                <div class="text-right">
                    <button class="btn button color" type="submit"><i class="mdi mdi-content-save"></i> Save Branches</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <h3 class="card-title">Copy Menu</h3>
            <p class="text-muted">Start a branch's menu from another branch: copies its website categories, dishes (with options) and deals. Dishes the branch already has are skipped. Delivery areas are not copied.</p>
            <form method="post" action="op_save_branches.php" onsubmit="return confirm('Copy the menu and deals to the selected branch?')">
                <input type="hidden" name="action" value="copy_menu">
                <div class="form-group">
                    <label for="from_branch_id">From branch</label>
                    <select name="from_branch_id" id="from_branch_id">
                        <? Utils::getDropDownOptionsFromArray($BranchNames, '') ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="to_branch_id">To branch</label>
                    <select name="to_branch_id" id="to_branch_id">
                        <? Utils::getDropDownOptionsFromArray($BranchNames, '') ?>
                    </select>
                </div>
                <div class="text-right">
                    <button class="btn button color" type="submit"><i class="mdi mdi-content-copy"></i> Copy Menu</button>
                </div>
            </form>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php"); ?>
