<?php
include_once("../connection/localhost.php");

$oChartOfAccount = new ChartOfAccount();
$oBranch = new Branch();

$Row = $oChartOfAccount->getHRData($_GET['account_id']);

$Array = [];
$Array['emp_id'] = $Row['emp_id'];
$Array['branch_name'] = $oBranch->getDetailsById($Row['branch_id'])['name'];

echo json_encode($Array);
