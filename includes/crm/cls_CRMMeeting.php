<?php

class CRMMeeting extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherPrint, DefaultVoucherReport
{
    public function __construct()
    {
        parent::__construct("crm_meeting", 'crm_meeting_history', 'meeting_id', VoucherType::CRMM, false);
    }

    protected function saveTransactionEntries()
    {
        // TODO: Implement saveTransactionEntries() method.
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $HiddenFields = ['crm_inquiry_id'];

        $oBasicVoucherFormUtils = new BasicVoucherFormUtils();
        $oBasicVoucherFormUtils->setAutocomplete('account_id', Autocomplete::TYPE_ACCOUNT);
        $oBasicVoucherFormUtils->setAutocomplete('crm_inquiry_type_id', Autocomplete::TYPE_CRM_INQUIRY_TYPE);

        $oBasicVoucherFormUtils->addDefaultGroup('col-sm-6', 'col-sm-6');

        $oBasicVoucherFormUtils->addElementGroup('Date & Time', 'col-sm-3', 'col-sm-12', ['meeting_date', 'meeting_time']);
        $oBasicVoucherFormUtils->addElementGroup('Meeting Location', 'col-sm-3', 'col-sm-12', ['location', 'location_link']);
        $oBasicVoucherFormUtils->addElementGroup('Whats the Meeting about?', 'col-sm-12', 'col-sm-12', ['description']);
        $oBasicVoucherFormUtils->addElementGroup('Post Meeting Transcript', 'col-sm-12', 'col-sm-12', ['transcript']);


        /* **************** If CRM Inquiry Id is provided, hide account id field **************** */
        if (isset($_GET['crm_inquiry_id']))
        {
            $HiddenFields[] = 'account_id';
        }
        elseif (SecurityShell::isAccess("Master Details", "Own Vouchers Only"))
            $HiddenFields[] = 'account_id';

        if ($this->isEdit())
        {
            $RowDetails = $this->getDetails();
            if (!empty($RowDetails['crm_inquiry_id']))
            {
                $HiddenFields[] = 'account_id';
            }
        }
        /* **************** If CRM Inquiry Id is provided, hide account id field **************** */

        /**
         * If account id field is not hidden, make it required
         */
        if (!in_array('account_id', $HiddenFields))
            $oBasicVoucherFormUtils->setRequiredFields(['account_id']);

        $oBasicVoucherFormUtils->setHiddenFields($HiddenFields);

        return  $oBasicVoucherFormUtils;
    }

    function getVoucherTotal(): float
    {
        return 0;
    }

    function getVoucherPrintUtilsClass(): BasicVoucherPrintUtils
    {
        return new BasicVoucherPrintUtils();
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        return new BasicVoucherReportFilters();
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = UtilsQueryMaker::ownVoucherCheck("did.created_by");

        return DBQM::query("
            SELECT 
                crmm.*,
                t.type_name as type,
                did.is_deleted,
                did.voucher_type_id,
                did.voucher_date,
                did.voucher_id,
                did.is_draft,
                IFNULL(pmc.name, IFNULL(aa.name, cc.name)) AS client_name
            FROM
                distinct_id did,
                crm_inquiry_type t,
                prj_project pmc,
                crm_meeting crmm
                LEFT JOIN acc_accounts aa on crmm.account_id = aa.id
                LEFT JOIN crm_inquiry ci on crmm.crm_inquiry_id = ci.id
                LefT JOIN dir_contact_card cc ON cc.id = ci.contact_card_id
            WHERE
                did.id = crmm.distinct_id AND
                t.id = crmm.crm_inquiry_type_id AND
                did.project_id = pmc.id
                {$Where}
            ORDER BY
                crmm.meeting_date DESC, crmm.meeting_time DESC
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oBasicVoucherReportTableColumn = new BasicVoucherReportTableColumn();
        $oBasicVoucherReportTableColumn->addColumn('meeting_date', 'Date', 'date');
        $oBasicVoucherReportTableColumn->addColumn('meeting_time', 'Time');
        $oBasicVoucherReportTableColumn->addColumn('client_name', 'Client');

        return $oBasicVoucherReportTableColumn;
    }

    function getRecentMeetings($isCompleted='')
    {
        $Where = '';
        $Where .= UtilsQueryMaker::equal('crmm.is_complete', $isCompleted);
        $Where .= UtilsQueryMaker::ownVoucherCheck("did.created_by");

        if ($isCompleted == 'N')
            $Where .= " AND crmm.meeting_date <= ".escape(date("Y-m-d", strtotime("+7 days")));
        elseif ($isCompleted == 'Y')
            $Where .= " AND crmm.meeting_date >= ".escape(date("Y-m-d", strtotime("-30 days")));

        return DBQM::query("
            SELECT
                crmm.*,
                t.type_name as type,
                IFNULL(cc.mobile1, bd.mob) AS mobile,
                IFNULL(pmc.name, IFNULL(aa.name, cc.name)) AS client_name,
                get_crm_inquiry_location(crmm.crm_inquiry_id) AS city
            FROM
                distinct_id did,
                crm_inquiry_type t,
                crm_meeting crmm
                LEFT JOIN acc_accounts aa on crmm.account_id = aa.id
                LEFT JOIN acc_bio_data bd ON aa.id = bd.account_id
                LEFT JOIN crm_inquiry ci on crmm.crm_inquiry_id = ci.id
                LEFT JOIN dir_contact_card cc ON cc.id = ci.contact_card_id
                LEFT JOIN prj_project pmc ON did.project_id = pmc.id
            WHERE
                did.id = crmm.distinct_id AND
                t.id = crmm.crm_inquiry_type_id AND
                did.is_deleted <> 'Y'
                {$Where}
            ORDER BY 
                IF(crmm.meeting_date = DATE(NOW()), 0, 1), crmm.meeting_date DESC, crmm.meeting_time;
        ");
    }

    function getTodayMeetingCount()
    {
        $Where = UtilsQueryMaker::ownVoucherCheck("did.created_by");

        $Select = DBQM::query("
            SELECT
                crmm.*,
                t.type_name as type
            FROM
                distinct_id did,
                crm_inquiry_type t,
                crm_meeting crmm
            WHERE
                did.id = crmm.distinct_id AND 
                t.id = crmm.crm_inquiry_type_id AND
                did.is_deleted <> 'Y' AND
                crmm.meeting_date <= DATE(NOW()) AND
                crmm.is_complete <> 'Y'
                {$Where}
        ");

        return DatabaseHelper::getRecordCount($Select);
    }
}