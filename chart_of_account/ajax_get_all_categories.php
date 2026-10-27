<?php
include_once("../connection/localhost.php");

$oChartOfAccountCategory = new ChartOfAccountCategory();
$Select = $oChartOfAccountCategory->getAll();

?>
<table id="coa_categories" class="table table-striped table-sm table-small-font">
    <thead>
        <tr>
            <th>COA Name</th>
        </tr>
    </thead>
    <? while ($Row = DBQM::fetch_assoc($Select)) {
            if ($Row['is_editable'] == 'N') continue;
    ?>
    <tr>
        <td><a href="manage_coa_categories.php?id=<?=$Row['id']?>"><?=$Row['name']?></a></td>
    </tr>
    <? } ?>
</table>