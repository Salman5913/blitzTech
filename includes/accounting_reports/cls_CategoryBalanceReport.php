<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 007, 1/7/2015
 * Time: 12:01 PM
 */

class CategoryBalanceReport extends AccountingReport
{
    function __construct() {
        parent::__construct();
    }

    function getReport($COAId)
    {
        $Where = '';

        $SelectChildIds = DBQM::query("SELECT get_children_ids_full(".escape($COAId, 'int').")");
        $ChildIds = explode(",", DBQM::result($SelectChildIds, 0));

        $Where .= UtilsQueryMaker::date("l.date_time", '', $this->DateTill);
        $Where .= UtilsQueryMaker::inOrEqual("a.coa_id", $ChildIds);

        $Query = "
                    SELECT
                        a.id,
                        a.ac_name AS name,
                        b.mob,
                        SUM(ld.debit - ld.credit) AS balance
                    FROM
                        ledger  l,
                        ledger_data ld,
                        vw_ac_accounts a
                        LEFT JOIN  acc_bio_data b 
                        on 
                        a.id = b.account_id
                    WHERE
                        l.id = ld.ledger_id AND
                        ld.account_id = a.id AND
                        l.year_id = ".escape(Year::getYearId())."
                        {$Where}
                    GROUP BY 
                    	a.id
                    HAVING
                        balance NOT BETWEEN -1 AND 1
                    ORDER BY
                        a.name ASC 
        ";

        $Query .= " ";

        return DBQM::query($Query);
    }
}