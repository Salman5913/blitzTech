<?php
/**
 * Created by PhpStorm.
 * User: musta
 * Date: 0009, 1, 9, 2020
 * Time: 5:53 PM
 */

class Search
{
	private $SearchTerm;

    private $SearchTables = [];

    const ADDITIONAL_LINKS = [
        'Income Statement' => 'accounting_reports/income_statement.php',
        'Trial Balance' => 'accounting_reports/trial_balance.php',
        'Balance Sheet' => 'accounting_reports/balance_sheet.php',
        'Customer Aging' => 'accounting_reports/aging.php?type=customer',
        'Supplier Aging' => 'accounting_reports/aging.php?type=supplier'
    ];

	function __construct($SearchTerm)
	{
		$this->SearchTerm = $SearchTerm;

        if (SecurityShell::isAccess('Accounting Report', 'Ledger Report'))
        {
            $this->SearchTables[] = [
                'table' => 'acc_chart_of_account',
                'column' => 'name',
                'type' => 'coa',
                'css_class' => 'text-success'
            ];
            $this->SearchTables[] = [
                'table' => 'vw_ac_accounts',
                'column' => 'ac_name',
                'type' => 'account',
                'css_class' => 'text-info'
            ];
        }
        $this->SearchTables[] = [
            'table' => 'vt_voucher_type',
            'column' => 'abbr',
            'type' => 'voucher_type'
        ];
        $this->SearchTables[] = [
            'table' => 'vt_voucher_type',
            'column' => 'name',
            'type' => 'voucher_type'
        ];
	}

	function getResults()
	{
        $Query = [];

		foreach ($this->SearchTables as $Tables)
        {
            $IDColumn = $Tables['id'] ?? "id";

            $Query[] = "
		            SELECT
		                $IDColumn,
		                {$Tables['column']} as column_name,
		                '{$Tables['type']}' as result_type,
		                '{$Tables['css_class']}' as css_class
                    FROM
                        `{$Tables['table']}`
                    WHERE
                        `{$Tables['column']}` LIKE ".escape("%".$this->SearchTerm."%")."
            ";
        }

        $Query = implode(" UNION ", $Query);

        $Query .= " LIMIT 30";

        return DBQM::query($Query);
	}

    function getLink($Type, $Id)
    {
        switch ($Type)
        {
            case "coa":
                return "accounting_reports/category_balance.php?coa_id={$Id}&date_till=".date("M d, Y")."&search=";
            case "account":
                return "accounting_reports/ledger.php?date_from=".urlencode(date('M d, Y', strtotime("-6 months")))."&date_till=&account_id={$Id}&search=";
            case "voucher_type":
                return str_replace(ProjectHelper::SiteURL(), '', VoucherType::getLink($Id));
            case "stock":
                return "stock_report/stock_ledger.php?stock_id={$Id}&date_from=&date_till=&branch_id=&search=";
            default:
                return false;
        }
    }

    function getDescription($Type)
    {
        return match ($Type) {
            "coa" => "<i class='mdi mdi-eye'></i> View Category Balance",
            "account" => "<i class='mdi mdi-eye'></i> View Ledger",
            "voucher_type" => "<i class='mdi mdi-plus'></i> Create Voucher",
            "stock" => "<i class='mdi mdi-eye'></i> View Stock Ledger",
            "edit-account" => "<i class='mdi mdi-pencil'></i> Edit Account",
            default => false,
        };
    }

    function searchViaUtils()
    {
        /* ****************** If View / Edit Voucher Request ******************* */
        preg_match('/([A-Za-z ]+)([-])([0-9 ]+)/i', $this->SearchTerm, $VoucherDetail);
        if (count($VoucherDetail) == 4)
        {
            $VoucherTypeName = strtoupper($VoucherDetail[1]);
            $VoucherNumber = $VoucherDetail[3];

            $VoucherTypeId = VoucherType::getIdByCode($VoucherTypeName);

            $SelectVoucherId = DBQM::query("SELECT voucher_id FROM distinct_id WHERE voucher_type_id = ".escape($VoucherTypeId)." AND voucher_number = ".escape($VoucherNumber, 'int')." AND year_id = ".escape(Year::getYearId()));
            $VoucherId = DBQM::result($SelectVoucherId, 0);

            if (SecurityShell::isVoucherAccess($VoucherTypeId) and !empty($VoucherId))
            {
                if (SecurityShell::isAccess("Settings", "Vouchers Edit"))
                    return ["Edit ".$VoucherTypeName."-".$VoucherNumber, VoucherType::getLink($VoucherTypeId, $VoucherId), '', 'text-warning'];
                else
                    return ["View ".$VoucherTypeName."-".$VoucherNumber, VoucherType::getLink($VoucherTypeId, $VoucherId)."&view=true", '', 'text-info'];
            }
        }
        /* ****************** If View / Edit Voucher Request ******************* */


        /* ****************** If Last Voucher Request ******************* */
        if (strstr(strtolower($this->SearchTerm), "last"))
        {
            $VoucherTypeName = str_replace("last", "", strtolower($this->SearchTerm));

            $VoucherTypeName = strtoupper(trim($VoucherTypeName));
            $VoucherTypeId = VoucherType::getIdByCode($VoucherTypeName);

            $SelectMaxVoucherId = DBQM::query("SELECT MAX(voucher_id) FROM distinct_id WHERE voucher_type_id = ".escape($VoucherTypeId)." AND year_id = ".escape(Year::getYearId()));
            $VoucherId = DBQM::result($SelectMaxVoucherId, 0);

            $SelectVoucherNumber = DBQM::query("SELECT voucher_number FROM distinct_id WHERE voucher_type_id = ".escape($VoucherTypeId)." AND voucher_id = ".escape($VoucherId, 'int')." AND year_id = ".escape(Year::getYearId()));
            $VoucherNumber = DBQM::result($SelectVoucherNumber, 0);

            if (SecurityShell::isVoucherAccess($VoucherTypeId) and !empty($VoucherId))
            {
                if (SecurityShell::isAccess("Settings", "Vouchers Edit"))
                    return ["Edit ".$VoucherTypeName."-".$VoucherNumber, VoucherType::getLink($VoucherTypeId, $VoucherId), '', 'text-warning'];
                else
                    return ["View ".$VoucherTypeName."-".$VoucherNumber, VoucherType::getLink($VoucherTypeId, $VoucherId)."&view=true", '', 'text-info'];
            }
        }
        /* ****************** If Last Voucher Request ******************* */


        return false;
    }
}