<?php
/**
 * Created by PhpStorm.
 * User: musta
 * Date: 0002, 1, 2, 2020
 * Time: 12:31 PM
 */

class YearClosing
{
	private $RowOldYear;
	private $RowNewYear;
	private $LedgerId;
	private $oLedger;
	private $OldYearId;
	private $NewYearId;

	function __construct($OldYearId, $NewYearId)
	{
		$this->OldYearId = $OldYearId;
		$this->NewYearId = $NewYearId;

		$SelectOldYear = DBQM::query("SELECT * FROM sas_year WHERE id = ".escape($OldYearId));
		$this->RowOldYear = DBQM::fetch_assoc($SelectOldYear);

		$SelectNewYear = DBQM::query("SELECT * FROM sas_year WHERE id = ".escape($NewYearId));
		$this->RowNewYear = DBQM::fetch_assoc($SelectNewYear);

		Year::setLoginYearId($NewYearId);

		$this->oLedger = new Ledger();
		$this->oLedger->deleteByDistinctId($this->RowNewYear['distinct_id']);
		$this->LedgerId = $this->oLedger->createNew(DistinctId::createNew($this->RowNewYear['distinct_id']), date("Y-m-d H:i:s", strtotime($this->RowNewYear['start_date']." -1 sec")), VoucherType::OP, 1);

		Year::setLoginYearId($OldYearId);

		$this->moveMovableBalances();
		$this->shiftProfitLoss();
	}

	private function moveMovableBalances()
	{
		$Select = DBQM::query("
					SELECT 
						ld.account_id, 
						SUM((ld.debit - ld.credit)) AS current_balance,
						ld.currency_rate
					FROM 
						sas_ledger_data ld, 
						sas_ledger l, 
						acc_accounts a 
					WHERE 
						ld.ledger_id = l.id AND 
						ld.account_id = a.id AND 
						l.year_id = ".escape($this->OldYearId)." AND
						(
							a.ac_code LIKE '01%' OR 
							a.ac_code LIKE '02%' OR 
							a.ac_code LIKE '03%'
						) 
					GROUP BY 
						account_id
		");

		while ($Row = DBQM::fetch_assoc($Select))
		{
			$SelectLastCurrencyRate = DBQM::query("SELECT ld.currency_rate FROM sas_ledger_data ld, sas_ledger l WHERE l.id = ld.ledger_id AND year_id = ".escape($this->OldYearId)." AND account_id = ".escape($Row['account_id'])." ORDER BY date_time DESC LIMIT 1");

			$this->oLedger->saveData($this->LedgerId, $Row['account_id'], $Row['current_balance'], DBQM::result($SelectLastCurrencyRate, 0), 'Balance Carry Forward from '.$this->RowOldYear['year_name']);
		}
	}

	private function shiftProfitLoss()
	{
		$Select = DBQM::query("
					SELECT 
						SUM((ld.debit - ld.credit)) AS profit_loss
					FROM 
						sas_ledger_data ld, 
						sas_ledger l, 
						acc_accounts a 
					WHERE 
						ld.ledger_id = l.id AND 
						ld.account_id = a.id AND 
						l.year_id = ".escape($this->OldYearId)." AND
						(
							a.ac_code LIKE '04%' OR 
							a.ac_code LIKE '05%'
						) 
		");

		$this->oLedger->saveData($this->LedgerId, CommonCOA::PROFIT_LOSS, DBQM::result($Select, 0), 1,'Profit / Loss From '.$this->RowOldYear['year_name']);
	}
}