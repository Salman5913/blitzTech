<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 022, 1/22/2015
 * Time: 11:09 AM
 */

class ProjectWisePayment extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport
{
    private $VoucherTotal = 0;

    function __construct()
    {
        parent::__construct(
            "prj_project_wise_payment",
            "",
            "",
            [VoucherType::PWPV]
        );
    }

    protected function saveTransactionEntries()
    {
        $oLedger = $this->getLedgerObject();
        $RowDetails = $this->getDetails();

        $oProject = new Project();
        $RowProjectDetails = $oProject->getDetailsByVoucherId($RowDetails['project_id']);

        $Description = "Against Project: ".$RowProjectDetails['name']."<br>".$RowDetails['description'];
        $oLedger->saveData($RowDetails['cash_bank_id'], $RowDetails['amount'], 1, $Description, $RowProjectDetails['account_id']);
        $oLedger->saveData($RowProjectDetails['account_id'], "-".$RowDetails['amount'], 1, $Description, $RowDetails['cash_bank_id']);

        $this->VoucherTotal = $RowDetails['amount'];
    }

    function getVoucherTotal(): float
    {
        return $this->VoucherTotal;
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oProject = new Project();
        if ($this->isEdit())
        {
            $ProjectId = $this->getDetails()['project_id'];
            $_GET['account_name'] = $oProject->getAccountNameByProjectId($ProjectId);
        }
        else
        {
            $_GET['account_name'] = $oProject->getAccountNameByProjectId($_GET['project_id']);
        }

        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->setReadonlyFields(['account_name']);
        $oFormUtils->setRequiredFields(['project_id']);
        $oFormUtils->setHiddenFields(['advance_account_id']);
        $oFormUtils->attachJS("../js_live/project/payment.min.js");
        $oFormUtils->setAutocomplete("payment_type", Autocomplete::TYPE_PROJECT_PAYMENT_TYPE);
        $oFormUtils->setAutocomplete("cash_bank_id", Autocomplete::TYPE_ACCOUNT_BY_COA, [getConstantCategory()->getCashInHand(), getConstantCategory()->getBank()]);

        $oFormUtils->addDefaultGroup("col-lg-8", 'col-lg-6');
        $oFormUtils->addElementGroup("Cheque Details", "col-lg-4", 'col-lg-12', ['account_name', 'cheque_number', 'cheque_date', 'cheque_bank']);

        return $oFormUtils;
    }

    function getVoucherPrintUtilsClass(): BasicVoucherPrintUtils
    {
        return new BasicVoucherPrintUtils;
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        $oFilters = new BasicVoucherReportFilters();
        $oFilters->setFilter('voucher_date', 'Date', 'date');
        $oFilters->setFilter('voucher_number', 'Voucher #', '');
        $oFilters->setFilter('account_id', 'Account', '', '', Autocomplete::TYPE_ACCOUNT);
        $oFilters->setFilter('project_id', 'Project Name', '', '', Autocomplete::TYPE_PROJECT);
        $oFilters->setFilter('cash_bank_id', 'Account', '', [getConstantCategory()->getCashInHand(), getConstantCategory()->getBank()], Autocomplete::TYPE_ACCOUNT_BY_COA);
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
            $Where .= UtilsQueryMaker::equal("cash_bank_id", $_GET['cash_bank_id']);
            $Where .= UtilsQueryMaker::equal("project_id", $_GET['project_id']);
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
                a.ac_name AS account_name,
                cb.ac_name as cash_bank_name,
                pr.ac_project as project_name,
                pwp.*
            FROM
                distinct_id di, 
                vw_ac_project pr,
                prj_project_wise_payment pwp,
                vw_ac_accounts a,
                vw_ac_accounts cb
            WHERE
                di.id = pwp.distinct_id AND
                di.project_id = pr.id AND
                a.id = pr.account_id AND
                pwp.cash_bank_id = cb.id AND
                di.year_id = ".escape(Year::getYearId())."
                {$Where}
            ORDER BY 
                di.voucher_date, pr.id
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('voucher_date', 'Date', BasicVoucherReportTableColumn::TYPE_DATE);
        $oTableColumn->addColumn('voucher_number', 'Voucher Number', 'int');
        $oTableColumn->addColumn('account_name', 'Account Name');
        $oTableColumn->addColumn('project_name', 'Project Name');
        $oTableColumn->addColumn('cash_bank_name', 'Cash/Bank Name');
        $oTableColumn->addColumn('description', 'Description');
        $oTableColumn->addColumn('voucher_total', 'Total', BasicVoucherReportTableColumn::TYPE_FLOAT);

        return $oTableColumn;
    }

    function getRemainingProjectsList($AccountId)
    {
        return DBQM::query("
                    SELECT
                        pr.ac_project,
                        pr.account_id,
                        pr.id,
                        get_project_invoiced_amount ( pr.id ) AS amount,
                        get_project_received_amount ( pr.id ) AS received_amount 
                    FROM
                        vw_ac_project pr,
                        distinct_id d,
                        qt_quote q,
                        distinct_id qd
                    WHERE
                        d.id = pr.distinct_id AND 
                        qd.id = q.distinct_id AND
                        pr.id = qd.project_id AND
                        d.is_deleted <> 'Y' AND
                        qd.is_deleted <> 'Y' AND
                        q.is_approved = 'Y' AND
                        pr.account_id = ".escape($AccountId, 'int')."
                    GROUP BY
                        pr.id
        ");
    }
}