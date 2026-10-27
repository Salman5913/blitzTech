<?php
include_once("../connection/localhost.php");

$oStockAttribute = new StockAttribute();
$Select = $oStockAttribute->getReport();

?>
<table id="stock_attributes" class="table table-striped">
    <thead>
        <tr>
            <th>Name</th>
        </tr>
    </thead>
    <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
    <tr>
        <td><a href="stock_attr_manager.php?id=<?=$Row['id']?>"><?=$Row['name']?></a></td>
    </tr>
    <? } ?>
</table>