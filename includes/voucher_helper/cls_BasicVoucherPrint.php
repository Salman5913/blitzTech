<?php


class BasicVoucherPrint
{
    public BasicVoucher $oVoucher;
    public BasicVoucherPrintUtils $oVoucherPrintUtils;
    public BasicVoucherFormUtils $oVoucherFormUtils;

    protected $MainTableData = [];
    protected $DataTableData = [];

    protected array $MainTableDataAdditionalFormatting = [];
    protected array $DataTableDataAdditionalFormatting = [];

    protected $TableColumnDataTypes = [];

    protected $TableSchema = [];

    /**
     * BasicVoucherPrint constructor.
     * @param BasicVoucher $oVoucher
     */
    public function __construct(BasicVoucher $oVoucher)
    {
        $this->oVoucher = $oVoucher;

        if ($oVoucher instanceof DefaultVoucherPrint)
            $this->oVoucherPrintUtils = $oVoucher->getVoucherPrintUtilsClass();
        else
            $this->oVoucherPrintUtils = new BasicVoucherPrintUtils();

        if ($oVoucher instanceof DefaultVoucherForm)
            $this->oVoucherFormUtils = $oVoucher->getVoucherFormUtils();
        else
            $this->oVoucherFormUtils = new BasicVoucherFormUtils();

        $this->getTableSchema($oVoucher->getTableName());
        $this->getTableColumnDataTypes($oVoucher->getTableName());

        foreach ($oVoucher->getDataTableName() as $Key => $TableName)
        {
            $this->getTableColumnDataTypes($TableName);
            $this->getTableSchema($TableName);
        }

        $this->setMainTableData();
        $this->setDataTableData();

        $this->addAdditionalColumns();
        $this->removeHiddenColumns();
    }

    function getTableColumnDataTypes($TableName)
    {
        $Select = DBQM::query("DESCRIBE `".$TableName."`");

        while ($Row = DBQM::fetch_assoc($Select))
        {
            $this->TableColumnDataTypes[$Row['Field']] = $Row['Type'];
        }
    }

    /**
     * Get All foreign keys connection (table names, connected column) for main table
     * @param $TableName
     */
    private function getTableSchema($TableName)
    {
        $Select = DatabaseHelper::getTableSchema($TableName);

        $this->TableSchema[$TableName] = [];
        while ($Row = DBQM::fetch_assoc($Select))
        {
            $this->TableSchema[$TableName][$Row['column_name']]['table'] = $Row['foreign_table'];
            $this->TableSchema[$TableName][$Row['column_name']]['column'] = $Row['foreign_column'];
        }
    }

    public function getMainTableDataAdditionalFormatting(): array
    {
        return $this->MainTableDataAdditionalFormatting;
    }

    public function getDataTableDataAdditionalFormatting(): array
    {
        return $this->DataTableDataAdditionalFormatting;
    }

    protected function setTableData($ColumnName, $Value, &$Array, $DataIdColumnName, $TableName, &$AdditionalFormattingArray=[], $AdditionalFormattingValue='')
    {
        if (strstr($ColumnName, '_id') or $ColumnName == 'id')
        {
            $Array['keys'][$ColumnName] = $Value;
        }
        else
            $Array[$ColumnName] = $Value;

        if ($ColumnName == 'distinct_id')
        {
            $RowDistinctId['distinct_id'] = $this->getDistinctIdDetails($Value);
            $Array = array_merge($Array, $RowDistinctId);
        }
        elseif ($ColumnName == $DataIdColumnName)
        {
            // do nothing
        }
        /* Check if its a foreign Key, get Its name from 1st varchar field of foreign table */
        elseif (isset($this->TableSchema[$TableName][$ColumnName]))
        {
            $SelectForeignData = DBQM::query("SELECT * FROM `".$this->TableSchema[$TableName][$ColumnName]['table']."` WHERE `".$this->TableSchema[$TableName][$ColumnName]['column']."` = ".escape($Value)." LIMIT 1");
            $RowForeignData = DBQM::fetch_assoc($SelectForeignData);

            $SelectForeignSchema = DatabaseHelper::getTableDescription($this->TableSchema[$TableName][$ColumnName]['table']);
            while ($RowForeignSchema = DBQM::fetch_assoc($SelectForeignSchema))
            {
                // checking for column type, if its varchar, set the name as column value
                if (strstr($RowForeignSchema['Type'], "varchar") and !isset($Array[$ColumnName."_name"]))
                    $Array[$ColumnName."_name"] = $RowForeignData[$RowForeignSchema['Field']];
            }
        }

        $AdditionalFormattingArray[$ColumnName] = $AdditionalFormattingValue;
        if (isset($Array[$ColumnName."_name"]))
            $AdditionalFormattingArray[$ColumnName."_name"] = $AdditionalFormattingValue;
    }

