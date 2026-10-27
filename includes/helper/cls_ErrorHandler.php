<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 6/10/14
 * Time: 4:34 PM
 */

class ErrorHandler {

    private static $ErrorPageName = "error.php";
    private static $SessionName = "__error";

    const UNAUTHORIZED_TEXT = 'Unauthorized Access, Please Login';

    /**
     * Stores multiple errors in session stack. You can get errors by calling showErrors
     * @param $Error
     * @param string $Query
     */
    static function setError($Error, $Query='')
    {
        if (!empty($Error)) {
            ErrorHandler::logError($Error, $Query);

            if (!isset($_SESSION[ErrorHandler::$SessionName]))
                $_SESSION[ErrorHandler::$SessionName] = array();

            $_SESSION[ErrorHandler::$SessionName][] = $Error;
        }
    }

    /**
     * Redirect to error page with error supplied in parameter
     * @param $Error
     * @param bool $LogError
     */
    static function showOnErrorPage($Error, bool $LogError=true)
    {
        if ($LogError)
            ErrorHandler::logError($Error);

        $ErrorPage = ProjectHelper::SiteURL().ErrorHandler::$ErrorPageName."?error_msg=".urlencode($Error);

        if (!headers_sent())
            header("Location: ".$ErrorPage);
        else
            echo '<meta http-equiv="refresh" content="0; url='.$ErrorPage.'" />';

        exit;
    }

    /**
     * returns true, if error available.
     * @return bool
     */
    static function hasError()
    {
        return (isset($_SESSION[ErrorHandler::$SessionName]) and count($_SESSION[ErrorHandler::$SessionName]) > 0);
    }

    static function UnauthorizedAccess()
    {
        ErrorHandler::showOnErrorPage("Unauthorized Access", false);
    }

    static private function logError($Error, $Query='')
    {
        if ($Error == self::UNAUTHORIZED_TEXT)
            return;

        $URL = urlencode($_SERVER['PHP_SELF']."?".http_build_query($_GET, "GET_"));
        $Refer = $_SERVER['HTTP_REFERER'];

        DBQM::query("INSERT INTO error_log VALUES (
                                                    0,
                                                    ".escape($URL).",
                                                    ".escape($Refer).",
                                                    ".escape($Error).",
                                                    ".escape($Query).",
                                                    ".escape(date('Y-m-d H:i:s')).",
                                                    ".escape(SecurityShell::getLoggedInUserId(), 'int', true).",
                                                    ".escape(Company::getLoggedInCompanyId(), 'int', true)."
                                                  )", false);
    }

    /**
     * Returns errors set by setError, and empty up session stack
     * @return array
     */
    private static function showErrors()
    {
        $Output = $_SESSION[ErrorHandler::$SessionName];
        $_SESSION[ErrorHandler::$SessionName] = array();

        return $Output;
    }

    /**
     * returns true, if error available.
     * @return bool
     */
    private static function isErrorAvailableToDisplay()
    {
        return (isset($_SESSION[ErrorHandler::$SessionName]) and count($_SESSION[ErrorHandler::$SessionName]) > 0);
    }

    static function displayErrors()
    {
        if (ErrorHandler::isErrorAvailableToDisplay())
        {   ?>
            <div class="error-list status-list">
                <div class="heading bg-danger"> <strong>ERROR</strong> <button type="button" class="fa fa-times"></button> </div>
                <div class="clearfix"></div>
                <ul>
                    <?
                    $Errors = ErrorHandler::showErrors();
                    foreach ($Errors as $Error) {
                        ?>
                        <li><i class="fa fa-times-circle"></i><?=$Error?></li>
                    <? } ?>
                </ul>
            </div>
            <?
        }
    }
}