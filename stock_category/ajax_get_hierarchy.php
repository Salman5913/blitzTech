<?php
include_once("../connection/localhost.php");

$oStockCategory = new StockCategory();
$Select = $oStockCategory->getAllAncestors($_GET['id']);

$Order = [];
while($Row = DBQM::fetch_assoc($Select))
{
	$Order[] = $Row['name'];
}
krsort($Order);
$i=0;
?>
<table class="table">
	<? foreach ($Order as $Name) { ?>
	<tr>
		<td><?=str_repeat(" &nbsp;", $i*2)?><?=$Name?></td>
	</tr>
	<? $i++; } ?>
</table>
