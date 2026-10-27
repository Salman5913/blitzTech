<?php


class BasicVoucherForm
{
    /**
     * @var BasicVoucher $oVoucher
     */
    protected BasicVoucher $oVoucher;
    protected array $TableColumnDataTypes = [];
    protected array $TableColumnComment = [];
    protected array $RowAttributes = [];

    const DEFAULT_GROUP = 'default';

    /**
     * @var BasicVoucherFormUtils
     */
    protected BasicVoucherFormUtils $oVoucherFormUtils;
    private array $ForceSetColumnType = [];


    public function __construct(BasicVoucher $oVoucher)
    {
        $this->oVoucher = $oVoucher;

        if ($oVoucher instanceof DefaultVoucherForm)
            $this->oVoucherFormUtils = $oVoucher->getVoucherFormUtils();
        else
            $this->oVoucherFormUtils = new BasicVoucherFormUtils();

        $this->getTableColumnDataTypes($oVoucher->getTableName());

        foreach ($oVoucher->getDataTableName() as $Key => $TableName)
        {
            $this->getTableColumnDataTypes($TableName, $Key);
        }

        $this->setElementGroups();
    }

    function getTableColumnDataTypes($TableName, $Key=''): void
    {
        $Select = DBQM::query("SHOW FULL COLUMNS FROM `".$TableName."`");

        while ($Row = DBQM::fetch_assoc($Select))
        {
            if ($Row['Field'] != 'id' AND $Row['Field'] != 'distinct_id' and $Row['Field'] != $this->oVoucher->getDataIdColumnName())
            {
                if ($TableName == $this->oVoucher->getTableName()) // If Main Table
                {
                    $this->TableColumnDataTypes[$TableName][self::DEFAULT_GROUP][$Row['Field']] = $Row['Type'];
                    $this->TableColumnComment[$TableName][self::DEFAULT_GROUP][$Row['Field']] = $Row['Comment'];
                }
                else // If Datatable
                {
                    $this->TableColumnDataTypes[$TableName][$Row['Field']] = $Row['Type'];
                    $this->TableColumnComment[$TableName][$Row['Field']] = $Row['Comment'];
                }
            }
        }
    }

    function getDataTableComment($TableName, $ColumnName)
    {
        return $this->TableColumnComment[$TableName][$ColumnName];
    }

    function getMainTableColumnsWithDataTypes(): array
    {
        $Columns = [];
        $Select = DBQM::query("DESCRIBE `".$this->oVoucher->getTableName()."`");

        while ($Row = DBQM::fetch_assoc($Select))
        {
            if ($Row['Field'] != 'id' AND $Row['Field'] != 'distinct_id' and $Row['Field'] != $this->oVoucher->getDataIdColumnName())
            {
                $Columns[$Row['Field']] = $Row['Type'];
            }
        }

        return $Columns;
    }

    function getAdditionalColumns($TableIndex, $CurrentColumnName):null|array
    {
        return $this->oVoucherFormUtils->getAdditionalColumns($TableIndex, $CurrentColumnName);
    }

    function getMainTableDesignHTML($GroupName, $GroupData, $RowDetails, $GET): void
    {
        foreach ($this->TableColumnDataTypes[$this->oVoucher->getTableName()][$GroupName] as $ColumnName => $Type) {
            $Value = '';
            if (isset($RowDetails[$ColumnName]))
                $Value = $RowDetails[$ColumnName];
            elseif (isset($GET[$ColumnName]))
                $Value = $GET[$ColumnName];

            $Comment = $this->TableColumnComment[$this->oVoucher->getTableName()][self::DEFAULT_GROUP][$ColumnName];
            ?>
            <div class="<?=in_array($ColumnName, $this->oVoucherFormUtils->getHiddenFields()) ? 'hidden' : '' ?> <?=$GroupData['element_class']?>">
                <label for="<?=$ColumnName?>"><?=$this->makeReadableColumnName($ColumnName)?></label>
                <? $this->getFieldType($ColumnName, $Type, $Value) ?>
                <? if (!empty($Comment)) { ?>
                    <div class="small text-info pt-1"><?=$Comment?></div>
                <? } ?>
            </div>
            <?php
        }
    }

