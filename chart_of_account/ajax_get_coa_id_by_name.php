<?php
include_once("../connection/localhost.php");

$oChartOfAccountCategory = new ChartOfAccountCategory();
echo $oChartOfAccountCategory->getCOAIdByName($_GET['name']);