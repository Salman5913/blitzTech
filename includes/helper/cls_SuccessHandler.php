<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 6/27/14
 * Time: 2:40 PM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("../../connection/localhost.php");

class SuccessHandler
{
    private static $SuccessPageName = "success.php";
    private static $SessionName = "__success";

    /**
     * Stores multiple errors in session stack. You can get errors by calling showErrors
     * @param $Message
     */
    static function setMessage($Message)
    {
        if (!isset($_SESSION[self::$SessionName]))
            $_SESSION[self::$SessionName] = array();

        $_SESSION[self::$SessionName][] = $Message;
    }

    /**
     * Redirect to error page with error supplied in parameter
     * @param $Message
     */
    static function showOnSuccessPage($Message)
    {
        $SuccessPage = ProjectHelper::SiteURL().self::$SuccessPageName."?message=".urlencode($Message);

        if (!headers_sent())
            header("Location: ".$SuccessPage);
        else
            echo '<meta http-equiv="refresh" content="0; url='.$SuccessPage.'" />';

        exit;
    }

    /**
     * @param string $SuccessPageName
     */
    public static function setSuccessPageName($SuccessPageName)
    {
        self::$SuccessPageName = $SuccessPageName;
    }

    /**
     * Returns errors set by setError, and empty up session stack
     * @return array
     */
    private static function showMessages()
    {
        $Output = $_SESSION[self::$SessionName];
        $_SESSION[self::$SessionName] = array();

        return $Output;
    }

    /**
     * returns true, if error available.
     * @return bool
     */
    private static function isMessagesAvailableToDisplay()
    {
        return (isset($_SESSION[self::$SessionName]) and count($_SESSION[self::$SessionName]) > 0);
    }

    static function displayMessages()
    {
        if (SuccessHandler::isMessagesAvailableToDisplay())
        {   ?>
            <div class="success-list status-list">
                <div class="heading bg-success"> <strong>Success</strong> <button type="button" class="fa fa-times"></button> </div>
                <div class="clearfix"></div>
                <ul>
                    <?
                    $SuccessMessages = SuccessHandler::showMessages();
                    foreach ($SuccessMessages as $Message) {
                        ?>
                        <li><i class="fa fa-check-circle"></i><?=$Message?></li>
                    <? } ?>
                </ul>
            </div>
            <?
        }
    }
} 