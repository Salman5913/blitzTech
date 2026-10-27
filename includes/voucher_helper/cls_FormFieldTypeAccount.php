<?php

class FormFieldTypeAccount extends FormFieldType
{
    public function __construct($CurrentBalanceColumnName, $CurrencyRateColumnName, array $AccountCategory=[])
    {
        $this->AdditionalDetails['category'] = $AccountCategory;
        $this->AdditionalDetails['current_balance_column_name'] = $CurrentBalanceColumnName;
        $this->AdditionalDetails['currency_rate_column_name'] = $CurrencyRateColumnName;
    }

    function getFieldName(): null|string
    {
        return null;
    }

    function getFieldDesign($Value, $CommonAttributes): void
    {
        $Category = $this->getAdditionalDetails()['category'];
        $CurrentBalanceColumnName = $this->getAdditionalDetails()['current_balance_column_name'];
        $CurrencyRateColumnName = $this->getAdditionalDetails()['currency_rate_column_name'];
        ?>
        <input
            <?=$CommonAttributes?>
            <? empty($Category) ? Autocomplete::ACSearchAccount($Value) : Autocomplete::ACSearchAccountByCOAId($Category, $Value) ?>
            data-current-balance-column-name="<?=$CurrentBalanceColumnName?>"
            data-currency_rate_column_name="<?=$CurrencyRateColumnName?>"
        />
        <?php
    }
}