<?php
include_once("../connection/localhost.php");

SecurityShell::redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    ErrorHandler::UnauthorizedAccess();

switch ($_POST['action'] ?? '')
{
    case 'save':
        RestaurantBranch::saveSettings($_POST['on_website'] ?? [], $_POST['sort_order'] ?? []);
        SuccessHandler::setMessage("Website branches saved.");
        break;

    case 'copy_menu':
        list($Dishes, $Deals, $Error) = RestaurantBranch::copyMenu((int)($_POST['from_branch_id'] ?? 0), (int)($_POST['to_branch_id'] ?? 0));

        if ($Error)
            ErrorHandler::setError($Error);
        else
            SuccessHandler::setMessage("Copied $Dishes " . ($Dishes == 1 ? "dish" : "dishes") . " and $Deals " . ($Deals == 1 ? "deal" : "deals") . " to " . Branch::getName((int)$_POST['to_branch_id']) . ".");
        break;
}

Utils::redirect("branches.php");
