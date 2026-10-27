<?php


class BasicVoucherFormUtils
{
    private array $AutocompleteFields = [];
    private array $ColumnWidth = [];
    private array $CustomColumnDisplayType = [];
    private array $Groups = [];
    private array $DefaultGroup = [];
    private array $HiddenFields = [];
    private array $RowHighlightCondition = [];
    private string $CheckEmptyRowWithColumn = '';
    private array $ShowColumnTotal = [];
    private array $AdditionalTableTextColumns = [];
    private array $StockColumns = [];
    private array $JSAttached = [];
    private array $ForcedColumnValueChange = [];
    private array $UniqueColumns = [];

    private array $RequiredFields = [];
    private array $RequiredFieldsWithDataColumnLink = [];

    private array $ReadonlyFields = [];

    private array $NonEditableDataTables = [];
    private array $IsHiddenTableDataTables = [];

    private array $OverviewTableTotals = [];
    private array $HiddenDataTableKeys = [];

    public function __construct()
    {
        $this->DefaultGroup[BasicVoucherForm::DEFAULT_GROUP]['group_class'] = 'col-sm-12';
        $this->DefaultGroup[BasicVoucherForm::DEFAULT_GROUP]['element_class'] = 'col-md-6 col-xl-4';
    }

    function setAutocomplete($ColumnName, $AutoCompleteType, $AdditionalArguments='')
    {
        $this->AutocompleteFields[$ColumnName]['type'] = $AutoCompleteType;
        $this->AutocompleteFields[$ColumnName]['additional_arguments'] = $AdditionalArguments;
    }

    function getAutocomplete($ColumnName)
    {
        return $this->AutocompleteFields[$ColumnName];
    }

    function designAutoComplete($ColumnName, $Value)
    {
        $Autocomplete = $this->getAutocomplete($ColumnName);
        Autocomplete::designAutocomplete($Autocomplete['type'], $Autocomplete['additional_arguments'], $Value);
    }

    public function isFieldRequired($ColumnName)
    {
        return in_array($ColumnName, $this->RequiredFields);
    }

    public function setRequiredFields(array $ColumnNames)
    {
        $this->RequiredFields = $ColumnNames;
    }

    /**
     * @param $ColumnName
     * @return string
     */
    public function getColumnWidth($ColumnName)
    {
        return $this->ColumnWidth[$ColumnName];
    }

    /**
     * @param array $ColumnWidth [ColumnName => Width]
     */
    public function setColumnWidth(array $ColumnWidth): void
    {
        $this->ColumnWidth = $ColumnWidth;
    }

    function setColumnDisplayType($Column, FormFieldType $oFormType): void
    {
        $this->CustomColumnDisplayType[$Column] = $oFormType;
    }

    function getColumnDisplayType($Column) : ?FormFieldType
    {
        return $this->CustomColumnDisplayType[$Column];
    }

    function addElementGroup($GroupName, $GroupClass, $ElementClass, array $Columns)
    {
        $this->Groups[$GroupName]['group_class'] = $GroupClass;
        $this->Groups[$GroupName]['element_class'] = $ElementClass;
        $this->Groups[$GroupName]['columns'] = $Columns;
    }

    function addDefaultGroup($GroupClass, $ElementClass)
    {
        $this->DefaultGroup[BasicVoucherForm::DEFAULT_GROUP]['group_class'] = $GroupClass;
        $this->DefaultGroup[BasicVoucherForm::DEFAULT_GROUP]['element_class'] = $ElementClass;
    }

    function getElementGroups(): array
    {
        return [...$this->DefaultGroup, ...$this->Groups];
    }

    /**
     * Array of column names to be passed, if this function has been previously called, it will merge the old hidden fields and new fields.
     *
     * @param array $HiddenFields
     * @return void
     */
    function setHiddenFields(array $HiddenFields): void
    {
        $this->HiddenFields = [...$this->HiddenFields, ...$HiddenFields];
    }

    function getHiddenFields(): array
    {
        return $this->HiddenFields;
    }

    /**
     * Checks on runtime and row needs to be highlighted via some class in case the result of equation is true
     *
     * @param $ColumnName
     * @param $Operator
     * @param $Value
     * @param $ClassNameIfTrue
     * @return void
     */
    function highlightRow($ColumnName, $Operator, $Value, $ClassNameIfTrue): void
    {
        $Count = count($this->RowHighlightCondition);

        $this->RowHighlightCondition[$Count]['column'] = $ColumnName;
        $this->RowHighlightCondition[$Count]['operator'] = $Operator;
        $this->RowHighlightCondition[$Count]['value'] = $Value;
        $this->RowHighlightCondition[$Count]['class_name'] = $ClassNameIfTrue;
    }

    function getAllRowHighlightConditions()
    {
        return $this->RowHighlightCondition;
    }

    function checkRowEmptyWithColumn($ColumnName)
    {
        $this->CheckEmptyRowWithColumn = $ColumnName;
    }

    /**
     * @return string
     */
    public function getCheckEmptyRowWithColumn(): string
    {
        return $this->CheckEmptyRowWithColumn;
    }

