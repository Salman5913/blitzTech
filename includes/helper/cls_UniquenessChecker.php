<?php


class UniquenessChecker
{
    const TYPE_COST_CENTER = 'cost_center';
    const TYPE_PM_CLIENT = 'pm_client';

    private static function getTypeData($Type)
    {
        $TableName = '';
        $ColumnName = '';

        switch ($Type)
        {
            case self::TYPE_COST_CENTER:
                $TableName = 'cc_cost_center';
                $ColumnName = 'cost_center_name';
                break;

            case self::TYPE_PM_CLIENT:
                $TableName = 'pm_client';
                $ColumnName = 'project_name';
                break;
        }

        return [$TableName, $ColumnName];
    }

    static function checkForUnique($TableName, $ColumnName, $Value)
    {
        echo " value='".$Value."' data-unique-old-value='".$Value."' data-unique-table='".$TableName."' data-unique-table-column='".$ColumnName."' ";
    }

    static function isUnique($TableName, $ColumnName, $Value)
    {
        $Select = DBQM::query("SELECT * FROM {$TableName} WHERE {$ColumnName} = ".escape($Value));
        return DatabaseHelper::isRecordSetEmpty($Select);
    }

    static function checkForUniqueByType($Type, $Value)
    {
        if (!empty($Type))
        {
            list($TableName, $ColumnName) = self::getTypeData($Type);

            return " data-unique-old-value='".$Value."' data-unique-table='".$TableName."' data-unique-table-column='".$ColumnName."' ";
        }

        return '';
    }
}