<?php

class FormFieldTypeSelectFromAutocomplete extends FormFieldType
{
    public function __construct(private $AutocompleteType) {}

    function getFieldName(): null|string
    {
        return null;
    }

    function getFieldDesign($Value, $CommonAttributes): void
    {
        $oAutocomplete = new Autocomplete($this->AutocompleteType);
        $Select = $oAutocomplete->searchData();

        $isArrayWithKeys = $this->getAdditionalDetails()['array_with_keys'];
        ?>
        <select <?=$CommonAttributes?> data-type="select2"><? Utils::getDropDownOptionsFromSelect($Select, 'col_data', 'col_data', $Value); ?></select>
        <?php
    }
}