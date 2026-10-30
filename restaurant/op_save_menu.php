<?php
include_once("../connection/localhost.php");

SecurityShell::redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    ErrorHandler::UnauthorizedAccess();

$Id = (int)($_POST['id'] ?? 0);

switch ($_POST['action'] ?? '')
{
    case 'save':
        // Dishes can only be edited here, not added
        if (!$Id || !RestaurantMenu::getDetails($Id))
        {
            ErrorHandler::setError("Choose a dish from the list to edit.");
            Utils::redirect("manage_menu.php");
        }

        $Data = $_POST;

        // "stock::variation" from the dropdown; linked dishes default to the item's (variation's) name and price
        $Data['erp_stock_id'] = $Data['erp_attribute_id'] = '';
        $RowStock = !empty($_POST['erp_stock']) ? RestaurantMenu::getSellable($_POST['erp_stock']) : null;
        if ($RowStock)
        {
            $Data['erp_stock_id'] = $RowStock['stock_id'];
            $Data['erp_attribute_id'] = $RowStock['attribute_id'];
            if (trim($Data['name']) === '')
                $Data['name'] = $RowStock['name'];
            if ($Data['price'] === '')
                $Data['price'] = $RowStock['price'];
        }
        elseif (!empty($_POST['erp_stock']))
            list($Data['erp_stock_id'], $Data['erp_attribute_id']) = array_pad(Stock::splitStockIdAttributeId($_POST['erp_stock']), 2, '');

        if (trim($Data['name']) === '' || trim($Data['category']) === '')
        {
            ErrorHandler::setError("Please enter a name (or choose a stock item) and a website category.");
            Utils::redirectToReferer();
        }

        $Id = RestaurantMenu::save($Id, $Data);
        SuccessHandler::setMessage("\"" . $Data['name'] . "\" saved to the website menu.");
        Utils::redirect("manage_menu.php?id=" . $Id);

    case 'categories':
        RestaurantMenu::saveCategories($_POST['website_name'] ?? [], $_POST['on_website'] ?? []);
        SuccessHandler::setMessage("Website categories saved. Items in categories that are switched on are shown on the website; the rest are hidden.");
        break;

    case 'sold_out':
    case 'available':
        RestaurantMenu::setFlag($Id, 'available', $_POST['action'] == 'available');
        break;

    case 'hide':
    case 'show':
        RestaurantMenu::setFlag($Id, 'is_visible', $_POST['action'] == 'show');
        break;

    // Hero slider on the website's home page (per branch: each branch's menu has its own hero dishes)
    case 'hero_on':
    case 'hero_off':
        RestaurantMenu::setFlag($Id, 'show_in_hero', $_POST['action'] == 'hero_on');
        break;

    case 'hide_category':
    case 'show_category':
        $Show = $_POST['action'] == 'show_category';
        RestaurantMenu::setCategoryVisible($_POST['category'] ?? '', $Show);
        SuccessHandler::setMessage("\"" . ($_POST['category'] ?? '') . "\" is now " . ($Show ? "shown on" : "hidden from") . " the website.");
        break;

    case 'delete':
        RestaurantMenu::delete($Id);
        SuccessHandler::setMessage("Dish deleted from the website menu.");
        break;

    /* ************* POS menu (manage_menu.php?view=pos) ************* */
    case 'pos_hide_item':
    case 'pos_show_item':
        RestaurantMenu::setPOSItemFlag($_POST['stock_key'] ?? '', 'is_hidden', $_POST['action'] == 'pos_hide_item');
        Utils::redirect("manage_menu.php?view=pos");

    case 'pos_sold_out':
    case 'pos_available':
        RestaurantMenu::setPOSItemFlag($_POST['stock_key'] ?? '', 'is_unavailable', $_POST['action'] == 'pos_sold_out');
        Utils::redirect("manage_menu.php?view=pos");

    case 'pos_hide_category':
    case 'pos_show_category':
        $Show = $_POST['action'] == 'pos_show_category';
        RestaurantMenu::setPOSCategoryVisible($_POST['stock_category_id'] ?? 0, $Show);
        SuccessHandler::setMessage("\"" . ($_POST['category'] ?? '') . "\" is now " . ($Show ? "shown on" : "hidden from") . " the POS.");
        Utils::redirect("manage_menu.php?view=pos");
}

Utils::redirect("manage_menu.php");
