<?php


abstract class Constant
{
    public function __construct()
    {
        if (!isset($_SESSION['__constants_coa']) OR !isset($_SESSION['__constants_accounts']))
        {
            $Select = DBQM::query("SELECT * FROM cp_common_coa WHERE company_id = ".escape(Company::getLoggedInCompanyId()));

            while ($Row = DBQM::fetch_assoc($Select))
            {
                if (!empty($Row['account_id']))
                    $_SESSION['__constants_accounts'][$Row['type']] = $Row['account_id'];
                else
                    $_SESSION['__constants_coa'][$Row['type']] = $Row['coa_id'];
            }
        }
    }

    protected function getAccountId($Type)
    {
        return $_SESSION['__constants_accounts'][$Type];
    }

    protected function getCategory($Type)
    {
        return $_SESSION['__constants_coa'][$Type];
    }
}