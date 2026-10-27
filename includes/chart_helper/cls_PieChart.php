<?php

class PieChart extends ChartHelper
{
    protected array $ValuesData = [];

    public function __construct()
    {
        parent::__construct('pie');
    }

    function setDataFromSelect(mysqli_result $Select, string $NameColumn, string $ValueColumn)
    {
        while ($Row = DBQM::fetch_assoc($Select))
        {
            $this->ValuesData[] = (int) $Row[$ValueColumn];
            $this->ColumnName[] = $Row[$NameColumn];
        }
    }

    /**
     * @param array $XAsisNames
     * @param array $YAxisValuesArray [0,1,2,3,4,100,102,102,103]
     * @return void
     */
    function setDataFromArray(array $XAsisNames, array $YAxisValuesArray)
    {
        $this->ColumnName = $XAsisNames;
        $this->ValuesData = $this->valueArrayToFloat($YAxisValuesArray);
    }

    protected function getChartOptions() : array
    {
        return [
            'series' => $this->ValuesData
        ];
    }
}