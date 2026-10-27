<?php
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("connection/localhost.php");

SecurityShell::logout();

Utils::redirect(ProjectHelper::SiteURL()."login.php");