<?php
include_once("../connection/localhost.php");

$oChartOfAccount = new ChartOfAccount();
echo $oChartOfAccount->getAccountIdByName($_GET['name']);