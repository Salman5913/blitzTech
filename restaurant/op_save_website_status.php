<?php
include_once("../connection/localhost.php");

SecurityShell::redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    ErrorHandler::UnauthorizedAccess();

RestaurantWebsite::save(($_POST['is_open'] ?? '') === 'Y', $_POST['open_time'] ?? '', $_POST['close_time'] ?? '', $_POST['closed_message'] ?? '');

$Status = RestaurantWebsite::getStatus();
SuccessHandler::setMessage("Website settings saved. The website is " . ($Status['open'] ? "open" : "closed") . " right now.");

Utils::redirect("website_status.php");
