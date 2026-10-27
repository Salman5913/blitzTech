<?php
/**
 * Polled every few seconds by manage_web_orders.php: returns the orders fingerprint
 * (RestaurantOrder::getChangeState()) so the page updates itself when a website order arrives or changes.
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

echo json_encode(RestaurantOrder::getChangeState());
