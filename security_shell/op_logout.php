<?php
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("connection/localhost.php");

$wasLoggedIn = SecurityShell::isLoggedIn();
SecurityShell::logout();

// browser logs out a user (not Full Access) after 1 minute without activity
if (($_GET['idle'] ?? '') == 'Y' and $wasLoggedIn)
    ErrorHandler::setError("You were logged out after 1 minute without activity");

Utils::redirect(ProjectHelper::SiteURL()."login.php");