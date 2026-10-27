<?php

abstract class ChartHelper
{
    protected ?int $Height = null;
    protected array $Padding = ['left' => 0, 'right' => 0, 'top' => 0, 'bottom' => 0];
    protected string $LegendsPosition = 'bottom';
    protected array $ColumnName = [];

    public function __construct(protected $ChartType)
    {
    }

    function setHeight(?int $Height)
    {
        $this->Height = $Height;
    }

    function setPadding($Top, $Right, $Bottom, $Left)
    {
        $this->Padding['top'] = empty($Top) ? 0 : $Top;
        $this->Padding['right'] = empty($Right) ? 0 : $Right;
        $this->Padding['bottom'] = empty($Bottom) ? 0 : $Bottom;
        $this->Padding['left'] = empty($Left) ? 0 : $Left;
    }

    function getChart()
    {
        $DivName = 'chart_rand_'.mt_rand(0,999)."_".mt_rand(999,9999);

        $AdditionalOptions = $this->getChartOptions($DivName);
        list($BasicOptionsArray, $BasicOptionObject) = $this->getBasicOptions($DivName);

        $AdditionalOptions = array_merge($BasicOptionsArray, $AdditionalOptions);
        ?>
        <div id="<?=$DivName?>"></div>
        <? echo $BasicOptionObject ?>
        <script>
            var options_<?=$DivName?> = <?=json_encode($AdditionalOptions) ?>;

            (new ApexCharts(document.querySelector("#<?=$DivName?>"), $.extend(options_<?=$DivName?>, basic_chart_options_<?=$DivName?>))).render();
        </script>
        <?php

        return $DivName;
    }

    function valueArrayToFloat(array $Values) : array
    {
        $IntValues = [];
        foreach ($Values as $Value)
        {
            $IntValues[] = doubleval($Value);
        }

        return $IntValues;
    }

    abstract protected function getChartOptions() : array;

    private function getBasicOptions($DivName) : array
    {
        $BasicOptionsArray = [
            'legend' => [
                'position' => $this->LegendsPosition
            ],
            'chart' => [
                'type' => $this->ChartType,
                'height' => $this->Height ?? 'auto'
            ],
            'labels' => $this->ColumnName,
            'xaxis' => [
                'categories' => $this->ColumnName
            ],
            'grid' => [
                'padding' => [
                    'left' => $this->Padding['left'],
                    'right' => $this->Padding['right'],
                    'top' => $this->Padding['top'],
                    'bottom' => $this->Padding['bottom']
                ]
            ]
        ];

        ob_start()
        ?>
        <script>
        var basic_chart_options_<?=$DivName?> = {
            tooltip: {
                y: {
                    formatter: function(value, { series, seriesIndex, dataPointIndex, w }) {
                        return NumberHelper.thousandSep(value)
                    }
                }
            }
        }
        </script>
        <?php
        $Object = ob_get_clean();

        return [$BasicOptionsArray, $Object];
    }

    /**
     * @param string $LegendsPosition
     */
    public function setLegendsPosition(string $LegendsPosition): void
    {
        $this->LegendsPosition = $LegendsPosition;
    }

}