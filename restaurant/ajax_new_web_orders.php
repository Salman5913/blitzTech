<?php
/**
 * Polled by the header notification bell (includes/inc_header.php) on every ERP page:
 * new website orders of the branch (RestaurantOrder::getNewOrdersState()).
 */
include_once("../connection/localhost.php");

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!SecurityShell::isLoggedIn())
{
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Release the session lock so polling never slows down other ERP pages
session_write_close();

echo json_encode(RestaurantOrder::getNewOrdersState());
