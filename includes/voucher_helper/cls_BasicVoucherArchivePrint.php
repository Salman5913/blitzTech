<?php

class BasicVoucherArchivePrint extends BasicVoucherPrint
{
    protected readonly array $ArchiveData;

    function __construct(BasicVoucher $oVoucher, protected $ArchiveId)
    {
        $this->getArchiveData();
        parent::__construct($oVoucher);
        $this->oVoucherPrintUtils->setIsSignatureRequire(false);
    }

    private function getArchiveData()
    {
        $Select = DBQM::query("SELECT * FROM voucher_modification_tracker WHERE id = ".escape($this->ArchiveId));
        $this->ArchiveData = DBQM::fetch_assoc($Select);
    }

    protected function setMainTableData()
    {
        $TableName = $this->oVoucher->getTableName();

        $Row = json_decode($this->ArchiveData['main_table_data'], true);
        unset($Row['voucher_number']);
        unset($Row['voucher_total']);
        unset($Row['is_updated']);
        unset($Row['is_deleted']);

        $RowDetails = $this->oVoucher->getDetails();

        foreach ($Row as $ColumnName => $Value)
        {
            // ---------- Setting main table data via looping on all columns so we can have field names as well ------------ //
            $this->setTableData(
                $ColumnName,
                $ColumnName == 'voucher_date' ? date('M d, Y', strtotime($Value)) : $Value,
                $this->MainTableData,
                '',
                $TableName,
                $this->MainTableDataAdditionalFormatting,
                $RowDetails[$ColumnName] == $Value ? '' : 'bg-soft-danger'
            );
        }
    }

    protected function setDataTableData()
    {
        $DataIdColumnName = $this->oVoucher->getDataIdColumnName();
        $i=1;
        foreach ($this->oVoucher->getDataTableName() AS $Key => $TableName)
        {
            $SelectData = $this->oVoucher->getData($Key);

            // ---------- Get Data from Data Table ------------ //
            $Rows = json_decode($this->ArchiveData['data_table_'.$i], true);

            $RecordCount = 0;
            foreach ($Rows as $Row)
            {
                $CurrentRowData = DBQM::fetch_assoc($SelectData);

                // ---------- Setting main table data via looping on all columns so we can have field names as well ------------ //
                foreach ($Row as $ColumnName => $Value)
                {
                    $this->setTableData(
                        $ColumnName,
                        $Value,
                        $this->DataTableData[$Key][$RecordCount],
                        $DataIdColumnName,
                        $TableName,
                        $this->DataTableDataAdditionalFormatting[$Key][$RecordCount],
                        $CurrentRowData[$ColumnName] == $Value ? '' : 'bg-soft-danger'
                    );
                }
                $RecordCount++;
            }

            $i++;
        }
    }

    protected function getVoucherActivityNote()
    {
        $Output = "Created by ".$this->getDistinctIdData('created_by_name')." on ".DateDecode($this->getDistinctIdData('created_date_time'), 'M d, Y @ h:ia');

        $Output .= ", Updated by ".SecurityShell::getUserNameById($this->ArchiveData['updated_by'])." on ".DateDecode($this->ArchiveData['updated_date_time'], 'M d, Y @ h:ia');

        return $Output;
    }
}