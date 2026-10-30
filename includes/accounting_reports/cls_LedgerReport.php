<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 007, 1/7/2015
 * Time: 12:01 PM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("../../connection/localhost.php");

class LedgerReport extends AccountingReport
{
    function __construct() {
        parent::__construct();
    }

    public static function getUnbalancedVouchers()
    {
        return DBQM::query("
                                SELECT
                                    vt.abbr,
                                    l.voucher_number,
                                    ROUND(SUM((debit - credit) * ld.currency_rate)) AS sum_amt
                                FROM
                                    ledger l,
                                    ledger_data ld,
                                    vt_voucher_type vt
                                WHERE
                                    l.id = ld.ledger_id AND
                                    l.year_id = " . escape(Year::getYearId()) . " AND
                                    vt.id = l.voucher_type_id AND
                                    l.voucher_type_id NOT IN ('7', '27')
                                GROUP BY
                                    l.id
                                HAVING
                                    sum_amt <> 0
                                ORDER BY 
                                    l.date_time LIMIT 7
        ");
    }

    /**
     * Returns total debit, total credit
     * @return array 0: debit, 1: credit
     */
    public static function getLedgerStatus()
    {
        $Select = DBQM::query("SELECT SUM(ld.debit * ld.currency_rate), SUM(ld.credit * ld.currency_rate) FROM ledger_data ld, ledger l WHERE l.id = ld.ledger_id AND l.year_id = " . escape(Year::getYearId()));
        return array(DBQM::result($Select, 0, 0), DBQM::result($Select, 0, 1));
    }

    /**
     * Returns account balance (debit - credit) IN DEFAULT CURRENCY
     * if voucher type and voucher number is given, then balance will of before the date registered in ledger for this voucher
     *
     * @param $AccountId
     * @param string $Date Date Only
     * @param string $VoucherTypeId
     * @param string $VoucherNumber
     * @return int
     */
    public static function getBalance($AccountId, $Date, $VoucherTypeId = '', $VoucherNumber = '')
    {
        $DateTime = '';
        if (!empty($VoucherTypeId) and !empty($VoucherNumber)) {
            $DateTime = Ledger::getDateTimeByVoucherNumber($VoucherTypeId, $VoucherNumber);
        }

        if (empty($DateTime))
            $DateTime = $Date . " 23:59:59";

        $Select = DBQM::query("
                              SELECT
                                  SUM(d.debit - d.credit)
                              FROM
                                  ledger l,
                                  ledger_data d
                              WHERE
                                  l.id = d.ledger_id AND
                                  l.year_id = " . escape(Year::getYearId(), 'int') . " AND
                                  l.date_time < " . escape($DateTime) . " AND
                                  d.account_id = " . escape($AccountId) . "
        ");

        return DBQM::result($Select, 0);
    }

    public static function getVoucherAmount($VoucherTypeId, $VoucherNumber)
    {
        $Select = DBQM::query("SELECT SUM(debit) FROM ledger l, ledger_data ld WHERE l.id = ld.ledger_id AND l.voucher_number = " . escape($VoucherNumber) . " AND l.voucher_type_id = " . escape($VoucherTypeId) . " AND l.year_id = " . escape(Year::getYearId()));

        return DBQM::result($Select, 0);
    }

    function reconcileLedgerData(array $LedgerDataId)
    {
        foreach ($LedgerDataId as $Id)
            DBQM::query("INSERT INTO ledger_data_reconc VALUES (0, ".escape($Id, 'int').", ".escape(SecurityShell::getLoggedInUserId(), 'int').", ".escape(DatabaseHelper::NOW()).")");
    }

    function reconcileCancelLedgerData(array $LedgerDataId)
    {
        DBQM::query("DELETE FROM ledger_data_reconc WHERE ledger_data_id IN (".implode(",", array_map("escape", $LedgerDataId)).")");
    }

    /**
     * @param $AccountId
     * @param string $Date format Y-m-d H:i:s
     * @param string $DateType full => date is passed with time, no need to add time <br> to => add 23:59:59 to date <br> from => add 00:00:00 to date
     * @param bool $FilterCostCenter true => only vouchers of the cost center picked in the report filter (reports)
     * @return bool|mixed returns balance in customer currency
     */
    public static function getAccountBalance($AccountId, $Date, $DateType = 'to', $FilterCostCenter = false)
    {
        if ($DateType == 'to')
            $Date .= " 23:59:59";
        elseif ($DateType == 'from')
            $Date .= " 00:00:00";

        $Select = DBQM::query("
                              SELECT
                                  SUM(d.debit - d.credit)
                              FROM
                                  ledger l,
                                  ledger_data d
                              WHERE
                                  l.id = d.ledger_id AND
                                  l.year_id = " . escape(Year::getYearId()) . " AND
                                  l.date_time < " . escape($Date) . " AND
                                  d.account_id = " . escape($AccountId) . "
                                  " . ($FilterCostCenter ? self::costCenterSql('l') : '') . "
        ");

        return DBQM::result($Select, 0);
    }

    function getReport($AccountId, array $COAId, $ShowReconciled=false)
    {
        $Where = $From = $Select = '';

        if (!empty($COAId))
        {
            $ChildIds = [];

            foreach ($COAId as $Id)
            {
                $SelectChildIds = DBQM::query("SELECT get_children_ids_full(".escape($Id, 'int').")");
                $ChildIds = array_merge($ChildIds, explode(",", DBQM::result($SelectChildIds, 0)));
            }

            $Where .= UtilsQueryMaker::inOrEqual("a.coa_id", $ChildIds);
        }

        $Where .= UtilsQueryMaker::date("l.date_time", $this->DateFrom, $this->DateTill);
        $Where .= UtilsQueryMaker::equal("a.id", $AccountId);
        $Where .= self::costCenterSql('l');

        if ($ShowReconciled)
        {
            $From = 'LEFT JOIN
                            ledger_data_reconc ldr ON 
                              ld.id = ldr.ledger_data_id';

            $Select = ', ldr.id AS reconciled_id';
        }

        $Query = "
                    SELECT
                        ld.id AS ledger_data_id,
                        ld.debit,
                        ld.credit,
                        ld.currency_rate,
                        (
                        	CASE
                        		WHEN ld.stock_id IS NULL THEN ld.description
                        		ELSE CONCAT(ld.description, ' - ', s.ac_stock, ' x ', CAST(ld.stock_qty AS CHAR), ' @ ', CAST(FORMAT(ld.stock_price, 0) AS CHAR))
                            END
                        ) AS description,
                        l.date_time,
                        l.voucher_number,
                        did.voucher_id,
                        l.voucher_type_id,
                        vt.name AS voucher_code,
                        vt.abbr AS voucher_name,
                        ld.ledger_id,
                        DATE(l.date_time) AS date_only,
                        l.distinct_id,
                        ca.name AS counter_name,
                        ca.id AS counter_account_id
                        {$Select}
                    FROM
                        vt_voucher_type vt,
                        acc_accounts a,
                        ledger  l,
                        distinct_id did,
                        ledger_data ld
                        LEFT JOIN
                           acc_accounts AS ca
                           ON
                              ca.id = ld.counter_account_id
                      	LEFT JOIN 
                      		vw_ac_stock s 
                      		ON 
                      			ld.stock_id = s.id AND ld.attribute_id <=> s.attribute_id
                        {$From}
                    WHERE
                        l.id = ld.ledger_id AND
                        ld.account_id = a.id AND
                        l.distinct_id = did.id AND
                        l.voucher_type_id = vt.id AND 
                        l.year_id = ".escape(Year::getYearId())."
                        {$Where}
                    GROUP BY
                        ld.id
                    ORDER BY
                        date_only,
                        distinct_id
        ";

        return DBQM::query($Query);
    }

    /**
     * For Report Voucher Details View (in Popup)
     * @param $LedgerId
     * @return mysqli_result
     */
    function getVoucherItemsDetail($LedgerId)
    {
        return DBQM::query("SELECT ld.*, a.name FROM ledger_data ld, acc_accounts a WHERE ld.account_id = a.id AND ld.ledger_id = ".escape($LedgerId, 'int'));
    }

    function getPostChequesAmount($AccountId)
    {
	    $Where = '';

	    $Where .= UtilsQueryMaker::date("l.date_time", date('Y-m-d', strtotime(DateDecode($this->DateTill)." +1 day")), '');
	    $Where .= UtilsQueryMaker::equal("a.id", $AccountId);
	    $Where .= self::costCenterSql('l');

    	$Select = DBQM::query("
										SELECT 
											SUM(debit - credit) 
										FROM 
											ledger l, 
											ledger_data ld,
					                        acc_accounts a
										WHERE 
											l.id = ld.ledger_id AND 
                       						ld.account_id = a.id AND 
                        					l.year_id = ".escape(Year::getYearId())." AND
											l.voucher_type_id IN (
												".escape(VoucherType::CQF).", 
												".escape(VoucherType::CQR).", 
												".escape(VoucherType::CQV)."
											)
											{$Where}
		");

    	return DBQM::result($Select, 0);
    }

	function getLedgerBalancesByMonths($AccountName)
	{
		$Months = Year::getMonthsOfCurrentYear();

		$Query = [];
		foreach ($Months AS $Key => $Dates)
		{
			$Query[] = "
				SELECT 
					SUM(debit - credit) AS sum_amount,
					'".date("M y", strtotime($Dates['start']))."' AS month_name
				FROM 
					ledger l,
					ledger_data ld,
					acc_accounts a
				WHERE
					l.id = ld.ledger_id AND 
					ld.account_id = a.id AND 
					l.year_id = ".escape(Year::getYearId())." AND
					l.date_time <= ".escape($Months[$Key]['end'])." 
					".UtilsQueryMaker::equal("a.name", $AccountName)."
					".self::costCenterSql('l')."
			";
		}

		$Query = implode(" UNION ", $Query);

		$Label = [];
		$Balance = [];
		$Select = DBQM::query($Query);

		while($Row = DBQM::fetch_assoc($Select))
		{
			$Label[] = $Row['month_name'];
			$Balance[] = intval($Row['sum_amount']);
		}

		return [$Label, $Balance];
	}

    function getAllAccountsByCOAId($COAId, $AccountId='')
    {
        if (!empty($COAId))
        {
            $SelectSubCategories = DBQM::query("SELECT get_children_ids_full(".escape($COAId).")");
            $SubCategories = DBQM::result($SelectSubCategories, 0);
        }

        $Where = '';
        $Where .= !empty($COAId) ? " AND coa_id IN (".implode(", ", array_map("escape", explode(",", $SubCategories))).")" : '';
        $Where .= !empty($AccountId) ? " AND id = ".escape($AccountId) : '';

        return DBQM::query("SELECT * FROM acc_accounts WHERE id <> '' {$Where} ");
    }

    static function getLedgerLink($AccountId)
    {
        return ProjectHelper::SiteURL().'accounting_reports/ledger.php?date_from='.date('m/d/Y', strtotime("-3 month")).'&date_till='.date("m/d/Y").'&account_id='.$AccountId.self::costCenterUrlParam().'&search=';
    }

    function getLedgerDetailsByDistinctId($DistinctId)
    {
        return DBQM::query("
                              SELECT
                                  ld.*,
                                  a.ac_name AS account_name
                              FROM
                                  vw_ac_accounts a,
                                  ledger_data ld,
                                  ledger l
                              WHERE
                                  a.id = ld.account_id AND
                                  l.id = ld.ledger_id AND
                                  l.distinct_id = ".escape($DistinctId, 'int')."
        ");
    }

    function getOtherUsersCreateUpdateVouchers($DateFrom='', $DateTill='')
    {
        $WereDate = UtilsQueryMaker::date("date_time", $DateFrom, $DateTill);

        return DBQM::query("
                        SELECT
                            *
                        FROM 
                        (
                            SELECT
                                CONCAT(vt.name,' - ', voucher_number) as voucher_prefix,
                                d.voucher_id,
                                d.voucher_type_id,
                                d.voucher_total,
                                vt.abbr as voucher_name,
                                d.id,
                                d.is_deleted,
                                shl.username as user,
                                d.created_date_time as date_time,
                                'Created' as action
                            FROM
                                distinct_id d,
                                vt_voucher_type vt,
                                sh_login shl
                            WHERE
                                d.voucher_type_id = vt.id AND 
                                d.created_by = shl.id AND
                                d.created_by <> 1 
                            GROUP BY
                                d.id
                            HAVING
                                d.id <> 0
                                {$WereDate}
                            
                            UNION
                            
                            SELECT
                                CONCAT(vt.name,' - ', voucher_number) as voucher_prefix,
                                d.voucher_id,
                                d.voucher_type_id,
                                d.voucher_total,
                                vt.abbr as voucher_name,
                                d.id,
                                d.is_deleted,
                                shl.username as user,
                                updated_date_time as date_time,
                                'Updated' as action
                            FROM
                                distinct_id d,
                                vt_voucher_type vt,
                                sh_login shl
                            WHERE
                                d.voucher_type_id = vt.id AND 
                                d.updated_by = shl.id AND 
                                d.updated_by <> 1
                            GROUP BY
                                d.id
                            HAVING
                                d.id <> 0
                                {$WereDate}
                            
                            UNION
                            
                            SELECT
                                CONCAT(vt.name,' - ', voucher_number) as voucher_prefix,
                                d.voucher_id,
                                d.voucher_type_id,
                                d.voucher_total,
                                vt.abbr as voucher_name,
                                d.id,
                                d.is_deleted,
                                shl.username as user,
                                deleted_date_time as date_time,
                                'Deleted' as action
                            FROM
                                distinct_id d,
                                vt_voucher_type vt,
                                sh_login shl
                            WHERE
                                d.voucher_type_id = vt.id AND 
                                d.deleted_by = shl.id AND 
                                d.deleted_by <> 1
                            GROUP BY
                                d.id
                            HAVING
                                d.id <> 0
                                {$WereDate}
                            
                        ) as tmp
                        WHERE
                            voucher_type_id NOT IN (".escape(VoucherType::QTV).", ".escape(VoucherType::PM).")
                        GROUP BY 
                            tmp.id
                        ORDER BY
                            tmp.date_time DESC 
        ");
    }
}