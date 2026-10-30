<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 022, 1/22/2015
 * Time: 11:09 AM
 */

class Project extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport
{
    private $VoucherTotal = 0;

    function __construct()
    {
        parent::__construct(
            "prj_project",
            "",
            "",
            VoucherType::PM
        );
        $this->setProject(false);
    }

    protected function saveTransactionEntries()
    {

    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new BasicVoucherFormUtils();
        //$oFormUtils->setUniqueColumns("name", 'text');
        $oFormUtils->setAutocomplete("account_id", Autocomplete::TYPE_ACCOUNT);

        return $oFormUtils;
    }

    function getVoucherTotal(): float
    {
        return $this->VoucherTotal;
    }

    function getVoucherPrintUtilsClass(): BasicVoucherPrintUtils
    {
        $oPrintUtils = new BasicVoucherPrintUtils();
        $oPrintUtils->setIsSignatureRequire(true);

        return $oPrintUtils;
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        $oFilters = new BasicVoucherReportFilters();
        $oFilters->setFilter('voucher_date', 'Date', 'date');
        $oFilters->setFilter('account_id', 'Account', '', '', Autocomplete::TYPE_ACCOUNT);
        $oFilters->setFilter('name', 'Project Name', '');
        $oFilters->setFilter('site', 'Site', '');
        $oFilters->setFilter('attention', 'Attention', '');
        $oFilters->setFilter('description', 'Description', 'text');

        return $oFilters;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = '';

        if (empty($_GET['voucher_number']))
        {
            $Where .= UtilsQueryMaker::date("di.voucher_date", $_GET['date_from'], $_GET['date_till']);
            $Where .= CostCenter::getFilterSql("di.cost_center_id", true);
            $Where .= UtilsQueryMaker::equal("account_id", $_GET['account_id']);
            $Where .= UtilsQueryMaker::like("name", $_GET['name']);
            $Where .= UtilsQueryMaker::like("site", $_GET['site']);
            $Where .= UtilsQueryMaker::like("attention", $_GET['attention']);
            $Where .= UtilsQueryMaker::like("description", $_GET['description']);
        } else {
            $Where .= UtilsQueryMaker::equal("di.voucher_number", $_GET['voucher_number']);
        }


        return DBQM::query("
            SELECT
                di.voucher_type_id,
                di.voucher_number,
                di.voucher_date,
                di.is_deleted,
                di.voucher_total,
                di.is_draft,
                a.ac_name,
                pr.*
            FROM
                distinct_id di, 
                prj_project pr,
                vw_ac_accounts a
            WHERE
                di.id = pr.distinct_id AND
                a.id = pr.account_id AND
                di.year_id = ".escape(Year::getYearId())."
                {$Where}
            GROUP BY 
                pr.id 
            ORDER BY 
                di.voucher_date, pr.id
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('voucher_date', 'Date', BasicVoucherReportTableColumn::TYPE_DATE);
        $oTableColumn->addColumn('voucher_number', 'Project #', 'int');
        $oTableColumn->addColumn('ac_name', 'Account Name');
        $oTableColumn->addColumn('name', 'Project Name');
        $oTableColumn->addColumn('site', 'Site');
        $oTableColumn->addColumn('attention', 'Attention');
        $oTableColumn->addColumn('description', 'Description');

        return $oTableColumn;
    }

    function getAccountNameByProjectId($ProjectId)
    {
        $Select = DBQM::query("SELECT account_name FROM vw_ac_project WHERE id = ".escape($ProjectId));
        return DBQM::result($Select, 0);
    }
}