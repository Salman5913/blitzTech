<?php


class DatabaseInsertUpdateHelper
{
    private $TableName;
    private $DistinctId = 0;

    public function __construct($TableName, $DistinctId='')
    {
        $this->TableName = $TableName;
        $this->DistinctId = $DistinctId;
    }

    private function insertData($Values)
    {
        $Query = "INSERT INTO `".$this->TableName."` VALUES (";

        $ColumnData = [];
        $ValueCount = 0;

        $Select = DBQM::query("DESCRIBE `".$this->TableName."`");

        while ($Row = DBQM::fetch_assoc($Select))
        {
            $ColumnName = $Row['Field'];
            $ColumnType = $Row['Type'];
            $isNullAllowed = $Row['Null'] == 'YES';


            $Type = 'text';
            if (strstr($ColumnType, 'int'))
                $Type = 'int';
            elseif (strstr($ColumnType, 'float'))
                $Type = 'double';
            elseif (strstr($ColumnType, 'double'))
                $Type = 'double';




            if ($ColumnName == "id")
                $ColumnData[] = "0";
            elseif ($ColumnName == "distinct_id")
                $ColumnData[] = escape($this->DistinctId, 'int');
            elseif ($ColumnName == "user_id")
                $ColumnData[] = escape(SecurityShell::getLoggedInUserId(), 'int');
            elseif ($ColumnName == "year_id")
                $ColumnData[] = escape(Year::getYearId(), 'int');
            elseif ($ColumnName == "company_id")
                $ColumnData[] = escape(Company::getLoggedInCompanyId(), 'int');
            else
                $ColumnData[] = escape($Values[$ValueCount++], $Type, $isNullAllowed);
        }

        $Query .= implode(", ", $ColumnData);
        $Query .= " ); ";

        DBQM::query($Query);

        return DatabaseHelper::getLastInsertId();
    }

    private function updateData($Values, $Id)
    {
        $Query = "UPDATE `".$this->TableName."` SET ";

        $ColumnData = [];
        $ValueCount = 0;

        $Select = DBQM::query("DESCRIBE `".$this->TableName."`");

        while ($Row = DBQM::fetch_assoc($Select))
        {
            $ColumnName = $Row['Field'];
            $ColumnType = $Row['Type'];
            $isNullAllowed = $Row['Null'] == 'YES';


            $Type = 'text';
            if (strstr($ColumnType, 'int'))
                $Type = 'int';
            elseif (strstr($ColumnType, 'float'))
                $Type = 'double';
            elseif (strstr($ColumnType, 'double'))
                $Type = 'double';


            if ($ColumnName != "id" and $ColumnName != "distinct_id" and $ColumnName != 'user_id' and $ColumnName != 'year_id' and $ColumnName != 'company_id')
                $ColumnData[] = " `$ColumnName` = ".escape($Values[$ValueCount++], $Type, $isNullAllowed);
        }

        $Query .= implode(", ", $ColumnData);

        $Query .= " WHERE id = ".escape($Id, 'int');

        DBQM::query($Query);
    }

    function saveData(array $Values, $Id)
    {
        if (empty($Id))
            return $this->insertData($Values);
        else
        {
            $this->updateData($Values, $Id);
            return $Id;
        }
    }
}