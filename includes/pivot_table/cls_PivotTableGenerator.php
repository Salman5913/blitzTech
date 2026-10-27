<?php

class PivotTableGenerator
{
    protected array $Dataset = [];
    protected array $DatasetColumns = [];
    protected int $TotalColumns = 0;

    protected array $ParsedData = [];
    protected array $ParsedHeader = [];
    protected array $ColumnTotal = [];

    public function __construct(
        private readonly string $View,
        private readonly string $Column,
        private readonly string $Row,
        private readonly string $Value,
        private readonly string|null $SubColumn,
        private readonly string|null $SubRow,
        private readonly bool $isSubColumnTotalRequired,
        private readonly bool $isSubRowTotalRequired,
        private $SelectFilters,
        private $SelectSort,
        private $RowDetails,
    )
    {
        $this->traverseDataset();

        $this->parseTableHeader();
        $this->parseTableBody();
    }

    private function queryMaker()
    {
        $Where = '';
        $Sort = [];

        while ($RowFilter = DBQM::fetch_assoc($this->SelectFilters))
        {
            $Column = $RowFilter['filter_column'];
            $Type = $RowFilter['filter_condition'];
            $Value = $RowFilter['filter_value'];
            
            if (!empty($Value))
            {
                $Where .= match ($Type) {
                    '=' => UtilsQueryMaker::equal($Column, $Value),
                    'contains' => UtilsQueryMaker::like($Column, $Value),
                    'inorequal' => UtilsQueryMaker::inOrEqual($Column, explode(",", $Value)),
                    '<>' => UtilsQueryMaker::customOperator($Type, $Column, $Value),
                    default => UtilsQueryMaker::customOperator($Type, $Column, floatval($Value))
                };
            }
        }


        while ($RowSort = DBQM::fetch_assoc($this->SelectSort))
        {
            $Column = $RowSort['sort_column'];
            $Type = $RowSort['sort_type'];

            if (!empty($Column))
            {
                $Sort[] = $Column." ".$Type;
            }
        }

        $Sort = empty($Sort) ? '' : (" ORDER BY ".implode(", ", $Sort));

        $Query = "
            SELECT 
                ".$this->Row." as row_name,
                ".(empty($this->SubRow) ? "''" : $this->SubRow)." as sub_row_name,
                ".(empty($this->RowDetails['value_aggregation']) ? "SUM" : $this->RowDetails['value_aggregation'])."(".$this->Value.") as value_total, 
                ".$this->Column." as column_name,
                ".(empty($this->SubColumn) ? "''" : $this->SubColumn)." as sub_column_name 
            FROM 
                ".$this->View." 
            WHERE
                1=1
                {$Where}
            GROUP BY 
                ".$this->Row.",
                ".(!empty($this->SubRow) ? $this->SubRow.", " : '')." 
                ".$this->Column."
                ".(!empty($this->SubColumn) ? ",".$this->SubColumn : '')." 
            {$Sort}
        ";

        return DBQM::query($Query);
    }

    private function traverseDataset()
    {
        $Select = $this->queryMaker();

        while ($Row = DBQM::fetch_assoc($Select))
        {
            if (empty($this->SubColumn))
            {
                $this->Dataset[$Row['row_name']][$Row['sub_row_name']][$Row['column_name']] = $Row['value_total'];
                $this->DatasetColumns[$Row['column_name']] = $Row['column_name'];
            }
            else
            {
                $this->Dataset[$Row['row_name']][$Row['sub_row_name']][$Row['column_name']][$Row['sub_column_name']] = $Row['value_total'];
                $this->DatasetColumns[$Row['column_name']][$Row['sub_column_name']] = $Row['sub_column_name'];
            }
        }

        if (!empty($this->SubColumn))
            $this->TotalColumns = array_sum( array_map( 'count', $this->DatasetColumns ) );
        else
            $this->TotalColumns = count($this->DatasetColumns);
    }

    private function parseTableHeader()
    {
        $HeaderColumnCount = 0;
        $this->ParsedHeader[0][$HeaderColumnCount++]['value'] = '';

        foreach ($this->DatasetColumns as $ColumnName => $ColumnData)
        {
            /* If sub columns are available */
            if (is_array($ColumnData))
            {
                $this->ParsedHeader[0][$HeaderColumnCount]['colspan'] = count($ColumnData)+($this->isSubRowTotalRequired ? 1 : 0);
                $this->ParsedHeader[0][$HeaderColumnCount]['value'] = $ColumnName;

                $HeaderColumnCount += count($ColumnData)+($this->isSubRowTotalRequired ? 1 : 0); // keeps the array index same as second row
            }
            else
                $this->ParsedHeader[0][$HeaderColumnCount++]['value'] = $ColumnName;
        }
        $this->ParsedHeader[0][$HeaderColumnCount++]['value'] = '';


        /* If sub columns are available, add second header row */
        if (is_array($this->DatasetColumns[array_key_first($this->DatasetColumns)]))
        {
            $HeaderColumnCount = 0;
            $this->ParsedHeader[1][$HeaderColumnCount++]['value'] = '';

            foreach ($this->DatasetColumns as $ColumnName => $ColumnData)
            {
                foreach ($ColumnData as $SubColumn)
                    $this->ParsedHeader[1][$HeaderColumnCount++]['value'] = $SubColumn;

                if ($this->isSubRowTotalRequired)
                {
                    $this->ParsedHeader[1][$HeaderColumnCount]['value'] = 'Total';
                    $this->ParsedHeader[1][$HeaderColumnCount++]['is_total'] = true;
                }
            }
            $this->ParsedHeader[1][$HeaderColumnCount]['value'] = '';
            $this->ParsedHeader[1][$HeaderColumnCount++]['is_final_total'] = true;
        }
    }

