<?php

class BasicVoucherFormFieldDesigner
{
    private string $Name;
    private string $DataType;
    private string $InputType;
    private string $TableColumnIdentifier;

    public function __construct(
            private readonly BasicVoucherForm $oBasicVoucherForm,
            private readonly BasicVoucherFormUtils $oVoucherFormUtils,
            private $ColumnName,
            private $Type,
            private $Value,
            private &$ForceSetColumnType,
            private $TableIndex=false,
            private &$RowData=[],
            private &$RowAttributes=[]
    )
    {
        $this->TableColumnIdentifier = ' data-table-column="'.$ColumnName.'" ';

        $this->setFieldName();
        $this->setDataType();
        $this->seInputType();
        $this->setInputValue();

        $oFormFieldType = $this->oVoucherFormUtils->getColumnDisplayType($this->ColumnName);

        if (!empty($oFormFieldType))
            $this->designInputFromFieldTypeObject($oFormFieldType);
        else
            $this->designFieldByDatabaseColumnType();
    }

    /**
     * If main table, return column name, else return name as data with table index and column name (array)
     */
    private function setFieldName(): void
    {
        $this->Name = $this->TableIndex === false ? $this->ColumnName : "data[$this->TableIndex][$this->ColumnName][]";
    }

    private function setDataType(): void
    {
        if (str_contains($this->Type, 'date'))
            {
                $DataType = ' data-type="date" ';
            }
        elseif (
                (isset($this->ForceSetColumnType[$this->ColumnName]) and $this->ForceSetColumnType[$this->ColumnName] == 'int') or
                (!isset($this->ForceSetColumnType[$this->ColumnName]) and str_contains($this->Type, 'int') and empty($this->oVoucherFormUtils->getAutocomplete($this->ColumnName)))
        )
            {
                $DataType = ' data-type="int" data-js="'.$this->ColumnName.'" ';
            }
        elseif (
                (isset($this->ForceSetColumnType[$this->ColumnName]) and $this->ForceSetColumnType[$this->ColumnName] == 'double') or
                (!isset($this->ForceSetColumnType[$this->ColumnName]) and (str_contains($this->Type, 'float') or strstr($this->Type, 'double') or strstr($this->Type, 'decimal')))
        )
            {
                $DataType = ' data-type="float" data-js="'.$this->ColumnName.'" ';
            }
        else
            {
                $DataType = '';
            }

        $DataType .= $this->oVoucherFormUtils->isReadonlyField($this->ColumnName) ? ' readonly="readonly" ' : '';
        $this->DataType = $DataType;
    }

    private function setInputValue(): void
    {
        if (str_contains($this->Type, 'date'))
            $this->Value = DateDecode($this->Value);
    }

    private function seInputType(): void
    {
        $InputType = 'text';

        if ($this->Type == 'time')
            $InputType = 'time';

        $this->InputType = $InputType;
    }

    function updateInputFieldName($UpdatedName)
    {
        if (!empty($UpdatedName))
            $this->Name = $UpdatedName;
    }

    private function designInputFromFieldTypeObject(FormFieldType $oFormFieldType): void
    {
        $oFormFieldType->setFormFieldDesignerObject($this);
        $this->updateInputFieldName($oFormFieldType->getFieldName());
        $oFormFieldType->setRowData($this->RowData);

        $CommonAttributes = 'name="'.$this->Name.'" id="'.$this->ColumnName.'" '.$this->TableColumnIdentifier.' '.(($this->oVoucherFormUtils->isFieldRequired($this->ColumnName)) ? Utils::isRequired(true) : '');
        $CommonAttributes .= " ".($this->oVoucherFormUtils->isFieldRequiredFieldsWithLinkedColumn($this->ColumnName) ? Utils::isRequiredLinkedWithDataColumn($this->oVoucherFormUtils->getRequiredFieldLinkedColumn($this->ColumnName), true) : '');
        $CommonAttributes .= " ".$this->RowAttributes[$this->ColumnName]." ";
        $CommonAttributes .= $this->oVoucherFormUtils->isReadonlyField($this->ColumnName) ? ' readonly="readonly" ' : '';
        $CommonAttributes .= UniquenessChecker::checkForUniqueByType($this->oVoucherFormUtils->getColumnUniqueType($this->ColumnName), $this->Value);
        $CommonAttributes .= " data-label='".$this->oBasicVoucherForm->makeReadableColumnName($this->ColumnName)."'";

        $oFormFieldType->getFieldDesign($this->Value, $CommonAttributes);
        $this->RowData = $oFormFieldType->getRowData();
        $this->RowAttributes = $oFormFieldType->getRowAttributes();
    }

    private function designFieldByDatabaseColumnType(): void
    {
        $CommonAttributes = " ".$this->RowAttributes[$this->ColumnName]." ";
        $CommonAttributes .= $this->oVoucherFormUtils->isReadonlyField($this->ColumnName) ? ' readonly="readonly" ' : '';
        $CommonAttributes .= UniquenessChecker::checkForUniqueByType($this->oVoucherFormUtils->getColumnUniqueType($this->ColumnName), $this->Value);
        $CommonAttributes .= " ".($this->oVoucherFormUtils->isFieldRequiredFieldsWithLinkedColumn($this->ColumnName) ? Utils::isRequiredLinkedWithDataColumn($this->oVoucherFormUtils->getRequiredFieldLinkedColumn($this->ColumnName), true) : '');
        $CommonAttributes .= " data-label='".$this->oBasicVoucherForm->makeReadableColumnName($this->ColumnName)."'";

        if (str_contains($this->Type, 'text'))
        {
            ?>
            <textarea name="<?=$this->Name?>" <?=$CommonAttributes?> <?=$this->TableColumnIdentifier?> id="<?=$this->ColumnName?>" <? if ($this->oVoucherFormUtils->isFieldRequired($this->ColumnName)) Utils::isRequired(); ?>><?=$this->Value?></textarea>
            <?php
        }
        elseif ($this->Type == 'char(1)')
        {
            ?>
            <select name="<?=$this->Name?>" <?=$CommonAttributes?> <?=$this->TableColumnIdentifier?> id="<?=$this->ColumnName?>" <? if ($this->oVoucherFormUtils->isFieldRequired($this->ColumnName)) Utils::isRequired(); ?>><? Utils::getDropDownOptionsFromArray(['N' => 'No', 'Y' => 'Yes'], $this->Value); ?></select>
            <?php
        }
        else
        {
            ?>
            <input
                type="<?=$this->InputType?>"
                name="<?=$this->Name?>"
                <?=$CommonAttributes?>
                <?=$this->TableColumnIdentifier?>
                id="<?=$this->ColumnName?>"
                <? if (empty($this->oVoucherFormUtils->getAutocomplete($this->ColumnName))) { ?>value="<?=$this->Value?>"<?php } ?>
                <?=$this->DataType?>
                <? $this->oVoucherFormUtils->designAutoComplete($this->ColumnName, $this->Value); ?>
                <? if ($this->oVoucherFormUtils->isFieldRequired($this->ColumnName)) Utils::isRequired(); ?>
            >
            <?php
        }
    }

    public function getTableIndex()
    {
        return $this->TableIndex;
    }

    /**
     * @return mixed
     */
    public function getColumnName()
    {
        return $this->ColumnName;
    }

    /**
     * @param $ColumnName
     * @return string
     */
    public function getRowData($ColumnName): string
    {
        return $this->RowData[$ColumnName] ?? '';
    }


    public function setForceColumnType($Column, $Value): void
    {
        $this->ForceSetColumnType[$Column] = $Value;
    }
}