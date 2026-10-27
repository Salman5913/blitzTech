<?php

class SalaryPayment extends BasicVoucher
{
    function __construct()
    {
        parent::__construct("hr_salary_data_payment", '' ,"", VoucherType::SLRY);
    }

    static function getIdBySalaryDataId($SalaryDataId)
    {
        $Select = DBQM::query("SELECT id FROM hr_salary_data_payment WHERE salary_data_id = ".escape($SalaryDataId));
        return DBQM::result($Select, 0);
    }

    static function getPaymentDetails($SalaryDataId)
    {
        $Select = DBQM::query("SELECT * FROM hr_salary_data_payment WHERE salary_data_id = ".escape($SalaryDataId));
        return DBQM::fetch_assoc($Select);
    }

    protected function saveTransactionEntries()
    {
    }
}