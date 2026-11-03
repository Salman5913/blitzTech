<?php
/**
 * Browser calls this while the user is clicking / typing, so the server knows the user is active
 * (connection/localhost.php updates the last activity time). Not used for Full Access users.
 */
include_once("../connection/localhost.php");

header('Content-Type: application/json');
echo json_encode(['logged_in' => SecurityShell::isLoggedIn()]);
