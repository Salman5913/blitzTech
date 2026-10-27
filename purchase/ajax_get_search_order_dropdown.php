<?php
include_once ("../connection/localhost.php");

$oPurchase = new Purchase();
$Select = $oPurchase->getAllPendingOrders($_GET['supplier_id']);
?>
<option value="">Select....</option>
<?
while ($Row = DBQM::fetch_assoc($Select))
{
    $VoucherNumber = $oPurchase->getVoucherNumberById($Row['purchase_order_id']);
    Utils::getDropDownOptionsFromArray([$Row['purchase_order_id'] => "POV-".$VoucherNumber], '');
}