<?php
/**
 * Created by PhpStorm.
 * User: musta
 * Date: 0027, 12, 27, 2019
 * Time: 9:09 PM
 */

class ExpendedReport extends AccountingReport
{
	private $COAId;

	function __construct($COAId) {
		parent::__construct();
		$this->COAId = $COAId;
	}

	function getAllChildCategories()
	{
		$SelectDecedents = DBQM::query("SELECT get_children_ids_full(".escape($this->COAId, 'int').")");
		$ChildIds = DBQM::result($SelectDecedents, 0);

		if (!empty($ChildIds))
		{
			$Where = UtilsQueryMaker::date("l.date_time", $this->EncodedDateFrom, $this->EncodedDateTill);

			return DBQM::query("
									SELECT 
										coa.id,
										coa.name,
										SUM((ld.debit - ld.credit) * ld.currency_rate) AS balance
									FROM
										acc_chart_of_account coa,
										acc_accounts a,
										ledger l,
										ledger_data ld
									WHERE
										a.coa_id = coa.id AND 
										l.id = ld.ledger_id AND 
										ld.account_id = a.id AND
										a.coa_id IN (".$ChildIds.") AND
										l.year_id = ".escape(Year::getYearId())." AND
										a.coa_id <> ".escape($this->COAId)."
										{$Where}
									GROUP BY
										coa.id
									HAVING
										balance <> 0
									ORDER BY
										name ASC
			");
		}
		else
			return false;
	}

	function getAllAccounts()
	{
		$Where = UtilsQueryMaker::date("l.date_time", $this->EncodedDateFrom, $this->EncodedDateTill);

		return DBQM::query("
									SELECT 
										a.id,
										a.name,
										SUM((ld.debit - ld.credit) * ld.currency_rate) AS balance
									FROM
										acc_accounts a,
										ledger l,
										ledger_data ld
									WHERE
										l.id = ld.ledger_id AND 
										ld.account_id = a.id AND
										l.year_id = ".escape(Year::getYearId())." AND
										a.coa_id = ".escape($this->COAId)."
										{$Where}
									GROUP BY
										a.id
									HAVING
										balance <> 0
									ORDER BY
										name ASC
		");
	}

	function getBreadcrumb()
	{
		$Select = $this->oChartOfAccountCategory->getAllAncestors($this->COAId);

		$Breadcrumb = [];
		while ($Row = DBQM::fetch_assoc($Select))
			$Breadcrumb[] = '<a href="expanded_report.php?date_from='.$this->DateFrom.'&date_till='.$this->DateTill.'&coa_id='.$Row['id'].'">'.$Row['name'].'</a>';

		$Breadcrumb = array_reverse($Breadcrumb);

		return implode(' &nbsp; <i class="fa fa-angle-right"></i> &nbsp; ', $Breadcrumb);
	}
}