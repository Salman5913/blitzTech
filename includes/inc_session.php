<?php
/**
 * Session start for every page (included from connection/localhost.php).
 * Sessions live for a year so Full Access users stay logged in until they log out;
 * other users are logged out after 1 minute without activity (SecurityShell::checkIdleTimeout()).
 * Session files are kept in their own folder: the shared XAMPP folder is cleaned by other apps after 24 minutes.
 */
const SESSION_LIFETIME = 31536000; // 1 year

$SessionSavePath = ini_get('session.save_path') ?: sys_get_temp_dir();
if (strpos($SessionSavePath, ';') === false)
{
    $SessionSavePath = rtrim($SessionSavePath, '/\\') . DIRECTORY_SEPARATOR . 'erp_' . substr(md5(__DIR__), 0, 8);
    if (is_dir($SessionSavePath) or @mkdir($SessionSavePath, 0700, true))
        session_save_path($SessionSavePath);
}

ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_httponly', 1);

session_start();
