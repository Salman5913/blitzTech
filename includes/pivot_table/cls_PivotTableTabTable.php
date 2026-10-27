<?php

class PivotTableTabTable extends PivotTableTabs
{
    function getTabData()
    {
        ?>
        <table class="table table-striped table-condensed table-bordered text-right color pivot-table">
            <thead>
            <? foreach ($this->ParsedHeader as $RowNumber => $Columns) { ?>
                <tr>
                    <? foreach ($Columns as $Key => $ColumnData) { ?>
                        <th class="text-center" colspan="<?=$ColumnData['colspan'] ?? 1 ?>"><?=$ColumnData['value']?></th>
                    <? } ?>
                </tr>
            <? } ?>
            </thead>
            <tbody>
            <? foreach ($this->ParsedData as $RowNumber => $Columns) { ?>
                <tr
                    class="<?=isset($Columns[array_key_first($Columns)]['is_total']) ? 'bg-success text-white font-weight-bold' : ''?>"
                >
                    <? foreach ($Columns as $ColumnKey => $ColumnData) { ?>
                        <td
                            class="
                                    <?=isset($ColumnData['is_name']) ? 'text-left' : ''?>
                                    <?=isset($ColumnData['is_total']) ? 'bg-success text-white font-weight-bold' : ''?>
                                    <?=isset($ColumnData['is_header_row']) ? 'bg-info text-white font-weight-bold' : ''?>
                                    <?=isset($ColumnData['is_final_total']) ? 'bg-warning text-white font-weight-bold' : ''?>
                                "
                            colspan="<?=$ColumnData['colspan'] ?? 1 ?>"
                        >
                            <?=isset($ColumnData['is_name']) ? $ColumnData['value'] : Utils::format($ColumnData['value'], 0, true)?>
                        </td>
                    <? } ?>
                </tr>
            <? } ?>
            </tbody>
            <tfoot>
            <tr>
                <? foreach ($this->ColumnTotal as $Key => $Total) { ?>
                    <td class="font-weight-bold bg-warning text-white"><?=$Key == array_key_first($this->ColumnTotal) ? '' : Utils::format($Total)?></td>
                <? } ?>
            </tr>
            </tfoot>
        </table>
        <script>
            $(function() {
                $(".excel_button").click(function () {
                    $("table.pivot-table").table2excel({
                        // exclude CSS class
                        exclude: ".print-hidden",
                        name: "Worksheet Name",
                        filename: $(".popup-header_voucher").text().replace(" ", "-").toLowerCase(), //do not include extension
                        fileext: ".xls", // file extension,
                        exclude_img:true,
                        exclude_links:true,
                        exclude_inputs:true,
                        preserveColors: true
                    });
                });
            })
        </script>
        <?php
    }
}