    /**
     * @return array
     */
    public function getShowColumnTotal(): array
    {
        return $this->ShowColumnTotal;
    }

    /**
     * @param array $ColumnNames
     */
    public function showColumnTotal(array $ColumnNames): void
    {
        $this->ShowColumnTotal = [...$this->ShowColumnTotal, ...$ColumnNames];
    }

    /**
     * Works only with datatables and not on main table, all additional columns will be displayed as text columns
     *
     * @param $TableIndex
     * @param $ColumnName
     * @param $DataType
     * @param string $BeforeColumn if set as 'last_column', this field will be added at the very last.
     * @param string $CssClass
     * @return void
     */
    function addAdditionalColumns($TableIndex, $ColumnName, $DataType, string $BeforeColumn, string $CssClass=''): void
    {
        $TableIndex = empty($TableIndex) ? 0 : $TableIndex;

        $this->AdditionalTableTextColumns[$TableIndex][$BeforeColumn][$ColumnName] = [
            'datatype' => $DataType,
            'css_class' => $CssClass
        ];
    }

    /**
     * @param $TableIndex
     * @param $ColumnName
     * @return array (Column => DataType)
     */
    function getAdditionalColumns($TableIndex, $ColumnName)
    {
        $TableIndex = empty($TableIndex) ? 0 : $TableIndex;

        return $this->AdditionalTableTextColumns[$TableIndex][$ColumnName];
    }

    /**
     * @param $TableIndex
     * @return array
     */
    public function getStockColumns($TableIndex): array
    {
        return (array)$this->StockColumns[$TableIndex];
    }

    /**
     * Mark columns as stock columns in datatable so in save file, these can be treated as same
     *
     * @param $TableIndex
     * @param $StockColumn
     * @param $AttributeColumn
     */
    public function setStockColumns($TableIndex, $StockColumn, $AttributeColumn): void
    {
        $this->StockColumns[$TableIndex][$StockColumn] = $AttributeColumn;
    }


    /**
     * @return array
     */
    public function getJSAttached(): array
    {
        return $this->JSAttached;
    }

    /**
     * @param string $URL
     */
    public function attachJS(string $URL): void
    {
        $this->JSAttached[] = $URL;
    }

    /**
     * @return array
     */
    public function getOverviewTableTotals(): array
    {
        return $this->OverviewTableTotals;
    }

    /**
     * @param $TableFinalColumnName
     * @param $OverviewRowName
     */
    public function setOverviewTableTotals($TableFinalColumnName, $OverviewRowName): void
    {
        $this->OverviewTableTotals[] = [
            'name' => $OverviewRowName,
            'table_final_column_name' => $TableFinalColumnName
        ];
    }

    /**
     * @param $ColumnName
     * @return bool
     */
    public function isReadonlyField($ColumnName): bool
    {
        return in_array($ColumnName, $this->ReadonlyFields);
    }

    /**
     * @param array $ReadonlyColumns
     */
    public function setReadonlyFields(array $ReadonlyColumns): void
    {
        $this->ReadonlyFields = $ReadonlyColumns;
    }

    /**
     * @param $DataTableKey
     * @return bool
     */
    public function isNonEditableDataTables($DataTableKey): bool
    {
        return in_array($DataTableKey, $this->NonEditableDataTables);
    }

    /**
     * @param $DataTableKey
     * @return bool
     */
    public function isHiddenTableDataTables($DataTableKey): bool
    {
        return in_array($DataTableKey, $this->IsHiddenTableDataTables);
    }

    /**
     * @param array $NonEditableDataTableKeys
     */
    public function setNonEditableDataTables(array $NonEditableDataTableKeys): void
    {
        $this->NonEditableDataTables = $NonEditableDataTableKeys;
    }

    /**
     * @param array $NonEditableDataTableKeys
     */
    public function setHiddenTableDataTables(array $IsHiddenTableDataTables): void
    {
        $this->IsHiddenTableDataTables = $IsHiddenTableDataTables;
    }

    public function getColumnUniqueType($ColumnName)
    {
        return $this->UniqueColumns[$ColumnName];
    }

    public function setUniqueColumns($ColumnName, $Type): void
    {
        $this->UniqueColumns[$ColumnName] = $Type;
    }

    public function isFieldRequiredFieldsWithLinkedColumn($ColumnName)
    {
        return isset($this->RequiredFieldsWithDataColumnLink[$ColumnName]);
    }

    public function setRequiredFieldsWithLinkedColumn($ColumnName, $LinkedDataColumn)
    {
        $this->RequiredFieldsWithDataColumnLink[$ColumnName] = $LinkedDataColumn;
    }

    public function getRequiredFieldLinkedColumn($ColumnName)
    {
        return $this->RequiredFieldsWithDataColumnLink[$ColumnName];
    }

    /**
     * @param array $DataTableKeys
     * @return void
     */
    function setHiddenDataTable(array $DataTableKeys): void
    {
        $this->HiddenDataTableKeys = $DataTableKeys;
    }

    public function isDataTableHidden($DataTableKey): bool
    {
        return in_array($DataTableKey, $this->HiddenDataTableKeys);
    }
}

