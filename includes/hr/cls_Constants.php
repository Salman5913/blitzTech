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

class Constants
{
    static function getTeaAllowance()
    {
		return self::getConstants("tea_allowance");
    }

	static function getAttendanceAllowance()
	{
		return self::getConstants("attendance_allowance");
	}

	static function getDaysInMonth()
	{
		return self::getConstants("days_in_month");
	}

	private static function getConstants($ColumnName)
	{
		$Select = DBQM::query("SELECT ".$ColumnName." FROM hr_constants");
		return DBQM::result($Select, 0);
	}

	static function setConstants($Name, $Value)
	{
		$Select = DBQM::query("UPDATE hr_constants SET `{$Name}` = ".escape($Value));
		return DBQM::result($Select, 0);
	}

	static function getWholeNumberDistinctId()
    {
        return 0; // 683
    }
} 