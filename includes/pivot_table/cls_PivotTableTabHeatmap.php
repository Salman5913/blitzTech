<?php

class PivotTableTabHeatmap extends PivotTableTabs
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
                $XAxisNames[] = $this->replaceSubRowPrefix($ColumnData['value']);
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
                        $Data[$Count]['name'] = $this->replaceSubRowPrefix($ColumnData['value']);

                    elseif (!isset($ColumnData['is_final_total']) and !isset($ColumnData['is_total']))
                        $Data[$Count]['data'][] = round($ColumnData['value']);
                }
                $Count++;
            }
        }

        ?>
        <div id="heatmap_chart"></div>
        <script>
            var options = {
                series: <?=json_encode($Data)?>,
                chart: {
                    height: <?=count($Data) * 30?>,
                    type: 'heatmap',
                },
                plotOptions: {
                    heatmap: {
                        reverseNegativeShade: true
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        colors: ['#000000']
                    },
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
                xaxis: {
                    categories: <?=json_encode($XAxisNames) ?>,
                },
                theme: {
                    palette: 'palette1' // upto palette10
                },
            };

            var heatmap_chart = new ApexCharts(document.querySelector("#heatmap_chart"), options);
            heatmap_chart.render();
        </script>
        <?php
    }
}