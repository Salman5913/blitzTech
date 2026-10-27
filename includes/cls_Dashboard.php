<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 014, 1/14/2015
 * Time: 12:13 PM
 */


class Dashboard extends DashboardStats
{
    /**
     * @return array 0: bool, 1: reason
     */
    function getApplicationStatus()
    {
        // ***** Get Unbalanced Vouchers ***** //
        $SelectUnBalancedVouchers = LedgerReport::getUnbalancedVouchers();
        $UnBalancedVouchers = [];
        while($Row = DBQM::fetch_assoc($SelectUnBalancedVouchers))
            $UnBalancedVouchers[] = $Row['abbr']."-".$Row['voucher_number'];
        if (count($UnBalancedVouchers) > 0)
            return [false, "Please check and re-save these vouchers <strong>".implode(", ", $UnBalancedVouchers)."</strong>"];
        // ***** Get Unbalanced Vouchers ***** //



        // ***** Check Ledger (Opening Balances) ***** //
        list($TotalDebit, $TotalCredit) = LedgerReport::getLedgerStatus();

        $LedgerDifference = $TotalDebit - $TotalCredit;
        if (round($LedgerDifference) <> 0)
            return [false, "Ledger is out by ".ProjectHelper::DEFAULT_CURRENCY." <strong>".Utils::format($LedgerDifference)."</strong>"];
        // ***** Check Ledger (Opening Balances) ***** //

        return [true, ''];
    }

