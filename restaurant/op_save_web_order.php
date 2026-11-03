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

// Completed & invoiced: open the new Website Invoice
if ($isSuccess and ($_POST['status'] ?? '') == RestaurantOrder::STATUS_COMPLETED)
{
    $Order = RestaurantOrder::getOrder((int)$_POST['id']);
    if (!empty($Order['erp_sales_id']))
    {
        $InvoiceVoucherTypeId = RestaurantOrder::getInvoiceVoucherTypeId($Order['erp_sales_id']);
        Utils::redirect(VoucherType::getLink($InvoiceVoucherTypeId, $Order['erp_sales_id']));
    }
}

Utils::redirectToReferer();
