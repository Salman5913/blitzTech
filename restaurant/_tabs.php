<?php
/**
 * Tab bar for the restaurant website pages. Set $ActiveTab before including: orders, menu, deals, deals_report, areas or branches.
 * The branch dropdown picks the branch these pages work on (RestaurantBranch::getCurrentId()); every branch has its own
 * menu, deals, delivery areas and web orders.
 */
$RestaurantTabs = [
    'orders' => ['Web Orders', 'manage_web_orders.php'],
    'menu' => ['Website Menu', 'manage_menu.php'],
    'deals' => ['Deals', 'deals.php'],
    'deals_report' => ['Deals Report', 'deals_report.php'],
    'areas' => ['Delivery Areas', 'delivery_areas.php'],
    'branches' => ['Branches', 'branches.php'],
];
$RestaurantBranchId = RestaurantBranch::getCurrentId();
?>
<style>
    .restaurant-tabs .nav-link { color: #b57bee; }
    .nav-pills.restaurant-tabs .nav-link.active,
    .nav-pills.restaurant-tabs .nav-link.active:hover { background-color: #b57bee; color: #fff; }
</style>
<div class="d-flex flex-wrap align-items-center mb-3">
    <ul class="nav nav-pills mb-2 mr-auto restaurant-tabs">
        <? foreach ($RestaurantTabs as $Key => $Tab) { ?>
            <li class="nav-item">
                <a class="nav-link  <?=($ActiveTab ?? '') === $Key ? 'active' : ''?>" href="<?=$Tab[1]?>"><?=$Tab[0]?></a>
            </li>
        <? } ?>
    </ul>
    <? if (($ActiveTab ?? '') !== 'branches') { ?>
        <form method="post" action="op_set_branch.php" class="form-inline mb-2">
            <input type="hidden" name="return" value="<?=htmlspecialchars(basename($_SERVER['PHP_SELF']), ENT_QUOTES, 'UTF-8')?>">
            <label for="restaurant_branch_id" class="mr-2 font-weight-bold"><i class="mdi mdi-store-outline"></i>&nbsp;Branch</label>
            <select name="branch_id" id="restaurant_branch_id" onchange="this.form.submit()" style="min-width: 180px">
                <? Utils::getDropDownOptionsFromArray(RestaurantBranch::getOptions(), $RestaurantBranchId) ?>
            </select>
        </form>
    <? } ?>
</div>
