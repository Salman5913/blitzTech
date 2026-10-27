<?php


class BasicVoucherReportTableColumn
{
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_LINK = 'link';
    const TYPE_DATE = 'date';
    const TYPE_LONGTEXT = 'longtext';

    protected $Columns = [];

    function addColumn($ColumnName, $Label, $Type='', $Width='', $CSSClass='')
    {
        $this->Columns[$ColumnName]['label'] = $Label;
        $this->Columns[$ColumnName]['type'] = $Type;
        $this->Columns[$ColumnName]['width'] = $Width;
        $this->Columns[$ColumnName]['css_class'] = $CSSClass;
    }

    function getColumns()
    {
        return $this->Columns;
    }
}