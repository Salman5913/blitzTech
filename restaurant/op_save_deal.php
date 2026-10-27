<?php
include_once("../connection/localhost.php");

SecurityShell::redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    ErrorHandler::UnauthorizedAccess();

$Id = (int)($_POST['id'] ?? 0);

switch ($_POST['action'] ?? '')
{
    case 'save':
        list($SavedId, $Error) = RestaurantDeal::save(
            $Id,
            trim($_POST['name'] ?? ''),
            trim($_POST['description'] ?? ''),
            $_POST['price'] ?? 0,
            $_POST['stock_id'] ?? []
        );

        if ($Error)
        {
            ErrorHandler::setError($Error);
            Utils::redirectToReferer();
        }

        RestaurantDeal::setWebsite($SavedId, ($_POST['show_on_website'] ?? '') == 'Y');
        SuccessHandler::setMessage("\"" . $_POST['name'] . "\" saved.");
        Utils::redirect("deals.php?id=" . $SavedId);

    case 'show':
    case 'hide':
        RestaurantDeal::setWebsite($Id, $_POST['action'] == 'show');
        SuccessHandler::setMessage($_POST['action'] == 'show' ? "Deal shown on the website." : "Deal hidden from the website.");
        break;

    case 'delete':
        RestaurantDeal::deactivate($Id);
        SuccessHandler::setMessage("Deal removed.");
        break;
}

Utils::redirect("deals.php");
