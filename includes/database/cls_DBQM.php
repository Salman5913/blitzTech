<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 24/12/2014
 * Time: 10:25 PM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("../../connection/localhost.php");

/**
 * Class DBQM Database Query Manager
 */
class DBQM
{
    static $LastError = null;
    static $QueryCount = 0;

    /**
     * Runs mysqli_query and handles error
     * @param $String
     * @param bool $LogError
     * @return mysqli_result
     */
    static function query($String, $LogError=true)
    {
        $Connection = DatabaseHelper::getConnection();
        self::$LastError = null;

        try {
            $QueryPerformed = $Connection->query($String);

            if (!$QueryPerformed)
                throw new Exception($Connection->error);

        } catch (Exception $e) {
            self::$LastError = $Connection->error;
            ErrorHandler::setError($Connection->error, $String);
        }

        /*if ($LogError)
        {
            if (!$QueryPerformed) {

            } else
        }*/

        return $QueryPerformed;
    }

    /**
     * @param mysqli_result $Select
     * @return array
     */
    static function fetch_assoc(&$Select)
    {
        if ($Select != false and get_class($Select) == 'mysqli_result')
            return $Select->fetch_assoc();
        return false;
    }

    /**
     * @param mysqli_result $Select
     * @return array
     */
    static function fetch_fields(&$Select)
    {
        if ($Select != false and get_class($Select) == 'mysqli_result')
            return $Select->fetch_fields();
        return false;
    }

    /**
     * @param mysqli_result $Select
     * @param int $RowNo
     * @param int $ColumnNo
     * @return bool|mixed
     */
    public static function result($Select, $RowNo, $ColumnNo = 0)
    {
        $NumberOfRows = DatabaseHelper::getRecordCount($Select);
        if ($NumberOfRows && $RowNo <= ($NumberOfRows - 1) && $RowNo >= 0) {
            $Select->data_seek($RowNo);

            return $Select->fetch_array()[$ColumnNo];
        }
        return false;
    }

	/**
	 * @param mysqli_result $Select
	 */
    public static function reset(&$Select)
    {
	    mysqli_data_seek($Select, 0);
    }
}