    private function parseTableBody()
    {
        $RowNumber = 0;
        foreach ($this->Dataset as $RowName => $SubRowNColumnValues)
        {
            $SubColumnTotal = [];
            $ColumnNumber = 0;

            /* If Sub-row is there, add 1st row as master row with all columns in colspan */
            if (!empty($this->SubRow))
            {
                $this->setRowColumnData($RowNumber, $ColumnNumber, $RowName, [
                    'is_name' => true,
                    'is_header_row' => true,
                    'colspan' => ($this->TotalColumns+2+($this->isSubRowTotalRequired ? count(array_keys($SubRowNColumnValues)) : 0))
                ]);

                $RowNumber++;
                $ColumnNumber = 0;
            }

            /* Traverse all rows */
            foreach ($SubRowNColumnValues as $SubRowName => $RowData)
            {
                $FinalRowTotal = 0;

                $this->ColumnTotal[$ColumnNumber] += 0;
                $this->setRowColumnData($RowNumber, $ColumnNumber, empty($SubRowName) ? $RowName : ("&nbsp; &nbsp; ".$SubRowName), ['is_name' => true]);


                foreach ($this->DatasetColumns as $ColumnName => $ColumnData)
                {
                    if (is_array($ColumnData))
                    {
                        $SubRowTotal = 0;
                        foreach ($ColumnData as $SubColumnKey => $SubColumn)
                        {
                            $ColumnValue = $RowData[$ColumnName][$SubColumn];
                            $SubRowTotal += $ColumnValue;
                            $FinalRowTotal += $ColumnValue;
                            $SubColumnTotal[$ColumnName][$SubColumn] += $ColumnValue;

                            $this->ColumnTotal[$ColumnNumber] += $ColumnValue;
                            $this->setRowColumnData($RowNumber, $ColumnNumber, $ColumnValue);
                        }

                        if ($this->isSubRowTotalRequired)
                        {
                            $this->ColumnTotal[$ColumnNumber] += $SubRowTotal;
                            $this->setRowColumnData($RowNumber, $ColumnNumber, $SubRowTotal, ['is_total' => true]);
                        }
                    }
                    else
                    {
                        $FinalRowTotal += $RowData[$ColumnData];
                        $SubColumnTotal[$ColumnData] += $RowData[$ColumnData];

                        $this->ColumnTotal[$ColumnNumber] += $RowData[$ColumnData];
                        $this->setRowColumnData($RowNumber, $ColumnNumber, $RowData[$ColumnData]);
                    }
                }

                $this->ColumnTotal[$ColumnNumber] += $FinalRowTotal;
                $this->setRowColumnData($RowNumber, $ColumnNumber, $FinalRowTotal, ['is_final_total' => true]);

                $RowNumber++;
                $ColumnNumber = 0;
            }

            if ($this->isSubColumnTotalRequired)
            {
                $FinalRowTotal = 0;

                $this->ColumnTotal[$ColumnNumber] += 0;
                $this->setRowColumnData($RowNumber, $ColumnNumber, 'Total', ['is_total' => true, 'is_name' => true]);

                foreach ($this->DatasetColumns as $ColumnName => $ColumnData)
                {
                    if (is_array($ColumnData))
                    {
                        foreach ($ColumnData as $SubColumnKey => $SubColumn)
                        {
                            $FinalRowTotal += $SubColumnTotal[$ColumnName][$SubColumn];
                            $this->setRowColumnData($RowNumber, $ColumnNumber, $SubColumnTotal[$ColumnName][$SubColumn], ['is_total' => true]);
                        }

                        if ($this->isSubRowTotalRequired)
                        {
                            $Value = array_sum($SubColumnTotal[$ColumnName]);
                            $this->setRowColumnData($RowNumber, $ColumnNumber, $Value, ['is_total' => true]);
                        }
                    }
                    else
                    {
                        $this->ColumnTotal[$ColumnNumber] += $SubColumnTotal[$ColumnData];
                        $this->setRowColumnData($RowNumber, $ColumnNumber, $SubColumnTotal[$ColumnData], ['is_total' => true]);

                        $FinalRowTotal += $SubColumnTotal[$ColumnData];
                    }
                }

                $this->setRowColumnData($RowNumber, $ColumnNumber, $FinalRowTotal, ['is_final_total' => true]);
            }

            $RowNumber++;
        }
    }

    private function setRowColumnData($RowNumber, &$ColumnNumber, $Value, $AdditionalAttribute=[])
    {
        $this->ParsedData[$RowNumber][$ColumnNumber] = [
            'value' => $Value,
            ...$AdditionalAttribute
        ];

        $ColumnNumber++;
    }

    function getParsedHeader()
    {
        return $this->ParsedHeader;
    }

    function getColumnTotal()
    {
        return $this->ColumnTotal;
    }

    function getParsedData()
    {
        return $this->ParsedData;
    }

}