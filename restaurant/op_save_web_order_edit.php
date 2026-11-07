<?php
include_once("../connection/localhost.php");

SecurityShell::redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    ErrorHandler::UnauthorizedAccess();

$OrderId = (int)$_POST['id'];
list($isSuccess, $Message) = RestaurantOrder::saveEdit($OrderId, $_POST);

if ($isSuccess)
{
    SuccessHandler::setMessage($Message);
    Utils::redirect(ProjectHelper::SiteURL() . "restaurant/manage_web_orders.php");
}

ErrorHandler::setError($Message);
Utils::redirect(ProjectHelper::SiteURL() . "restaurant/web_order_edit.php?id=" . $OrderId);
