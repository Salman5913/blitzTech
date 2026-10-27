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

Utils::redirectToReferer();
