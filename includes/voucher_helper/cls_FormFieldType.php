<?php

abstract class FormFieldType
{
    protected array $AdditionalDetails = [];
    protected BasicVoucherFormFieldDesigner $oFormFieldDesigner;
    protected array $RowData = [];
    protected array $RowAttributes = [];


    function getAdditionalDetails()
    {
        return $this->AdditionalDetails;
    }

    function setFormFieldDesignerObject(BasicVoucherFormFieldDesigner $oFormFieldDesigner)
    {
        $this->oFormFieldDesigner = $oFormFieldDesigner;
    }

    abstract function getFieldName() : null|string;

    abstract function getFieldDesign($Value, $CommonAttributes) : void;

    /**
     * @return array
     */
    public function getRowData(): array
    {
        return $this->RowData;
    }

    /**
     * @param array|null $RowData
     */
    public function setRowData(array|null $RowData): void
    {
        $this->RowData = $RowData ?? [];
    }

    public function updateRowColumnValue($ColumnName, $Value) : void
    {
        $this->RowData[$ColumnName] = $Value;
    }

    public function updateRowColumnAttributes($ColumnName, $Attributes) : void
    {
        $this->RowAttributes[$ColumnName] = $Attributes;
    }

    /**
     * @return array
     */
    public function getRowAttributes(): array
    {
        return $this->RowAttributes;
    }
}