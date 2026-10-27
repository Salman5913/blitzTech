<?php
include_once("../connection/localhost.php");

$oStock = new Stock();
$Select = $oStock->searchStock($_GET['term']);

$Output = array();
$i=0;

while ($Row = DBQM::fetch_assoc($Select))
{
    $Output[$i]['label'] = $Row['ac_stock'];
    $Output[$i]['value'] = $Row['ac_stock'];
    $Output[$i]['id'] = Stock::joinStockIdAttributeId($Row['id'], $Row['attribute_id']);

    $i++;
}

echo json_encode($Output);