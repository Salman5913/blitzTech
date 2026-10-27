<?php
include_once("../connection/localhost.php");
$oStock = new Stock();

list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_GET['stock_id']);

list($Qty, $SP, $CostPrice) = $oStock->getStockAdditionalDetails($StockId, $AttributeId, $_GET['distinct_id'], $_GET['branch_id']);

$Array = array();

$Array['max_qty'] = round(max($Qty, 0),2);
$Array['selling_price'] = round($SP);
$Array['cost_price'] = round($CostPrice);
$Array['wholesale_price'] = 20;

echo json_encode($Array);