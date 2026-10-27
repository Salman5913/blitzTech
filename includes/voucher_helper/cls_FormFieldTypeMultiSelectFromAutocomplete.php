<?php

class FormFieldTypeMultiSelectFromAutocomplete extends FormFieldType
{
    public function __construct(private $AutocompleteType, private $isArrayWithKeys=false) {}

    function getFieldName(): null|string
    {
        return $this->oFormFieldDesigner->getColumnName()."[]";
    }

    function getFieldDesign($Value, $CommonAttributes): void
    {
        $oAutocomplete = new Autocomplete($this->AutocompleteType);
        $Select = $oAutocomplete->searchData();

        $isArrayWithKeys = $this->getAdditionalDetails()['array_with_keys'];
        $Value = explode(";", $Value);
        ?>
        <select <?=$CommonAttributes?> data-type="select2" multiple="multiple"><? Utils::getDropDownOptionsFromSelect($Select, 'col_data', 'col_data', $Value); ?></select>
        <?php
    }
}