    function getCashFlow($MonthYear)
    {
        $Select = DBQM::query("
					SELECT 
                        SUM(ld.debit) as total_in,
				       	SUM(ld.credit) as total_out
                    FROM
                        vw_ac_accounts a,
                        ledger l,
                        ledger_data ld                                    			
                    WHERE
                        l.id = ld.ledger_id AND
                        ld.account_id = a.id AND
                        a.coa_id IN (
                            ".escape(getConstantCategory()->getCashInHand()).",
                            ".escape(getConstantCategory()->getBank())."
                        ) AND
                        l.voucher_type_id NOT IN (
                            ".escape(VoucherType::OPA)."
                        ) AND     
                        l.date_time LIKE ".escape($MonthYear)."
		");

        return [DBQM::result($Select, 0, 0), DBQM::result($Select, 0, 1)];
    }

    function getIncomeByChartValues()
    {
        $Months = Year::getMonthsOfCurrentYear();

        $SelectRevenueHeads = DBQM::query("SELECT get_children_ids_full(4)");
        $RevenueCOAIds = DBQM::result($SelectRevenueHeads, 0);

        $SelectExpenseHeads = DBQM::query("SELECT get_children_ids_full(5)");
        $ExpenseCOAIds = DBQM::result($SelectExpenseHeads, 0);

        $Query = [];
        foreach ($Months AS $Key => $Dates)
        {
            $Query[] = "
				SELECT 
					SUM((debit - credit) * -1) AS sum_amount,
					0 AS cogs,
					'revenue_".$Key."' AS description,
					'".date("M y", strtotime($Dates['start']))."' AS month_name
				FROM 
					ledger l,
					ledger_data ld,
					acc_accounts a
				WHERE
					l.id = ld.ledger_id AND 
					ld.account_id = a.id AND 
					l.year_id = ".escape(Year::getYearId())." AND
					a.coa_id IN (".$RevenueCOAIds.") AND
					l.date_time BETWEEN ".escape($Months[$Key]['start'])." AND ".escape($Months[$Key]['end'])."
			";

            $Query[] = "
				SELECT 
					SUM((debit - credit)) AS sum_amount,
					get_cogs(".escape($Months[$Key]['start']).", ".escape($Months[$Key]['end']).", ".escape(getConstantAccount()->getPurchaseAccount()).") AS cogs,
					'expense_".$Key."' AS description,
					'".date("M y", strtotime($Dates['start']))."' AS month_name
				FROM 
					ledger l,
					ledger_data ld,
					acc_accounts a
				WHERE
					l.id = ld.ledger_id AND 
					ld.account_id = a.id AND 
					a.coa_id <> ".escape(getConstantCategory()->getCostOfGoodSold())." AND
					l.year_id = ".escape(Year::getYearId())." AND
					a.coa_id IN (".$ExpenseCOAIds.") AND
					l.date_time BETWEEN ".escape($Months[$Key]['start'])." AND ".escape($Months[$Key]['end'])."
			";
        }

        $Query = implode(" UNION ", $Query);

        $Label = [];
        $Revenue = [];
        $Expense = [];
        $Income = [];
        $Select = DBQM::query($Query);

        while($Row = DBQM::fetch_assoc($Select))
        {
            if (strstr($Row['description'], 'revenue'))
            {
                $Label[] = $Row['month_name'];
                $Revenue[] = !empty($Row['sum_amount']) ? intval($Row['sum_amount']) : 0;
            }
            else
            {
                $Expense[] = !empty($Row['sum_amount'] + $Row['cogs']) ? intval($Row['sum_amount'] + $Row['cogs']) : 0;
            }
        }

        for ($i=0; $i<count($Revenue); $i++)
            $Income[] = $Revenue[$i] - $Expense[$i];

        return [$Label, $Revenue, $Expense, $Income];
    }

    function getIncomeByChartValues2()
    {
        $Months = Year::getMonthsNameOfCurrentYear();

        $SelectRevenueHeads = DBQM::query("SELECT get_children_ids_full(4)");
        $RevenueCOAIds = DBQM::result($SelectRevenueHeads, 0);

        $SelectExpenseHeads = DBQM::query("SELECT get_children_ids_full(5)");
        $ExpenseCOAIds = DBQM::result($SelectExpenseHeads, 0);

        $QueryData = [];
        $Select = DBQM::query("
                SELECT
                    SUM( revenue ) AS revenue,
                    SUM( expenses ) AS expenses,
                    get_cogs_by_month ( month_name, ".escape(getConstantAccount()->getPurchaseAccount())." ) AS cogs,
                    month_name 
                FROM
                    (
                    SELECT
                        SUM(( debit - credit ) * - 1 ) AS revenue,
                        0 AS expenses,
                        CONCAT(MONTHNAME( l.date_time ),' ',YEAR( l.date_time )) AS month_name 
                    FROM
                        ledger l,
                        ledger_data ld,
                        acc_accounts a 
                    WHERE
                        l.id = ld.ledger_id AND 
                        ld.account_id = a.id AND 
                        l.year_id = ".escape(Year::getYearId())." AND 
                        a.coa_id IN ( ".$RevenueCOAIds." ) 
                    GROUP BY
                        MONTH ( l.date_time ), YEAR(l.date_time)
                        
                    UNION
                    
                    SELECT
                        0 AS revenue,
                        SUM(( debit - credit ) * - 1 ) AS expenses,
                        CONCAT(MONTHNAME( l.date_time ),' ',YEAR( l.date_time )) AS month_name 
                    FROM
                        ledger l,
                        ledger_data ld,
                        acc_accounts a 
                    WHERE
                        l.id = ld.ledger_id AND 
                        ld.account_id = a.id AND
                        l.year_id = ".escape(Year::getYearId())." AND
                        a.coa_id IN ( ".$ExpenseCOAIds." ) 
                    GROUP BY
                        MONTH ( l.date_time ), YEAR(l.date_time)
                    ) AS tmp 
                GROUP BY
                    month_name
        ");

        while ($Row = DBQM::fetch_assoc($Select))
        {
            $Expenses = intval($Row['expenses'] + $Row['cogs']);
            $Income = $Row['revenue'] - $Expenses;

            $QueryData[$Row['month_name']] = [$Row['revenue'], $Expenses, $Income];
        }

        $Label = [];
        $Revenue = [];
        $Expense = [];
        $Income = [];

        foreach ($Months AS $Key => $MonthName)
        {
            $Label[$Key] = $MonthName;
            $Revenue[$Key] = !empty($QueryData[$MonthName][0]) ? $QueryData[$MonthName][0] : 0;
            $Expense[$Key] = !empty($QueryData[$MonthName][1]) ? $QueryData[$MonthName][1] : 0;
            $Income[$Key] = !empty($QueryData[$MonthName][2]) ? $QueryData[$MonthName][2] : 0;

        }

        return [$Label, $Revenue, $Expense, $Income];
    }

    function getCashBankBalances()
    {
        return DBQM::query("
	                                SELECT 
                                    	a.id,
	                                    a.ac_name AS name, 
	                                    SUM(ld.debit - ld.credit) as balance
                                    FROM
                                    	ledger l,
                                        ledger_data ld,
                                        vw_ac_accounts a                             			
                                    WHERE
                                      	l.id = ld.ledger_id AND
                                      	ld.account_id = a.id AND
                                      	l.year_id = ".escape(Year::getYearId())." AND
                                    	a.coa_id IN (
                                         	".escape(getConstantCategory()->getCashInHand()).",
                                            ".escape(getConstantCategory()->getBank())."
                                        )
                                    GROUP BY
                                        a.id
	    ");
    }

    function getTopCustomersBySalesValue()
    {
        $Where = UtilsQueryMaker::date("did.voucher_date", date("m/d/Y", strtotime("-30 days")), date("m/d/Y"));

        $Select = DBQM::query("
            SELECT
                sl.customer_account_id,
                a.name,
                SUM(did.voucher_total) AS total_sales
            FROM
                sl_sales sl,
                distinct_id did,
                vw_ac_accounts a
            WHERE 
                sl.distinct_id = did.id AND
                sl.customer_account_id = a.id AND
                did.is_deleted <> 'Y'
                {$Where}
            GROUP BY
                sl.customer_account_id
            ORDER BY
                total_sales DESC
        ");

        $Array = [];
        while ($Row = DBQM::fetch_assoc($Select))
        {
            if (count($Array) < 5)
                $Array[$Row['name']] = $Row['total_sales'];
            else
                $Array["Other"] += $Row['total_sales'];
        }

        $ArrayNames = [];
        $ArrayAmount = [];
        foreach ($Array as $Name => $Amount)
        {
            $ArrayNames[] = $Name;
            $ArrayAmount[] = $Amount;
        }
        return [$ArrayNames, $ArrayAmount];
    }

    function getTopItemsByProfitValue()
    {
        $Where = UtilsQueryMaker::date("stl.date_time", date("m/d/Y", strtotime("-30 days")), date("m/d/Y"));

        $Select = DBQM::query("
            SELECT
                st.ac_stock,
                SUM(stl.qty * (stl.sp - stb.cost) * -1) AS total_profit
            FROM
                vw_ac_stock st,
                st_stock_batch stb,
                st_stock_ledger stl
            WHERE 
                st.id = stb.stock_id AND
                st.attribute_id <=> stb.attribute_id AND
                stb.id = stl.batch_id AND
                voucher_type_id IN (".VoucherType::getSalesVoucherTypes().", ".escape(VoucherType::SRV).")
                {$Where}
            GROUP BY
                stb.stock_id, stb.attribute_id
            HAVING
                total_profit > 0
            ORDER BY
                total_profit DESC
        ");

        $Array = [];
        while ($Row = DBQM::fetch_assoc($Select))
        {
            if (count($Array) < 5)
                $Array[$Row['ac_stock']] = $Row['total_profit'];
            else
                $Array["Other"] += $Row['total_profit'];
        }

        $ArrayNames = [];
        $ArrayAmount = [];
        foreach ($Array as $Name => $Amount)
        {
            $ArrayNames[] = $Name;
            $ArrayAmount[] = $Amount;
        }
        return [$ArrayNames, $ArrayAmount];
    }
}