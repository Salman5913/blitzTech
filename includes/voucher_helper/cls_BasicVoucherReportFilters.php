<?php


class BasicVoucherReportFilters
{
    protected $Filter = [];


    function getFilters()
    {
        return $this->Filter;
    }

    public function setFilter($ColumnName, $Label, $DataType, $AdditionalData='', $AutocompleteType='')
    {
        $this->Filter[$ColumnName]['label'] = $Label;
        $this->Filter[$ColumnName]['datatype'] = $DataType;
        $this->Filter[$ColumnName]['autocomplete_type'] = $AutocompleteType;
        $this->Filter[$ColumnName]['additional_data'] = $AdditionalData;
    }
}