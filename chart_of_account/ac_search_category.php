<?php
include_once("../connection/localhost.php");

$oChartOfAccountCategory = new ChartOfAccountCategory();
$Select = $oChartOfAccountCategory->getAll($_GET['term']);

$Output = array();
$i=0;

while($Row = DBQM::fetch_assoc($Select))
{
	$Output[$i]['value'] = $Row['name'];
	$Output[$i]['id'] = $Row['id'];

	$i++;
}

echo json_encode($Output);
