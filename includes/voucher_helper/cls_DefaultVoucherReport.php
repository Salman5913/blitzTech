<?php


interface DefaultVoucherReport
{
    function getReportFilters() : BasicVoucherReportFilters;

    function getSearchQuery(): mysqli_result;

    function getReportTableColumn() : BasicVoucherReportTableColumn;
}