    protected function setMainTableData()
    {
        $TableName = $this->oVoucher->getTableName();
        // ---------- Get Data from Main Table ------------ //
        $SelectDetails = DBQM::query("SELECT * FROM `".$this->oVoucher->getTableName()."` WHERE `id` = ".escape($this->oVoucher->getVoucherId()));

        while ($Row = DBQM::fetch_assoc($SelectDetails))
        {
            // ---------- Setting main table data via looping on all columns so we can have field names as well ------------ //
            foreach ($Row as $ColumnName => $Value)
            {
                $this->setTableData($ColumnName, $Value, $this->MainTableData, '', $TableName);
            }
        }
    }

    protected function setDataTableData()
    {
        $DataIdColumnName = $this->oVoucher->getDataIdColumnName();
        foreach ($this->oVoucher->getDataTableName() AS $Key => $TableName)
        {
            // ---------- Get Data from Main Table ------------ //
            $SelectDetails = DBQM::query("SELECT * FROM `".$TableName."` WHERE `".$DataIdColumnName."` = ".escape($this->oVoucher->getVoucherId()));

            $RecordCount = 0;
            while ($Row = DBQM::fetch_assoc($SelectDetails))
            {
                // ---------- Setting main table data via looping on all columns so we can have field names as well ------------ //
                foreach ($Row as $ColumnName => $Value)
                {
                    $this->setTableData($ColumnName, $Value, $this->DataTableData[$Key][$RecordCount], $DataIdColumnName, $TableName);
                }
                $RecordCount++;
            }
        }
    }

    function getDistinctIdDetails($DistinctId)
    {
        $SelectDistinctId = DBQM::query("
            SELECT 
                d.*,
                cb.username AS created_by_name,
                ub.username AS updated_by_name,
                db.username AS deleted_by_name
            FROM 
                distinct_id d
                LEFT JOIN
                    sh_login cb on d.created_by = cb.id
                LEFT JOIN
                    sh_login ub on d.updated_by = ub.id
                LEFT JOIN
                    sh_login db on d.deleted_by = db.id
            WHERE 
                d.id = ".escape($DistinctId)
        );
        return DBQM::fetch_assoc($SelectDistinctId);
    }

    /**
     * @return array
     */
    public function getMainTableData()
    {
        return $this->MainTableData;
    }

    /**
     * @return array
     */
    public function getDataTableData()
    {
        return $this->DataTableData;
    }

    function getDistinctIdData($ColumnName)
    {
        return $this->MainTableData['distinct_id'][$ColumnName];
    }

    function makeReadableColumnName($ColumnName)
    {
        $ColumnName = str_starts_with($ColumnName, "is_") ? str_replace("is_", '', $ColumnName)."?" : $ColumnName;
        $ColumnName = str_replace("_id_name", "", $ColumnName);
        $ColumnName = str_replace("calc_", "", $ColumnName);
        $ColumnName = str_replace("_", " ", $ColumnName);

        return ucwords($ColumnName);
    }

