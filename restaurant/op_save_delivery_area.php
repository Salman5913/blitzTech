<?php
include_once("../connection/localhost.php");

SecurityShell::redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    ErrorHandler::UnauthorizedAccess();

$Id = (int)($_POST['id'] ?? 0);

switch ($_POST['action'] ?? '')
{
    case 'save':
        list($SavedId, $Error) = RestaurantDeliveryArea::save(
            $Id,
            trim($_POST['name'] ?? ''),
            $_POST['delivery_charge'] ?? '',
            $_POST['free_over'] ?? '',
            $_POST['sort_order'] ?? 0
        );

        if ($Error)
        {
            ErrorHandler::setError($Error);
            Utils::redirectToReferer();
        }

        SuccessHandler::setMessage("\"" . trim($_POST['name']) . "\" saved.");
        Utils::redirect("delivery_areas.php");

    case 'delete':
        RestaurantDeliveryArea::deactivate($Id);
        SuccessHandler::setMessage("Area removed from the website.");
        break;
}

Utils::redirect("delivery_areas.php");
