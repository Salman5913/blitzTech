<?php

class PivotTable
{


    protected array $AttachedColumnTypes = [];

    public function __construct(
        protected string $View,
        protected array $PermittedColumns,
        protected array $PermittedValueColumns,
    )
    {

    }

    /**
     * @return string
     */
    public function getView(): string
    {
        return $this->View;
    }

    /**
     * @return array
     */
    public function getPermittedColumns(): array
    {
        return $this->PermittedColumns;
    }

    /**
     * @return array
     */
    public function getPermittedValueColumns(): array
    {
        return $this->PermittedValueColumns;
    }


    protected function attachColumnType($ViewColumnName, PivotTableViewColumnType $eColumnType)
    {
        $this->AttachedColumnTypes[$ViewColumnName] = $eColumnType;
    }

    function getViewColumnType($ViewColumnName)
    {
        return $this->AttachedColumnTypes[$ViewColumnName] ?? null;
    }

    /**
     * @param $ViewColumnName
     * @return array
     */
    function getSelectionValues($ViewColumnName)
    {
        $Datasource = $this->getSelectionValuesDataSource($ViewColumnName);

        if (empty($Datasource)) return [];

        if ($Datasource['type'] == 'table')
        {
            $ColumnName = isset($Datasource['additional_column']) ? ' CONCAT('.$Datasource['column'].", ' - ', ".$Datasource['additional_column'].')' : $Datasource['column'];

            $Select = DBQM::query("SELECT `".$Datasource['column']."` AS column_key, $ColumnName AS column_name FROM `".$Datasource['table']."` ORDER BY `".$Datasource['column']."`");

            $Data = [];
            while ($Row = DBQM::fetch_assoc($Select))
                $Data[$Row['column_key']] = $Row['column_name'];

            return $Data;
        }
        else
        {
            return $Datasource['values'];
        }
    }

    private function getSelectionValuesDataSource($ViewColumnName)
    {
        return match ($this->getViewColumnType($ViewColumnName)) {
            PivotTableViewColumnType::CATEGORY => ['type' => 'table', 'table' => 'acc_chart_of_account', 'column' => 'name'],
            PivotTableViewColumnType::ACCOUNT_NAME => ['type' => 'table', 'table' => 'vw_ac_accounts', 'column' => 'ac_name'],
            PivotTableViewColumnType::MONTH_NUMBERS => ['type' => 'array', 'values' => [
                '1' => 'Jan',
                '2' => 'Feb',
                '3' => 'Mar',
                '4' => 'Apr',
                '5' => 'May',
                '6' => 'Jun',
                '7' => 'Jul',
                '8' => 'Aug',
                '9' => 'Sep',
                '10' => 'Oct',
                '11' => 'Nov',
                '12' => 'Dec'
            ]],
            PivotTableViewColumnType::VOUCHER_TYPE => ['type' => 'table', 'table' => 'vt_voucher_type', 'column' => 'name', 'additional_column' => 'abbr'],
            default => []
        };
    }
}