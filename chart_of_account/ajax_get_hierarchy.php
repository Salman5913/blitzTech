<?php
include_once("../connection/localhost.php");

$oChartOfAccountCategory = new ChartOfAccountCategory();
$Select = $oChartOfAccountCategory->getAllAncestors($_GET['id']);

$Order = [];
while($Row = DBQM::fetch_assoc($Select))
{
	$Order[] = $Row['name'];
}
krsort($Order);
$i=0;
?>
<table class="table table-sm">
	<? foreach ($Order as $Name) { ?>
	<tr>
		<td><?=str_repeat(" &nbsp;", $i*2)?><?=$Name?></td>
	</tr>
	<? $i++; } ?>
</table>
