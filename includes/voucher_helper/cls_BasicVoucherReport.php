<?php

class BasicVoucherReport
{
    /**
     * @var BasicVoucher
     */
    protected $oBasicVoucher;

    /**
     * @var BasicVoucherReportFilters
     */
    protected $oBasicVoucherReportFilter;

    /**
     * @var BasicVoucherReportTableColumn
     */
    protected $oBasicVoucherReportTableColumn;

    public function __construct(BasicVoucher $oBasicVoucher)
    {
        $this->oBasicVoucher = $oBasicVoucher;

        if ($oBasicVoucher instanceof DefaultVoucherReport)
        {
            $this->oBasicVoucherReportFilter = $oBasicVoucher->getReportFilters();
            $this->oBasicVoucherReportTableColumn = $oBasicVoucher->getReportTableColumn();
        }
        else
        {
            $this->oBasicVoucherReportFilter = new BasicVoucherReportFilters();
            $this->oBasicVoucherReportTableColumn = new BasicVoucherReportTableColumn();
        }
    }

    function designFilters()
    {
        foreach ($this->oBasicVoucherReportFilter->getFilters() as $Column => $FilterData)
        {
            if ($FilterData['datatype'] == 'date') {
                ?>
                <div class="col-xl-6 col-md-6">
                    <div class="form-group mb-0">
                        <label>Date Range</label>
                        <div class="input-daterange input-group" data-provide="datepicker">
                            <input type="text" class="form-control" name="date_from" data-type="date" value="<?=isset($_GET['date_from']) ? $_GET['date_from'] : date("M d, Y", strtotime("-15 Days"))?>" data-empty="true" />
                            <input type="text" class="form-control" name="date_till" data-type="date" value="<?=isset($_GET['date_till']) ? $_GET['date_till'] : date("M d, Y") ?>" data-empty="true" />
                        </div>
                    </div>
                </div>
                <?php
            }
            else
            {
                ?>
                <div class="col-xl-3 col-md-6">
                    <div class="form-group mb-0">
                        <label for="<?=$Column?>"><?=$FilterData['label']?></label>
                        <? $this->getFilterField($Column, $FilterData); ?>
                    </div>
                </div>
                <?php
            }
        }

        if ($this->hasCostCenterFilter())
            CostCenter::designReportFilter('col-xl-3 col-md-6');
    }

    /** Vouchers that carry a cost center (the ones with ledger entries) can be filtered on it */
    function hasCostCenterFilter()
    {
        return ProjectHelper::HAS_COST_CENTER and $this->oBasicVoucher->isLedgerObjectRequired();
    }

    function getFilterField($Column, $FilterData)
    {
        $ColumnValue = $_GET[str_replace(".", "_", $Column)];
        if ($FilterData['datatype'] == 'select')
        {
            ?>
            <select name="<?=$Column?>" id="<?=$Column?>"><? Utils::getDropDownOptionsFromArray($FilterData['additional_data'], $ColumnValue); ?></select>
            <?php
        }
        elseif ($FilterData['datatype'] == 'int')
        {
            ?>
            <input type="text" name="<?=$Column?>" id="<?=$Column?>" data-type="int" value="<?=$ColumnValue?>" >
            <?php
        }
        else
        {
            ?>
            <input type="text" name="<?=$Column?>" id="<?=$Column?>" <?php if (!empty($FilterData['autocomplete_type'])) { Autocomplete::designAutocomplete($FilterData['autocomplete_type'], $FilterData['additional_data'], $ColumnValue); } else { ?> value="<?=$_GET['value']?>" <? } ?>>
            <?php
        }
    }

    function getReportTableColumns()
    {
        return $this->oBasicVoucherReportTableColumn->getColumns();
    }

    function getCSSClass($Type, $ColumnName)
    {
        if (!empty($this->oBasicVoucherReportTableColumn->getColumns()[$ColumnName]['css_class']))
            return $this->oBasicVoucherReportTableColumn->getColumns()[$ColumnName]['css_class'];

        if ($Type == 'int' or $Type == 'double')
            return " text-center ";

        return '';
    }

    function getReadableValue($Type, $Value)
    {
        if ($Type == BasicVoucherReportTableColumn::TYPE_INT)
            return Utils::format($Value, 0);

        elseif ($Type == 'double' or $Type == BasicVoucherReportTableColumn::TYPE_FLOAT)
            return Utils::format($Value, 2);

        elseif ($Type == BasicVoucherReportTableColumn::TYPE_DATE)
            return DateDecode($Value, 'M d, Y');

        elseif ($Type == BasicVoucherReportTableColumn::TYPE_LINK)
            return "<a href='{$Value}' target='_blank'>Click Here</a>";

        elseif ($Type == BasicVoucherReportTableColumn::TYPE_LONGTEXT)
            return nl2br($Value);

        elseif (strlen($Value) == 1)
        {
            if ($Value == 'N')
                return 'No';
            elseif ($Value == 'Y')
                return 'Yes';
        }

        return $Value;
    }

    function hasFilters()
    {
        return !empty(($this->oBasicVoucherReportFilter->getFilters())) or $this->hasCostCenterFilter();
    }
}