<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 25/12/2014
 * Time: 9:35 AM
 */

class ConstantAccount extends Constant
{
    const PURCHASE = 'Purchase';
    const NON_INVENTORY_PURCHASE = 'Non Inventory Purchase';
    const CHEQUE_IN_HAND = 'Cheques In Hand';
    const PETTY_CASH = 'Petty Cash';
    const SALES = 'Sales';
    const ADVANCE_TAX = 'Advance Tax';
    const GST = 'GST';
    const EQUITY_INVESTMENT = 'Initial Investment';
    const WHT = 'WHT';
    const CASH_CUSTOMER = 'Cash Customer';
    const CASH_SUPPLIER  = 'Cash Supplier';
    const PROJECT_SALES = 'Project Sales';
    const COST_OF_GOOD_SOLD = 'COGS';
    const LOAN_ADVANCES_BAD_DEBTS = 'Loan/Advance Bad Debts';
    const SALARY_EXPENSE = 'Salary';

    function getPurchaseAccount()
    {
        return $this->getAccountId(self::PURCHASE);
    }

    function getCOGS()
    {
        return $this->getAccountId(self::COST_OF_GOOD_SOLD);
    }

    function getNonInventoryPurchasesAccount()
    {
        return $this->getAccountId(self::NON_INVENTORY_PURCHASE);
    }

    function getWHT()
    {
        return $this->getAccountId(self::WHT);
    }

    function getEquityInvestmentAccount()
    {
        return $this->getAccountId(self::EQUITY_INVESTMENT);
    }

    function getChequeAccount()
    {
        return $this->getAccountId(self::CHEQUE_IN_HAND);
    }

    function getPettyCashAccount()
    {
        return $this->getAccountId(self::PETTY_CASH);
    }

    function getSalesAccount()
    {
        return $this->getAccountId(self::SALES);
    }

    function getAdvanceTaxAccount()
    {
        return $this->getAccountId(self::ADVANCE_TAX);
    }

    function getGSTAccount()
    {
        return $this->getAccountId(self::GST);
    }

    function getCashCustomer()
    {
        return $this->getAccountId(self::CASH_CUSTOMER);
    }

    function getCashSupplier()
    {
        return $this->getAccountId(self::CASH_SUPPLIER);
    }

    function getProjectSales()
    {
        return $this->getAccountId(self::PROJECT_SALES);
    }

    function getLoanBadDebtsAccount()
    {
        return $this->getAccountId(self::LOAN_ADVANCES_BAD_DEBTS);
    }

    function getSalaryAccount()
    {
        return $this->getAccountId(self::SALARY_EXPENSE);
    }
}