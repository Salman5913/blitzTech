<?php
error_reporting(E_ERROR);
set_time_limit(90);

date_default_timezone_set("Asia/Karachi");

include_once(__DIR__ . "/../includes/inc_session.php");

if (is_file("includes/functions.php"))
	include("includes/functions.php");	
elseif (is_file("../includes/functions.php"))
	include("../includes/functions.php");
elseif (is_file("../../includes/functions.php"))
	include("../../includes/functions.php");
				
# =-=-=-=-=-=-=-=- Donot Edit This -=-=-=-=-=-=-=-=-=-=-			
$hostname_localhost = "13.140.214.164";
$database_localhost = "erp_db";
$username_localhost = "erp_user";
$password_localhost = "SA$12345";

$localhost = new mysqli($hostname_localhost, $username_localhost, $password_localhost, $database_localhost);
$localhost->set_charset("utf8mb4");

if ($localhost->connect_errno)
	ErrorHandler::showOnErrorPage("Database Connection Error");
# =-=-=-=-=-=-=-=- Donot Edit This -=-=-=-=-=-=-=-=-=-=-

// Logs out users (not Full Access) after 1 minute without activity
SecurityShell::checkIdleTimeout();
