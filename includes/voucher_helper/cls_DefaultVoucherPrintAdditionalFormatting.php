<?php

interface DefaultVoucherPrintAdditionalFormatting
{
    function getFormattedColumnValue($ColumnName, $Value) : string|null;
}