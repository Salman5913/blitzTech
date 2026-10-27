<?php
include_once("../connection/localhost.php");

$oStockCategory = new StockCategory();
$Select = $oStockCategory->getAll();

?>
<table id="stock_categories" class="table table-striped">
    <thead>
        <tr>
            <th>Name</th>
        </tr>
    </thead>
    <? while ($Row = DBQM::fetch_assoc($Select)) {
            if ($Row['is_editable'] == 'N') continue;
    ?>
    <tr>
        <td><a href="manage_stock_category.php?id=<?=$Row['id']?>"><?=$Row['name']?></a></td>
    </tr>
    <? } ?>
</table>