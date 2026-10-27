<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 28/12/2014
 * Time: 11:49 AM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("connection/localhost.php");

class CompanyModule
{
    const PROJECT = 'Project';
    const SALES = 'Sales';
    const PURCHASE = 'Purchase';
    const HR = 'HR Management';
    const STOCK = 'Stock';
    const PAYMENT_RECEIPTS = 'Payment/Receipts';
    const BANK_STATEMENT = 'Bank Statement';
    const OTHER_VOUCHERS = 'Other Vouchers (JV/Opening Accounts)';
    const ACCOUNTING = 'Accounting Report';
    const SECURITY_SHELL = 'Security Shell';
    const CHART_OF_ACCOUNT = 'Chart Of Account';
    const CUSTOMIZED_REPORTS = 'Customized Reports';
    const CRM = 'CRM';

    static function isModuleActive($ModuleName)
    {
        return in_array($ModuleName, (array)$_SESSION['ACCESS_MODULE']);
    }
} 