    function makeReadableColumnValue($ColumnName, $Value)
    {
        $ColumnDataType = $this->TableColumnDataTypes[$ColumnName];

        if ($this->oVoucher instanceof DefaultVoucherPrintAdditionalFormatting)
        {
            $FormattedValue = $this->oVoucher->getFormattedColumnValue($ColumnName, $Value);

            if ($FormattedValue)
                return $FormattedValue;
        }

        if (!empty($this->oVoucherPrintUtils->getImageColumns()['columns']))
        {
            if (in_array($ColumnName, $this->oVoucherPrintUtils->getImageColumns()['columns']))
            {
                $oUploadPath = $this->oVoucherPrintUtils->getImageColumns()['path'];
                $Path = $oUploadPath->getPath().$Value;

                if (!is_file($Path)) return '';
                return "<a href='".$Path."' target='_blank'><img src='".$Path."' class='img-fluid mb-2' /></a>";
            }
        }

        if (!empty($this->oVoucherPrintUtils->getLinkColumns()['columns']))
        {
            if (in_array($ColumnName, $this->oVoucherPrintUtils->getLinkColumns()['columns']))
            {
                $oUploadPath = $this->oVoucherPrintUtils->getLinkColumns()['path'];
                $Path = $oUploadPath->getPath().$Value;

                if (!is_file($Path)) return '';
                return "<a href='".$Path."' target='_blank' class='text-primary'>Click here to view file</a>";
            }
        }

        if (!empty($this->oVoucherPrintUtils->getExternalLinkColumns()['columns']))
        {
            if (in_array($ColumnName, $this->oVoucherPrintUtils->getExternalLinkColumns()['columns']))
            {
                if (!empty($Value))
                    return "<a href='".$Value."' target='_blank' class='text-primary'>Click here to view Link</a>";
                else
                    return "";
            }
        }

        $oFormType = $this->oVoucherFormUtils->getColumnDisplayType($ColumnName);
        if ($oFormType instanceof FormFieldTypeFile)
        {
            $FileType = $oFormType->getAdditionalDetails()['accept_type'];
            $Path = (new UploadPath(get_class($this->oVoucher)))->getPath().$Value;

            if ($FileType != FormFieldTypeFile::TYPE_IMAGE)
                return "<a href='".$Path."' target='_blank' class='text-primary'>Click here to view Link</a>";
            else
                return "<a href='".$Path."' target='_blank' class='text-primary'><img src='".$Path."' alt='' style='max-width: 200px; max-height: 200px' /></a>";
        }

        if (str_contains($ColumnDataType, "int"))
            return Utils::format($Value, 0);

        if (str_contains($ColumnDataType, "double"))
            return Utils::format($Value);

        if (str_contains($ColumnDataType, "date"))
            return DateDecode($Value, 'M d, Y');

        if (str_contains($ColumnName, "calc_"))
            return Utils::format($Value, 2);

        if (strlen($Value) == 1)
        {
            switch ($Value)
            {
                case "N";
                    return "No";

                case "Y";
                    return "Yes";
            }
        }

        return $Value;
    }

    function getClassByColumnDataType($ColumnName)
    {
        $ColumnDataType = $this->TableColumnDataTypes[$ColumnName];

        if (str_contains($ColumnDataType, "int"))
            return " text-center ";

        if (str_contains($ColumnDataType, "double"))
            return " text-center ";

        if (str_contains($ColumnDataType, "date"))
            return " text-center ";

        if (str_contains($ColumnName, "calc_"))
            return " text-center ";

        return '';
    }

    protected function getVoucherActivityNote()
    {
        $Output = "Created by ".$this->getDistinctIdData('created_by_name')." on ".DateDecode($this->getDistinctIdData('created_date_time'), 'M d, Y @ h:ia');

        if (!empty($this->getDistinctIdData('updated_by')))
            $Output .= ", Updated by ".$this->getDistinctIdData('updated_by_name')." on ".DateDecode($this->getDistinctIdData('updated_date_time'), 'M d, Y @ h:ia');

        if (!empty($this->getDistinctIdData('deleted_by')))
            $Output .= ", <span class='text-white bg-danger p-1'>Deleted by ".$this->getDistinctIdData('deleted_by_name')." on ".DateDecode($this->getDistinctIdData('deleted_date_time'), 'M d, Y @ h:ia')."</span>";

        return $Output;
    }