    function getDataTables(): array
    {
        return $this->oVoucher->getDataTableName();
    }

    function getDataTableColumns($TableName)
    {
        return $this->TableColumnDataTypes[$TableName];
    }

    /**
     * first column name to check if field value is empty or not.
     *
     * @param $TableName
     * @return string
     */
    function getDataTableFirstColumn($TableName): string
    {
        if (!empty($this->oVoucherFormUtils->getCheckEmptyRowWithColumn()) and key_exists($this->oVoucherFormUtils->getCheckEmptyRowWithColumn(), $this->TableColumnDataTypes[$TableName]))
            return $this->oVoucherFormUtils->getCheckEmptyRowWithColumn();

        foreach ($this->TableColumnDataTypes[$TableName] as $FieldName => $Type)
        {
            if (!str_contains($Type, "date"))
                return $FieldName;
        }

        return '';
    }

    function getFieldType($ColumnName, $Type, $Value, $Key=false, &$RowData=[]): void
    {
        new BasicVoucherFormFieldDesigner(
            $this,
            $this->oVoucherFormUtils,
            $ColumnName,
            $Type,
            $Value,
            $this->ForceSetColumnType,
            $Key,
            $RowData,
            $this->RowAttributes
        );
    }

    function makeReadableColumnName($ColumnName): string
    {
        $UpdatedColumnName = null;
        if ($this->oVoucher instanceof DefaultVoucherFormAdditionalFormatting)
            $UpdatedColumnName = $this->oVoucher->getFormFormattedColumnLabel($ColumnName);

        if (empty($UpdatedColumnName))
        {
            if (str_starts_with($ColumnName, "is_")) {
                $ColumnName = substr($ColumnName, 3, strlen($ColumnName));
                $ColumnName .= "?";
            }

            $ColumnName = str_replace("_id", "", $ColumnName);
            $ColumnName = str_replace("_", " ", $ColumnName);
        }
        else
            $ColumnName = $UpdatedColumnName;


        return ucwords($ColumnName);
    }

    function getColumnWidth($ColumnName)
    {
        return $this->oVoucherFormUtils->getColumnWidth($ColumnName);
    }

    private function setElementGroups(): void
    {
        $MainTableName = $this->oVoucher->getTableName();
        $Groups = $this->getElementGroups();

        foreach ($Groups as $GroupName => $GroupData)
        {
            if ($GroupName == self::DEFAULT_GROUP) continue;

            foreach ($GroupData['columns'] as $Column)
            {
                $Element = $this->TableColumnDataTypes[$MainTableName][self::DEFAULT_GROUP][$Column];
                unset($this->TableColumnDataTypes[$MainTableName][self::DEFAULT_GROUP][$Column]);

                $this->TableColumnDataTypes[$MainTableName][$GroupName][$Column] = $Element;
            }
        }
    }

    function getElementGroups(): array
    {
        return $this->oVoucherFormUtils->getElementGroups();
    }

    /**
     * @return BasicVoucherFormUtils
     */
    public function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        return $this->oVoucherFormUtils;
    }

    function isColumnTotalRequired($Column): bool
    {
        return in_array($Column, $this->oVoucherFormUtils->getShowColumnTotal());
    }

    public function getAllRowHighlightConditions(): array
    {
        return $this->oVoucherFormUtils->getAllRowHighlightConditions();
    }

    function getAttachedJS()
    {
        return $this->oVoucherFormUtils->getJSAttached();
    }

    /**
     * @return array
     */
    public function getRowAttributes(): array
    {
        return $this->RowAttributes;
    }

    /**
     * @param array $RowAttributes
     */
    public function setRowAttributes(array $RowAttributes): void
    {
        $this->RowAttributes = $RowAttributes;
    }

    function getOverviewTotals()
    {
        return $this->oVoucherFormUtils->getOverviewTableTotals();
    }

    function isDataTableHidden($DataTableKey) : bool
    {
        return $this->oVoucherFormUtils->isDataTableHidden($DataTableKey);
    }
}