<?php

class BarChart extends ChartHelper
{
    protected array $ValuesData = [];
    private ?string $LineColumn = null;

    public function __construct()
    {
        parent::__construct('line');
    }

    function setDataFromSelect(mysqli_result $Select, $NameColumn, array $ValueColumn)
    {
        while ($Row = DBQM::fetch_assoc($Select))
        {
            foreach ($ValueColumn as $ValueColumnName)
                $this->ValuesData[$ValueColumnName][] = $Row[$ValueColumnName];

            $this->ColumnName[] = $Row[$NameColumn];
        }
    }

    function setColumnAsLine($ColumnName)
    {
        $this->LineColumn = $ColumnName;
    }

    /**
     * @param array $XAsisNames
     * @param array $YAxisValuesArrayWithColumnNameAsKey ['Column1' => [0,1,2,3,4], 'Column2' => [100,102,102,103]]
     * @return void
     */
    function setDataFromArray(array $XAsisNames, array $YAxisValuesArrayWithColumnNameAsKey)
    {
        $this->ColumnName = $XAsisNames;
        $this->ValuesData = $YAxisValuesArrayWithColumnNameAsKey;
    }

    protected function getChartOptions() : array
    {
        $YAxisArray = [];
        $Stroke = [];

        foreach ($this->ValuesData AS $ColumnName => $ColumnValues)
        {
            $YAxisArray[] = [
                'name' => ucwords(str_replace("_", " ", $ColumnName)),
                'type' => $ColumnName == $this->LineColumn ? 'line' : 'column',
                'data' => $this->valueArrayToFloat($ColumnValues)
            ];

            $Stroke[] = $ColumnName == $this->LineColumn ? 2 : 0;
        }


        return [
            'series' => $YAxisArray,
            'plotOptions' => [
                'bar' => [
                    'columnWidth' => '45%',
                    'endingShape' => 'rounded'
                ]
            ],
            'fill' => [
                'opacity' => 1
            ],
            'chart' => [
                'stacked' => false,
                'toolbar' => [
                    'show' => false
                ]
            ],
            'dataLabels' => [
                'enabled' => false
            ],
            'stroke' => [
                'width' => $Stroke,
                'curve' => 'smooth',
                'dashArray' => 4
            ],
            'yaxis' => [
                [
                    'axisTicks' => [
                        'show' => false
                    ],
                    'axisBorder' => [
                        'show' => false
                    ],
                    'labels' => [
                        'show' => false
                    ],
                    'tooltip' => [
                        'enabled' => false
                   ]
                ]
            ]
        ];
    }
}