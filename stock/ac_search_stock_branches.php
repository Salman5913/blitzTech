<?php
include_once("../connection/localhost.php");

list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_GET['stock_id']);

$Select = StockReport::getStockBranches($StockId, $AttributeId, $_GET['term'], $_GET['distinct_id']);

$Output = array();
$i=0;

while($Row = DBQM::fetch_assoc($Select))
{
    $Output[$i]['label'] = $Row['branch_name']." (".Utils::format($Row['available_qty'], 0)." Pcs)";
    $Output[$i]['value'] = $Row['branch_name'];
    $Output[$i]['id'] = $Row['branch_id'];

    $i++;
}

if (DatabaseHelper::isRecordSetEmpty($Select))
{
    $Output[$i]['value'] = "0 Stock Available";
}

echo json_encode($Output);
