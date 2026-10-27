<?php
include_once("../connection/localhost.php");

$oChartOfAccount = new ChartOfAccount();

$oChartOfAccount->initForSave();
$_POST['currency_code'] = ProjectHelper::DEFAULT_CURRENCY;

$oChartOfAccount->saveMainTableData([
    $_POST['new_category_id'],
    $_POST['new_name'],
    $_POST['currency_code'],
    'Y',
    'Y'
]);

if ($oChartOfAccount->isVoucherCreatedSuccessfully()) {
    $oChartOfAccount->saveDataTableData([
        '',
        '',
        $_POST['new_mob'],
        '',
        $_POST['new_email1'],
        '',
        $_POST['new_city'],
        $_POST['new_cnic'],
        $_POST['new_ntn'],
        0,
        0,
        $_POST['new_address'],
        ''
    ], ChartOfAccount::IND_BIO_DATA);
}

$Array = array();
$Array['account_id'] = $oChartOfAccount->getVoucherId();
$Array['account_name'] = ChartOfAccount::getName($oChartOfAccount->getVoucherId());

echo json_encode($Array);