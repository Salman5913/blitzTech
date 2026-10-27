<?php
include_once ("../connection/localhost.php");

$VoucherTypeId = $_GET['action_voucher_type_id'];
$VoucherClass = VoucherType::getVoucherClass($VoucherTypeId);

$Allowed = true;

/** @var BasicVoucher $oBasicVoucher */
$oBasicVoucher = new $VoucherClass();

foreach ($_GET['distinct_id'] as $DistinctId)
{
    if (!VoucherStatus::isAllowedForApprove($_GET['voucher_status'][$DistinctId]))
        $Allowed = false;

    if (!$Allowed)
        continue;
}

if ($Allowed)
{
    foreach ($_GET['distinct_id'] as $DistinctId)
    {
        $RowVoucherDetails = DistinctId::getVoucherDetails($DistinctId);
        $oBasicVoucher->setVariablesForSaveManually(
            $RowVoucherDetails['voucher_id'],
            $RowVoucherDetails['voucher_type_id'],
            $RowVoucherDetails['cost_center_id'],
            $RowVoucherDetails['project_id']
        );

        $oBasicVoucher->setDeleteDataUponSave(false);
        $oBasicVoucher->initForSave();

        $oBasicVoucher->performTransactions();
    }

    SuccessHandler::setMessage("All selected vouchers hve been approved successfully.");
}
else
    ErrorHandler::setError("Please select only unapproved vouchers.");

Utils::redirectToReferer();