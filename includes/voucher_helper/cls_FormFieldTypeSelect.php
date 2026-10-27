<?php

class FormFieldTypeSelect extends FormFieldType
{
    public function __construct(array $Options, $isArrayWithKeys = true)
    {
        $this->AdditionalDetails['options'] = $Options;
        $this->AdditionalDetails['array_with_keys'] = $isArrayWithKeys;
    }

    function getFieldName(): null|string
    {
        return null;
    }

    function getFieldDesign($Value, $CommonAttributes): void
    {
        $Options = $this->getAdditionalDetails()['options'];
        $isArrayWithKeys = $this->getAdditionalDetails()['array_with_keys'];
        ?>
        <select <?=$CommonAttributes?>><? Utils::getDropDownOptionsFromArray($Options, $Value, $isArrayWithKeys); ?></select>
        <?php
    }
}