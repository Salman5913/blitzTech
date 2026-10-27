<?php
error_reporting(E_ERROR);
set_time_limit(90);

date_default_timezone_set("Asia/Karachi");

session_start();

if (is_file("includes/functions.php"))
	include("includes/functions.php");	
elseif (is_file("../includes/functions.php"))
	include("../includes/functions.php");
elseif (is_file("../../includes/functions.php"))
	include("../../includes/functions.php");
				
# =-=-=-=-=-=-=-=- Donot Edit This -=-=-=-=-=-=-=-=-=-=-			
$hostname_localhost = "localhost";
$database_localhost = "demofinac";
$username_localhost = "root";
$password_localhost = "";

$localhost = new mysqli($hostname_localhost, $username_localhost, $password_localhost, $database_localhost);
$localhost->set_charset("utf8mb4");

if ($localhost->connect_errno)
	ErrorHandler::showOnErrorPage("Database Connection Error");
# =-=-=-=-=-=-=-=- Donot Edit This -=-=-=-=-=-=-=-=-=-=-