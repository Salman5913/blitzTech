<?php

class PivotTableLedger extends PivotTable
{
    public function __construct()
    {
        parent::__construct('vw_pivot_ledger', [
            'day_in_number' => 'Day of Month',
            'month_in_number' => 'Month (In Number)',
            'year_in_number' => 'Year',
            'year_and_month' => 'Year-Month',
            'ac_name' => 'Account Name',
            'category_name' => 'Category',
            'account_type' => 'Type of Account',
            'voucher_type_name' => 'Voucher Type',
            'currency' => 'Currency'
        ], [
            'amount' => 'Amount'
        ]);

        $this->attachColumnType('month_in_number', PivotTableViewColumnType::MONTH_NUMBERS);
        $this->attachColumnType('ac_name', PivotTableViewColumnType::ACCOUNT_NAME);
        $this->attachColumnType('category_name', PivotTableViewColumnType::CATEGORY);
        $this->attachColumnType('voucher_type_name', PivotTableViewColumnType::VOUCHER_TYPE);
    }
}