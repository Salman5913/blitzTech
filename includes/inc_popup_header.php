<?
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("connection/localhost.php");

$SiteURL = ProjectHelper::SiteURL();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Invoice</title>
    <link rel="shortcut icon" href="<?=$SiteURL?>images/favicon.ico" type="image/x-icon">
    <link rel="icon" href="<?=$SiteURL?>images/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="<?=$SiteURL?>assets/css/bootstrap.css" type="text/css">
    <link rel="stylesheet" href="<?=$SiteURL?>assets/css/print.css" type="text/css">
    <script src="<?=$SiteURL?>assets/libs/jquery/jquery.min.js"></script>
    <link rel="stylesheet" href="<?=$SiteURL?>assets/css/icons.css">
</head>
<body class="popup_window report container">