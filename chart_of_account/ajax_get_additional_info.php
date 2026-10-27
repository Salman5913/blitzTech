<?php
include_once ("../connection/localhost.php");

$Output = [];
$RowAdditionalInfo = ChartOfAccount::getAdditionalInfo($_GET['account_id']);

$Phone = [];
if (!empty($RowAdditionalInfo['ph1']))
    $Phone[] = $RowAdditionalInfo['ph1'];
if (!empty($RowAdditionalInfo['ph2']))
    $Phone[] = $RowAdditionalInfo['ph2'];
if (!empty($RowAdditionalInfo['mob']))
    $Phone[] = $RowAdditionalInfo['mob'];
if (!empty($RowAdditionalInfo['mob2']))
    $Phone[] = $RowAdditionalInfo['mob2'];

$Output['discount_percentage'] = $RowAdditionalInfo['sales_disc_per'];
$Output['credit_limit'] = $RowAdditionalInfo['credit_limit'];
$Output['address'] = $RowAdditionalInfo['address'];
$Output['phone'] = implode(", ", $Phone);

echo json_encode($Output);