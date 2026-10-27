<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 007, 1/7/2015
 * Time: 10:58 AM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("../../connection/localhost.php");

class AccountingReport
{
    protected $DateFrom;
    protected $DateTill;

    protected $EncodedDateFrom;
    protected $EncodedDateTill;


    /**
     * @var ChartOfAccountCategory
     */
    protected $oChartOfAccountCategory = null;

    function __construct()
    {
        $this->oChartOfAccountCategory = new ChartOfAccountCategory();
    }

    function getAllLevel1()
    {
        return $this->oChartOfAccountCategory->getAllLevel1();
    }

    function setDate($DateFrom, $DateTill)
    {
        $this->DateFrom = $DateFrom;
        $this->DateTill = $DateTill;

        $this->EncodedDateFrom = DateEncode($DateFrom, 'Y-m-d',true);
        $this->EncodedDateTill = DateEncode($DateTill, 'Y-m-d', true);
    }

    function getLedgerAmountByDuration($AccountId, $DateFrom, $DateTill)
    {
        $Query = "
                    SELECT
                        SUM(ld.debit - ld.credit)
                    FROM
                        ledger l,
                        ledger_data ld
                    WHERE
                        l.id = ld.ledger_id AND
                        l.year_id = ".escape(Year::getYearId())." AND
                        ld.account_id = ".escape($AccountId);

        $Query .= UtilsQueryMaker::date("l.date_time", $DateFrom, $DateTill);

        $Select = DBQM::query($Query);

        return DBQM::result($Select, 0);
    }

    function getAllSubCategories($COAId)
    {
        $SelectChildIds = DBQM::query("SELECT get_children_ids_full(".escape($COAId, 'int').")");
        $ChildIds = explode(",", DBQM::result($SelectChildIds, 0));

        $Select = "
	        SELECT
	            *
            FROM
            	acc_chart_of_account
            WHERE
            	id IN (".implode(",", array_map("escape", $ChildIds)).")
            ORDER BY
                name
	    ";

        return DBQM::query($Select);
    }
}