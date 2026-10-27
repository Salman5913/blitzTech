<?php

class PivotTableTabTreeView extends PivotTableTabs
{
    function getTabData()
    {
        $Data = [];

        $Count=0;
        foreach ($this->ParsedData as $RowNumber => $Columns)
        {
            if (!isset($Columns[array_key_last($Columns)]['is_final_total'])) continue;

            $Data[$Count]['x'] = $this->replaceSubRowPrefix($Columns[0]['value']);
            $Data[$Count]['y'] = round($Columns[array_key_last($Columns)]['value'], 2);

            $Count++;
        }

        ?>
        <div id="tree_view_chart"></div>
        <script>
            $(function() {
                var options = {
                    series: [
                        {
                            data: <? echo json_encode($Data); ?>
                        }
                    ],
                    legend: {
                        show: false
                    },
                    chart: {
                        height: 750,
                        type: 'treemap'
                    },
                    theme: {
                        palette: 'palette1' // upto palette10
                    },
                    plotOptions: {
                        treemap: {
                            distributed: true,
                            enableShades: false
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(value, { series, seriesIndex, dataPointIndex, w }) {
                                return thousandSep(value)
                            }
                        }
                    },
                };
                var chart = new ApexCharts(document.querySelector("#tree_view_chart"), options);
                chart.render();
            })
        </script>
        <?php
    }
}