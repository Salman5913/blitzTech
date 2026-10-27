<?php
/**
 * Branch dropdown on the restaurant tabs: switches the branch the restaurant pages work on and goes back to the page
 * (without its query string, since a dish / deal / area id belongs to the old branch).
 */
include_once("../connection/localhost.php");

SecurityShell::redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    ErrorHandler::UnauthorizedAccess();

RestaurantBranch::setCurrentId((int)($_POST['branch_id'] ?? 0));

$Pages = ['manage_web_orders.php', 'manage_menu.php', 'deals.php', 'deals_report.php', 'delivery_areas.php', 'deal_view.php'];
$Return = in_array($_POST['return'] ?? '', $Pages, true) ? $_POST['return'] : 'manage_web_orders.php';

// A deal page shows one deal of the old branch: go to the report instead
Utils::redirect($Return === 'deal_view.php' ? 'deals_report.php' : $Return);
