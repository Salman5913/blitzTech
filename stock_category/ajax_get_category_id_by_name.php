<?php
include_once("../connection/localhost.php");

$oStockCategory = new StockCategory();
echo $oStockCategory->getCategoryIdByName($_GET['name']);