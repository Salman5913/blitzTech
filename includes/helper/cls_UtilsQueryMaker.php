<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 4/1/2015
 * Time: 1:28 PM
 */

class UtilsQueryMaker
{
    static function equal($ColumnName, $Value)
    {
        if ($Value != '')
            return " AND ".$ColumnName." = ".escape($Value);
        else
            return '';
    }

    static function customOperator($Operator, $ColumnName, $Value)
    {
        if ($Value != '')
            return " AND ".$ColumnName." ".$Operator." ".escape($Value);
        else
            return '';
    }

    static function like($ColumnName, $Value, $Equals = '', $InputType = "text")
    {
        if ($Value != $Equals)
            return " AND ".$ColumnName." LIKE ".escape("%".$Value."%", $InputType);
        else
            return '';
    }

    static function notLike($ColumnName, $Value, $Equals = '', $InputType = "text")
    {
        if ($Value != $Equals)
            return " AND ".$ColumnName." NOT LIKE ".escape("%".$Value."%", $InputType);
        else
            return '';
    }

    static function inOrEqual($ColumnName, array|string $Value)
    {
        if (is_array($Value) and count($Value) > 0)
            return " AND ".$ColumnName." IN (".implode(", ", array_map("escape", $Value)).") ";
        else if (!is_array($Value) AND $Value != '')
            return " AND ".$ColumnName." = ".escape($Value);
        else
            return '';
    }

    static function equalOrNull($ColumnName, $Value)
    {
        if ($Value != '')
            return " AND ".$ColumnName." = ".escape($Value);
        else
            return " AND ".$ColumnName." IS NULL ";
    }

    static function stock($StockIdColumn, $AttributeIdColumn, $Value)
    {
        if (!empty($Value))
        {
            list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($Value);
            return " AND $StockIdColumn = ".escape($StockId)." ".(!empty($AttributeId) ? " AND $AttributeIdColumn = ".escape($AttributeId) : '')." ";
        }
        return '';
    }

    static function date($ColumnName, $DateFrom, $DateTill)
    {
        if ($DateFrom != '' and $DateTill != '')
        {
            $DateFrom = DateEncode($DateFrom);
            $DateTill = DateEncode($DateTill);

            return " AND ".$ColumnName." BETWEEN ".escape($DateFrom, "date")." AND ".escape($DateTill." 23:59:59", "date");
        }
        else if ($DateFrom != '')
        {
            $DateFrom = DateEncode($DateFrom);
            return " AND ".$ColumnName." >= ".escape($DateFrom, "date");
        }
        else if ($DateTill != '')
        {
            $DateTill = DateEncode($DateTill);
            return " AND ".$ColumnName." <= ".escape($DateTill." 23:59:59", "date");
        }
        return "";
    }

    static function fullTextSearch(array $ColumnNames, $Text)
    {
        if ($Text != '')
            return " AND MATCH(".implode(",", $ColumnNames).") AGAINST (".escape($Text)." IN NATURAL LANGUAGE MODE) ";

        return "";
    }

    static function ownVoucherCheck($CreatedByColumnName)
    {
        if (SecurityShell::isAccess("Master Details", "Own Vouchers Only"))
            return self::equal($CreatedByColumnName, SecurityShell::getLoggedInUserId());
    }

}