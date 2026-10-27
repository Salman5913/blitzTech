<?php
include_once ("../connection/localhost.php");

$oSales = new Sales();
$Select = $oSales->getAllPendingOrders($_GET['customer_id']);
?>
<option value="">Select....</option>
<?
while ($Row = DBQM::fetch_assoc($Select))
{
    $VoucherNumber = $oSales->getVoucherNumberById($Row['sales_order_id']);
    Utils::getDropDownOptionsFromArray([$Row['sales_order_id'] => "SOV-".$VoucherNumber], '');
}