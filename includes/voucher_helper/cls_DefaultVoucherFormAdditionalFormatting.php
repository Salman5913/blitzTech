<?php

interface DefaultVoucherFormAdditionalFormatting
{
    function getFormFormattedColumnLabel($ColumnName) : string|null;
}