<?php

class PivotTableTabStackedColumn extends PivotTableTabs
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
                    $Data[$Key-1]['name'] = $OldRowName."-".$this->replaceSubRowPrefix($this->ParsedHeader[1][$Key]['value']);
            }
        }
        elseif (count($this->ParsedHeader) == 1)
        {
            foreach ($this->ParsedHeader[0] as $Key => $ColumnData)
                $Data[$Key-1]['name'] = $ColumnData['value'];
        }



        foreach ($this->ParsedData as $RowNumber => $Columns)
        {
            //if (!isset($Columns[array_key_last($Columns)]['is_final_total'])) continue;

            if (!isset($Columns[0]['is_header_row']))
            {
                $XAxisNames[] = str_replace('&nbsp; &nbsp; ', '', $Columns[0]['value']);

                foreach ($Columns as $Key => $ColumnData)
                    if ($Key != 0 and !isset($ColumnData['is_final_total']) and !isset($ColumnData['is_total']))
                        $Data[$Key-1]['data'][] = round($ColumnData['value']);
            }
        }
        ?>
        <div id="stacked_column_chart"></div>
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
                dataLabels: {
                    enabled: true,
                    formatter: function(value, { series, seriesIndex, dataPointIndex, w }) {
                        return value !== 0 ? thousandSep(value) : '-'
                    }
                },
                legend: {
                    position: 'bottom'
                },
                fill: {
                    opacity: 1
                }
            };

            var chart_stacked_column = new ApexCharts(document.querySelector("#stacked_column_chart"), options);
            chart_stacked_column.render();
        </script>
        <?php
    }
}