<?php

class PivotTableView
{
    private PivotTableGenerator $oPivotTableGenerator;

    public function __construct(private readonly PivotTable $oPivotTable, private $RowDetails, private $SelectFilters, private $SelectSort)
    {
        $RowDetails = $this->RowDetails;

        $Column = $RowDetails['column'];
        $Row = $RowDetails['row'];
        $Value = $RowDetails['value'];
        $SubColumn = $RowDetails['sub_column'];
        $SubRow = $RowDetails['sub_row'];

        if (!in_array($Value, array_keys($this->oPivotTable->getPermittedValueColumns())))
        {
            ErrorHandler::setError("Invalid Value Column");
            Utils::redirectToReferer();
        }

        $this->oPivotTableGenerator = new PivotTableGenerator(
            $this->oPivotTable->getView(),
            $Column,
            $Row,
            $Value,
            $SubColumn,
            $SubRow,
            $RowDetails['is_sub_column_total_required'] == 'Y',
            $RowDetails['is_sub_row_total_required'] == 'Y',
            $this->SelectFilters,
            $this->SelectSort,
            $this->RowDetails
        );
    }

    function getTabData($ClassName)
    {
        $oPivotTableTab = new $ClassName($this->oPivotTableGenerator);

        if (!$oPivotTableTab instanceof PivotTableTabs)
            ErrorHandler::showOnErrorPage("Undefined Class");

        $oPivotTableTab->getTabData();
    }

    function getSubheading()
    {
        $Subheading = '';
        $Columns = [...$this->oPivotTable->getPermittedColumns(), ...$this->oPivotTable->getPermittedValueColumns()];

        $Filters = [];
        DBQM::reset($this->SelectFilters);
        while ($RowFilters = DBQM::fetch_assoc($this->SelectFilters))
            $Filters[] = $Columns[$RowFilters['filter_column']]." ".$RowFilters['filter_condition']." ".$RowFilters['filter_value'];
        DBQM::reset($this->SelectFilters);
        $Subheading .= count($Filters) ? '<strong>Filters:</strong> '.implode("; ", $Filters)."; " : '';



        $Sort = [];
        DBQM::reset($this->SelectSort);
        while ($RowSort = DBQM::fetch_assoc($this->SelectSort))
            $Sort[] = $Columns[$RowSort['sort_column']]." - ".$RowSort['sort_type'];
        DBQM::reset($this->SelectSort);
        $Subheading .= count($Sort) ? '<strong>Sorting:</strong> '.implode("; ", $Sort) : '';

        return $Subheading;
    }
}