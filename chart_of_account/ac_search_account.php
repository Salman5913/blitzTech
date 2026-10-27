<?php
include_once("../connection/localhost.php");

$oChartOfAccount = new ChartOfAccount();
$Select = $oChartOfAccount->searchAccount($_GET['term']);

$Output = array();
$i=0;

while($Row = DBQM::fetch_assoc($Select))
{
    $Output[$i]['value'] = $Row['ac_name'];
    $Output[$i]['id'] = $Row['id'];

    $i++;
}

echo json_encode($Output);
