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

    /* ************************* Cost Center Filter ************************* */
    /**
     * " AND ..." limiting a ledger query to the cost center picked in the report filter (GET cost_center_id),
     * '' when none is picked. $LedgerAlias is the alias (or table name) whose distinct_id column is filtered.
     */
    static function costCenterSql($LedgerAlias = 'l')
    {
        return CostCenter::getFilterSql("{$LedgerAlias}.distinct_id");
    }

    /** "&cost_center_id=N" to carry the cost center filter into drill-down links, '' when none is picked */
    static function costCenterUrlParam()
    {
        return !empty($_GET['cost_center_id']) ? '&cost_center_id='.(int)$_GET['cost_center_id'] : '';
    }

    /** " - Cost Center: X" for report / print headings, '' when none is picked */
    static function costCenterHeading()
    {
        $Name = !empty($_GET['cost_center_id']) ? CostCenter::getName((int)$_GET['cost_center_id']) : '';
        return $Name != '' ? ' - Cost Center: '.$Name : '';
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

        $Query .= self::costCenterSql('l');
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