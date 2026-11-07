<?php
include_once("../connection/localhost.php");

SecurityShell::redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    ErrorHandler::UnauthorizedAccess();

list($isSuccess, $Message) = RestaurantOrder::setStatus((int)$_POST['id'], $_POST['status'] ?? '');

if ($isSuccess)
    SuccessHandler::setMessage($Message);
else
    ErrorHandler::setError($Message);

// Completed & invoiced: Website Invoices go to the POS report, older orders invoiced as SIV open their voucher
if ($isSuccess and ($_POST['status'] ?? '') == RestaurantOrder::STATUS_COMPLETED)
{
    $Order = RestaurantOrder::getOrder((int)$_POST['id']);
    if (!empty($Order['erp_sales_id']))
    {
        $InvoiceVoucherTypeId = RestaurantOrder::getInvoiceVoucherTypeId($Order['erp_sales_id']);
        if ($InvoiceVoucherTypeId == VoucherType::WIV)
            Utils::redirect(ProjectHelper::SiteURL()."pos/report_sales.php?voucher_type_id=".VoucherType::WIV);

        Utils::redirect(VoucherType::getLink($InvoiceVoucherTypeId, $Order['erp_sales_id']));
    }
}

Utils::redirectToReferer();
