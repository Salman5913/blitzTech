<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 001, 1/1/2015
 * Time: 3:34 PM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("connection/localhost.php");

class Settings
{
    static function getFromEmailAddress() {
        return self::getSettings("email");
    }

    static function getCompanyName() {
        return self::getSettings("name");
    }

    static function getWebsiteAddress() {
        return self::getSettings("web");
    }

    static function getCompanyTagLine() {
        return self::getSettings("tag_line");
    }

    static function getOpeningDateTime() {
        return self::getSettings("opening_date");
    }

    private static function getSettings($Name)
    {
        $Select = DBQM::query("SELECT `".$Name."` FROM cp_company WHERE id = ".escape(Company::getLoggedInCompanyId()));
        return DBQM::result($Select, 0);
    }
} 