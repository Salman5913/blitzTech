<?php
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("connection/localhost.php");
SecurityShell::redirectIfNotAllowedFormSubmission();

if (isset($_POST['submit']))
{
    $oSecurityShell = new SecurityShell();
    if ($oSecurityShell->loginMeIn($_POST['email'], $_POST['password'], $_POST['year_id']))
        Utils::redirect(ProjectHelper::SiteURL());
    else
        ErrorHandler::setError("Invalid Username or Password");

    Utils::redirectToReferer();
}
else
    ErrorHandler::UnauthorizedAccess();