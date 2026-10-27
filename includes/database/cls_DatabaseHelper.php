<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 5/15/14
 * Time: 12:26 PM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("../../connection/localhost.php");

class DatabaseHelper
{
    /**
     * @return mysqli
     */
    static function getConnection()
    {
        global $localhost;
        return $localhost;
    }

    /**
     * return true if record set is empty
     * @param $Select mysqli_result
     * @return bool
     */
    static function isRecordSetEmpty($Select) {
        return $Select->num_rows == 0;
    }

    /**
     * return true if record is deleted or updated successfully
     * @return bool
     */
    static function isRecordDeletedOrUpdatedSuccessfully() {
        return self::getConnection()->affected_rows > 0;
    }

    /**
     * return number of records in passes mysqli_result
     * @param mysqli_result $Select
     * @return int
     */
    static function getRecordCount($Select) {
        if ($Select != null and is_object($Select))
            return $Select->num_rows;
        else
            return 0;
    }

    /**
     * @return bool|int
     */
    static function getLastInsertId()
    {
        if (empty(DBQM::$LastError))
            return self::getConnection()->insert_id;
        else
            return false;
    }


    static function TIME()
    {
        return date(" H:i:s");
    }

    static function DATE($Format='Y-m-d')
    {
        return date($Format);
    }

    static function YEAR($Date='')
    {
        $YearDate = empty($Date) ? DatabaseHelper::DATE() : $Date;
        return date('Y', strtotime($YearDate));
    }

    static function YESTERDAY($Format='Y-m-d')
    {
        return date($Format, strtotime("-1 days"));
    }

    static function NOW() {
        return date("Y-m-d H:i:s");
    }

    static function getYearEndDate($Date)
    {
        return self::YEAR($Date)."-12-31";
    }

    /**
     * Get All foreign keys connection (table names, connected column) for main table
     * @param $TableName
     * @return mysqli_result
     */
    static function getTableSchema($TableName)
    {
        return DBQM::query("
            SELECT
                `column_name`, 
                `referenced_table_name` AS foreign_table, 
                `referenced_column_name`  AS foreign_column 
            FROM
                `information_schema`.`KEY_COLUMN_USAGE`
            WHERE
                `constraint_schema` = SCHEMA()
            AND
                `table_name` = ".escape($TableName)."
            AND
                `referenced_column_name` IS NOT NULL
            ORDER BY
                `column_name`;
        ");
    }

    /**
     * @param $TableName
     * @return mysqli_result
     */
    static function getTableDescription($TableName)
    {
        return DBQM::query("SHOW FULL COLUMNS FROM `".$TableName."`");
    }
}