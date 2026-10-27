<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 008, 1/8/2015
 * Time: 12:20 PM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("../../connection/localhost.php");

class PopupWindowManager
{
    private static $SessionName = '__popup_session_manager';
    private static $SessionCount = 0;

    static function addURL($URL)
    {
        $_SESSION[self::$SessionName][] = $URL;
    }

    static function displayPopups()
    {
        foreach($_SESSION[self::$SessionName] as $URL)
        {
            ?>
            window.open('<?=$URL?>', 'win_<?=$_SESSION[self::$SessionCount]++?>');
            <?
        }

        $_SESSION[self::$SessionName] = array();
    }

    static function closeWindow()
    {
        ?>
        <script>window.close();</script>
        <?
    }
}