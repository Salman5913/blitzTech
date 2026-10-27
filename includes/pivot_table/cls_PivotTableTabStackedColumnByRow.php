<?php

class PivotTableTabStackedColumnByRow extends PivotTableTabs
{
    function getTabData()
    {
        $Data = [];
        $XAxisNames = [];

        if (count($this->ParsedHeader) == 2) // means sub header is available
        {
            $OldRowName = '';

            foreach ($this->ParsedHeader[1] as $Key => $ColumnData)
            {
                $OldRowName = $this->ParsedHeader[0][$Key]['value'] ?? $OldRowName;

                if ($Key != 0 and !isset($ColumnData['is_final_total']) and !isset($ColumnData['is_total']))
                    $XAxisNames[] = $OldRowName."-".$this->replaceSubRowPrefix($this->ParsedHeader[1][$Key]['value']);
            }
        }
        elseif (count($this->ParsedHeader) == 1)
        {
            foreach ($this->ParsedHeader[0] as $Key => $ColumnData)
                $XAxisNames[] = $ColumnData['value'];
        }


        $Count=0;
        foreach ($this->ParsedData as $RowNumber => $Columns)
        {
            //if (!isset($Columns[array_key_last($Columns)]['is_final_total'])) continue;

            if (!isset($Columns[0]['is_header_row']))
            {
                foreach ($Columns as $Key => $ColumnData)
                {
                    if ($Key == 0)
                        $Data[$Count]['name'] = $ColumnData['value'];

                    elseif (!isset($ColumnData['is_final_total']) and !isset($ColumnData['is_total']))
                        $Data[$Count]['data'][] = round($ColumnData['value']);
                }
                $Count++;
            }
        }

        ?>
        <div id="stacked_column_by_row_chart"></div>
        <script>
            var options = {
                series: <?=json_encode($Data) ?>,
                chart: {
                    type: 'bar',
                    height: 1000,
                    stacked: true,
                    toolbar: {
                        show: true
                    },
                    zoom: {
                        enabled: true
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(value, { series, seriesIndex, dataPointIndex, w }) {
                        return value !== 0 ? thousandSep(value) : '-'
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value, { series, seriesIndex, dataPointIndex, w }) {
                            return thousandSep(value)
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        borderRadius: 10
                    },
                },
                xaxis: {
                    categories: <?=json_encode($XAxisNames) ?>,
                },
                legend: {
                    position: 'bottom'
                },
                fill: {
                    opacity: 1
                }
            };

            var chart_stacked_column_by_row = new ApexCharts(document.querySelector("#stacked_column_by_row_chart"), options);
            chart_stacked_column_by_row.render();
        </script>
        <?php
    }
}