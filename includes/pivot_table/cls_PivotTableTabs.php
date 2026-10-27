<?php

abstract class PivotTableTabs
{
    protected array $ParsedData = [];
    protected array $ParsedHeader = [];
    protected array $ColumnTotal = [];

    public function __construct(private readonly PivotTableGenerator $oPivotTableGenerator)
    {
        $this->ParsedHeader = $this->oPivotTableGenerator->getParsedHeader();
        $this->ParsedData = $this->oPivotTableGenerator->getParsedData();
        $this->ColumnTotal = $this->oPivotTableGenerator->getColumnTotal();
    }

    abstract function getTabData();

    protected function replaceSubRowPrefix($Name)
    {
        return str_replace('&nbsp; &nbsp; ', '', $Name);
    }
}