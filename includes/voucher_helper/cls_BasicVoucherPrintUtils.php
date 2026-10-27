<?php


class BasicVoucherPrintUtils
{
    private $HiddenColumns = [];
    private $CalculationColumns = [];
    private $CalcTotals = [];
    private $ColumnWidth = [];
    private $ColumnClass = [];
    private $PageTitle;
    private array $ImageColumns = [];
    private array $LinkColumns = [];
    private array $ExternalLinkColumns = [];

    protected bool $isSignatureRequire = false;

    public function __construct()
    {
        $this->HiddenColumns = [];
        $this->CalculationColumns = [];
    }

    function setHideColumns(array $Column)
    {
        $this->HiddenColumns = $Column;
    }

    function addNewColumn($TableName, $ColumnName, $Calculation, $BeforeColumn='')
    {
        $this->CalculationColumns[] = [
            'table' => $TableName,
            'column' => $ColumnName,
            'calculation' => $Calculation,
            'before_column' => $BeforeColumn
        ];
    }

    function setColumnWidth(array $Column)
    {
        $this->ColumnWidth = $Column;
    }

    function calcTotals(array $Column)
    {
        $this->CalcTotals = $Column;
    }

    function getHiddenColumns()
    {
        return $this->HiddenColumns;
    }

    function getAdditionalColumns()
    {
        return $this->CalculationColumns;
    }

    function isTotalRequired($ColumnName)
    {
        return in_array($ColumnName, $this->CalcTotals);
    }

    function getColumnWidth($ColumnName)
    {
        return $this->ColumnWidth[$ColumnName];
    }

    /**
     * @param $ColumnName
     * @param array $ColumnClass 0: Label Column Class, 1: Data Column Class
     * @return array
     */
    function setMainColumnClass($ColumnName, array $ColumnClass)
    {
        return $this->ColumnClass[$ColumnName] = $ColumnClass;
    }

    function getMainColumnClass($ColumnName)
    {
        return $this->ColumnClass[$ColumnName];
    }

    function setPageTitle(array $ColumnNamesOrStrings)
    {
        $this->PageTitle = $ColumnNamesOrStrings;
    }

    function getPageTitle()
    {
        return $this->PageTitle;
    }

    /**
     * @return array
     */
    public function getImageColumns(): array
    {
        return $this->ImageColumns;
    }

    /**
     * @param array $ColumnNames
     * @param UploadPath $oUploadPath
     */
    public function showAsImage(array $ColumnNames, UploadPath $oUploadPath): void
    {
        $this->ImageColumns['columns'] = $ColumnNames;
        $this->ImageColumns['path'] = $oUploadPath;
    }

    /**
     * @return array
     */
    public function getLinkColumns(): array
    {
        return $this->LinkColumns;
    }

    /**
     * @param array $ColumnNames
     * @param UploadPath $oUploadPath
     */
    public function showAsLink(array $ColumnNames, UploadPath $oUploadPath): void
    {
        $this->LinkColumns['columns'] = $ColumnNames;
        $this->LinkColumns['path'] = $oUploadPath;
    }

    /**
     * @return array
     */
    public function getExternalLinkColumns(): array
    {
        return $this->ExternalLinkColumns;
    }

    /**
     * @param array $ColumnNames
     */
    public function showAsExternalLink(array $ColumnNames): void
    {
        $this->ExternalLinkColumns['columns'] = $ColumnNames;
    }

    /**
     * @return bool
     */
    public function isSignatureRequire(): bool
    {
        return $this->isSignatureRequire;
    }

    /**
     * @param bool $isSignatureRequire
     */
    public function setIsSignatureRequire(bool $isSignatureRequire): void
    {
        $this->isSignatureRequire = $isSignatureRequire;
    }
}