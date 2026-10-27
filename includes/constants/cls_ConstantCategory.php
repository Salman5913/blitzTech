<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 25/12/2014
 * Time: 9:35 AM
 */

class ConstantCategory extends Constant
{
    const CASH_IN_HAND = 'Cash-in-hand';
    const BANK_ACCOUNTS = 'Bank Accounts';
    const CUSTOMER = 'Customer';
    const COST_OF_GOOD_SOLD = 'Cost of Goods Sold';
    const RECEIVABLE = 'Sundry Receivable';
    const PAYABLE = "Sundry Payable";
    const CURRENT_ASSET = "Current Asset";
    const EMPLOYEE = "Employee";
    const SALESMAN = "Salesman";
    const SUPPLIER = "Supplier";
    const LOAN_GIVEN = "Loan Given";

    function getCashInHand()
    {
        return $this->getCategory(self::CASH_IN_HAND);
    }

    function getCurrentAsset()
    {
        return $this->getCategory(self::CURRENT_ASSET);
    }

    function getBank()
    {
        return $this->getCategory(self::BANK_ACCOUNTS);
    }

    function getReceivable()
    {
        return $this->getCategory(self::RECEIVABLE);
    }

    function getPayable()
    {
        return $this->getCategory(self::PAYABLE);
    }

    function getCustomer()
    {
        return $this->getCategory(self::CUSTOMER);
    }

    function getCostOfGoodSold()
    {
        return $this->getCategory(self::COST_OF_GOOD_SOLD);
    }

    function getEmployee()
    {
        return $this->getCategory(self::EMPLOYEE);
    }

    function getLoanGiven()
    {
        return $this->getCategory(self::LOAN_GIVEN);
    }

    function getSalesMan()
    {
        return $this->getCategory(self::SALESMAN);
    }

    function getSupplier()
    {
        return $this->getCategory(self::SUPPLIER);
    }
}