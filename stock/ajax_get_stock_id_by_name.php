<?php
include_once("../connection/localhost.php");

$oStock = new Stock();
echo $oStock->getStockIdByName($_GET['name']);