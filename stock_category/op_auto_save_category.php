<?php
include_once("../connection/localhost.php");

$oStockCategory = new StockCategory();
$oStockCategory->initForSave();

$oStockCategory->saveMainTableData([1, $_POST['new_name']]);
$CategoryId = $oStockCategory->getVoucherId();

$Array = array();
$Array['category_id'] = $CategoryId;
$Array['category_name'] = $_POST['new_name'];

echo json_encode($Array);