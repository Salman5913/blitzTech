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

class GeneralJournal extends AccountingReport
{
    function __construct() {
        parent::__construct();
    }

    function getReport()
    {
        $Query = "
                    SELECT
                        l.*,
                        vt.name AS default_voucher_code,
                        vt.name AS voucher_code,
                        vt.abbr AS voucher_name
                    FROM
                        ledger l,
                        ledger_data ld,
                        vt_voucher_type vt
                    WHERE
                        l.id = ld.ledger_id AND
                        l.year_id = ".escape(Year::getYearId())." AND
                        l.voucher_type_id = vt.id
        ";

        $Query .= UtilsQueryMaker::date("date_time", $this->DateFrom, $this->DateTill);

        $Query .= " GROUP BY l.id ORDER BY date_time ASC";

        return $Query;
    }

    function getLedgerDetailsByLedgerId($LedgerId)
    {
        return DBQM::query("
                              SELECT
                                  ld.*,
                                  a.ac_name AS account_name
                              FROM
                                  vw_ac_accounts a,
                                  ledger_data ld
                              WHERE
                                  a.id = ld.account_id AND
                                  ld.ledger_id = ".escape($LedgerId, 'int')."
        ");
    }
}