    private function addAdditionalColumns()
    {
        foreach ($this->oVoucherPrintUtils->getAdditionalColumns() as $AdditionalColumn)
        {
            if ($AdditionalColumn['table'] == '')
            {
                $ColumnValue = $AdditionalColumn['calculation'];

                $Keys = array_keys($this->MainTableData);
                $Values = array_values($this->MainTableData);

                try {
                    $CalculatedValue = str_replace($Keys, $Values, $ColumnValue);
                    eval("\$CalculatedValue = $CalculatedValue;");
                } catch (Exception $e) {
                    $CalculatedValue = $AdditionalColumn['calculation'];
                }

                $this->insertAdditionalColumnInBetween($this->MainTableData, $AdditionalColumn['before_column'], [$AdditionalColumn['column'] => $CalculatedValue]);
            }
            else
            {
                $ColumnValue = $AdditionalColumn['calculation'];

                foreach ($this->DataTableData[$AdditionalColumn['table']] as $RowNumber => $RowData)
                {
                    $Keys = array_keys($RowData);
                    $Values = array_values($RowData);

                    try {
                        $CalculatedValue = str_replace($Keys, $Values, $ColumnValue);
                        eval("\$CalculatedValue = $CalculatedValue;");
                    } catch (Exception $e) {
                        $CalculatedValue = $AdditionalColumn['calculation'];
                    }

                    $this->insertAdditionalColumnInBetween($this->DataTableData[$AdditionalColumn['table']][$RowNumber], $AdditionalColumn['before_column'], [$AdditionalColumn['column'] => $CalculatedValue]);
                }
            }
        }
    }

    private function removeHiddenColumns()
    {
        foreach ($this->oVoucherPrintUtils->getHiddenColumns() as $ColumnName)
        {
            unset($this->MainTableData[$ColumnName]);

            foreach ($this->DataTableData as $TableKey => $TableData)
                foreach ($TableData as $RowNumber => $TableRow)
                    unset($this->DataTableData[$TableKey][$RowNumber][$ColumnName]);
        }
    }

    private function insertAdditionalColumnInBetween(&$Array, $BeforeKey, $NewElement)
    {
        if ($BeforeKey != '')
        {
            $Position = array_search($BeforeKey, array_keys($Array));
            $Array = array_merge(
                array_slice($Array, 0, $Position),
                $NewElement,
                array_slice($Array, $Position)
            );
        }
        else
            $Array = array_merge($Array, $NewElement);
    }

    function isTotalRequired($ColumnName, $isDataRow=true)
    {
        if ($this->oVoucherPrintUtils->isTotalRequired($ColumnName))
        {
            echo $isDataRow ? ' data-js="total_'.$ColumnName.'" ' : ' data-total="total_'.$ColumnName.'" ';
        }
    }

    function getColumnWidth($ColumnName)
    {
        $Width = $this->oVoucherPrintUtils->getColumnWidth($ColumnName);
        echo empty($Width) ? '' : (" width='".$Width."' ");
    }

    function getPageTitle()
    {
        $Title = VoucherType::getCode($this->oVoucher->getVoucherType())."-".$this->oVoucher->getVoucherNumber();

        $ColumnOrStingNames = $this->oVoucherPrintUtils->getPageTitle();

        if (empty($ColumnOrStingNames))
        {
            $Title .= " - ";
            $Title .= VoucherType::getName($this->oVoucher->getVoucherType());
        }
        foreach ($ColumnOrStingNames as $ColName)
        {
            $Title .= " - ";

            if ($ColName == 'voucher_date')
                $Title .= DateDecode($this->getDistinctIdData($ColName), 'M d, y');
            else
            {
                if ($this->MainTableData[$ColName])
                {
                    if ($this->oVoucher instanceof DefaultVoucherPrintAdditionalFormatting)
                    {
                        $FormattedValue = $this->oVoucher->getFormattedColumnValue($ColName, $this->MainTableData[$ColName]);
                        if ($FormattedValue != null)
                            $Title .= $FormattedValue;
                        else
                            $Title .= $this->MainTableData[$ColName];
                    }
                    else
                        $Title .= $this->MainTableData[$ColName];
                }
                else
                    $Title .= $ColName;
            }
        }

        return $Title;
    }

    function footer()
    {
        ?>
        <div class="col-sm-6">
            <span class="font-italic"><small><?=$this->getVoucherActivityNote() ?></small></span>
        </div>
        <div class="col-sm-6 text-right">
            <? ProjectHelper::getFooterNote() ?>
        </div>
        <?php
    }
}