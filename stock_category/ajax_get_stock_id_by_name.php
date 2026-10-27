<?php
include_once("../connection/localhost.php");

$oStockCategory = new StockCategory();
echo $oStockCategory->getStockIdByName($_GET['name']);