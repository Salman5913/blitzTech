<?php

class CRMInquiry extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherReport
{
    const IND_DATA = 'Meeting Info';
    const IND_ADDITIONAL_INFO = 'Additional Info';

    const BIN_STAGES = [
        6 // bin
    ];
    const STAGE_NEW = 1;

    const SOURCE_SOCIAL_MEDIA = '3';

    public function __construct()
    {
        parent::__construct("crm_inquiry", [
            self::IND_DATA => 'crm_inquiry_data',
            self::IND_ADDITIONAL_INFO => 'crm_inquiry_additional_info'
        ], 'crm_inquiry_id', VoucherType::CRMI, false);

        $this->setProject(false);
    }

    protected function saveTransactionEntries()
    {
        $RowDetails = $this->getDetails();

        $NickName = strtolower($RowDetails['nickname']);
        $NickName = preg_replace('/[^a-z0-9\s]/', '', $NickName);
        $NickName = preg_replace('/\s+/', '-', $NickName);

        DBQM::query("UPDATE crm_inquiry SET nickname = ".escape($NickName)." WHERE id = ".escape($this->getVoucherId()));
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oFormUtils = new BasicVoucherFormUtils();

        $oFormUtils->setRequiredFields(["source_id", "stage_id"]);
        $oFormUtils->setHiddenFields(['import_reference_id', 'inquiry_status', 'sort_number']);
        $oFormUtils->setColumnWidth(['comment' => '50%']);

        $oFormUtils->setAutocomplete('reference_account_id', Autocomplete::TYPE_ACCOUNT_BY_COA, [getConstantCategory()->getCustomer()]);
        $oFormUtils->setAutocomplete('source_id', Autocomplete::TYPE_CRM_SOURCE);
        $oFormUtils->setAutocomplete('stage_id', Autocomplete::TYPE_CRM_STAGE);
        $oFormUtils->setAutocomplete('crm_sales_pitch_id', Autocomplete::TYPE_CRM_SALES_PITCH);
        $oFormUtils->setAutocomplete('crm_inquiry_type_id', Autocomplete::TYPE_CRM_INQUIRY_TYPE);
        $oFormUtils->setAutocomplete('contact_card_id', Autocomplete::TYPE_CONTACT_CARD);

        $oFormUtils->setColumnDisplayType('tags', new FormFieldTypeMultiSelectFromAutocomplete(Autocomplete::TYPE_CRM_TAGS));

        $oFormUtils->addDefaultGroup('col-md-6', 'col-md-6');
        $oFormUtils->addElementGroup('Contact Info', 'col-md-6', 'col-md-6', [
            'contact_card_id',
            'reference_account_id',
            'source_id'
        ]);

        return $oFormUtils;
    }

    function getVoucherTotal(): float
    {
        return 0;
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        $oFilter =  new BasicVoucherReportFilters();
        $oFilter->setFilter('tags', 'Tag', '', '', Autocomplete::TYPE_CRM_TAGS);

        return $oFilter;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = UtilsQueryMaker::like('tags', $_GET['tags'].";", ";");

        return DBQM::query("
            SELECT 
                ci.*, 
                t.type_name as inquiry_type,
                cis.source_name as source,
                cc.name,
                cc.mobile1,
                cc.email1,
                di.voucher_date, 
                di.is_deleted, 
                di.voucher_type_id, 
                di.voucher_number, 
                di.voucher_id,
                di.is_draft
            FROM 
                crm_inquiry ci,
                crm_inquiry_type t,
                crm_source cis,
                distinct_id di,
                dir_contact_card cc
            WHERE
                ci.crm_inquiry_type_id = t.id AND
                cc.id = ci.contact_card_id AND
                ci.source_id = cis.id AND
                ci.distinct_id = di.id 
                {$Where} 
            ORDER BY 
                ci.id DESC
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('voucher_date', 'Date', 'date');
        $oTableColumn->addColumn('name', 'Name');
        $oTableColumn->addColumn('mobile1', 'Mobile');
        $oTableColumn->addColumn('interested_in', 'Interested In');
        $oTableColumn->addColumn('inquiry_status', 'Status');

        return $oTableColumn;
    }

    function getAllOpenInquiries($Type='', $isEscalated='N')
    {
        $Where = '';
        $Where .= UtilsQueryMaker::equal('is_escalated', $isEscalated);

        if (SecurityShell::isAccess('CRM', 'Own Leads Only'))
        {
            $Where .= UtilsQueryMaker::inOrEqual("created_by", [SecurityShell::getLoggedInUserId(), SecurityShell::SUPER_ADMIN_ID]);
        }

        if (!empty($Type))
            $Where .= " AND crm_inquiry_type_id = ".escape($Type);

        return DBQM::query("
            SELECT 
                cr.*,
                cis.source_name as source,
                cid.comment,
                IFNULL(cid.date_comment, di.voucher_date) AS date_comment,
                cid.next_meeting_date,
                cid.next_meeting_time,
                di.voucher_date,
                di.created_by,
                di.voucher_type_id,
                di.is_deleted,
                di.voucher_number,
                di.voucher_id,
                inquiry_status,
                get_crm_inquiry_location(cr.id) as city,
                t.type_name as inquiry_type,
                cc.name,
                cc.email1,
                cc.mobile1
            FROM 
                dir_contact_card cc,
                crm_inquiry_type t,
                distinct_id di,
                crm_source cis,
                crm_inquiry cr
                LEFT JOIN crm_inquiry_data cid on cr.id = cid.crm_inquiry_id AND cid.id = get_crm_last_comment_id(cr.id)
            WHERE 
                cc.id = cr.contact_card_id AND
                cr.distinct_id = di.id AND 
                cis.id = cr.source_id AND
                t.id = cr.crm_inquiry_type_id AND
                inquiry_status IN ('Open', 'Imported - New') AND
                is_deleted <> 'Y'
                {$Where}
            ORDER BY
                IF(next_meeting_date = DATE(NOW()), 0, 1), IF (tags LIKE '%high potential;%', 0, 1), next_meeting_date, next_meeting_time
        ");
    }

    static function getTodayMeetings()
    {
        $Select = DBQM::query("
            SELECT 
                cr.*,
                t.type_name as inquiry_type,
                cis.source_name as source,
                cid.comment,
                cid.date_comment,
                cid.next_meeting_date,
                cid.next_meeting_time,
                di.voucher_date,
                cc.name,
                cc.email1,
                cc.mobile1
            FROM 
                dir_contact_card cc,
                distinct_id di,
                crm_source cis,
                crm_inquiry_type t,
                crm_inquiry cr
                LEFT JOIN crm_inquiry_data cid on cr.id = cid.crm_inquiry_id AND cid.id = get_crm_last_comment_id(cr.id)
            WHERE 
                cc.id = cr.contact_card_id AND
                cr.distinct_id = di.id AND 
                cis.id = cr.source_id AND
                t.id = cr.crm_inquiry_type_id AND
                inquiry_status = 'Open' AND
                next_meeting_date = ".escape(date("Y-m-d"))."
        ");

        if (!DatabaseHelper::isRecordSetEmpty($Select))
        {
            $Notes = DatabaseHelper::getRecordCount($Select)." Meetings Today\n";
            while ($Row = DBQM::fetch_assoc($Select))
            {
                $Notes .= "\n".DateDecode($Row['next_meeting_date']." ".$Row['next_meeting_time'], "h:ia")." - ".$Row['client_name']." - ".$Row['comment'];
            }

            return $Notes;
        }
        return '';
    }

    function getFormattedTagsForDisplay($Tags)
    {
        if (!isset($_SESSION['__crm_tag_class']))
        {
            $SelectTags = DBQM::query("SELECT * FROM crm_tags");
            $TagClass = [];
            while ($Row = DBQM::fetch_assoc($SelectTags))
                $TagClass[$Row['tag_name']] = $Row['css_color_class'];

            $_SESSION['__crm_tag_class'] = $TagClass;
        }

        $Output = '';
        $Tags = explode(";", $Tags);
        foreach ($Tags as $Tag)
        {
            $Output .= "<span class='crm-badge badge ".$_SESSION['__crm_tag_class'][$Tag]."'>".$Tag."</span> ";
        }
        return $Output;
    }

    public function __destruct()
    {
        unset($_SESSION['__crm_tag_class']);
    }

    function getTodayPendingCount($Type='', $Status='')
    {
        $Where = '';

        if (SecurityShell::isAccess('CRM', 'Own Leads Only'))
        {
            $Where .= UtilsQueryMaker::inOrEqual("created_by", [SecurityShell::getLoggedInUserId(), SecurityShell::SUPER_ADMIN_ID]);
        }

        if (!empty($Type))
            $Where .= " AND crm_inquiry_type_id = ".escape($Type);

        if (!empty($Status))
            $Where .= " AND inquiry_status IN (".escape($Status).") ";
        else
            $Where .= " AND inquiry_status IN ('Open') AND next_meeting_date <= DATE(NOW()) ";


        $Select = DBQM::query("
            SELECT 
                cr.id
            FROM 
                distinct_id di,
                crm_inquiry cr
                LEFT JOIN crm_inquiry_data cid on cr.id = cid.crm_inquiry_id AND cid.id = get_crm_last_comment_id(cr.id)
            WHERE 
                cr.distinct_id = di.id AND 
                is_deleted <> 'Y' 
                {$Where}
        ");

        return DatabaseHelper::getRecordCount($Select);
    }

    function getRatioStats()
    {
        $Select = DBQM::query("
            SELECT 
                SUM(spam_count) AS spam_count,
                SUM(converted_count) AS converted_count,
                SUM(all_count) AS all_count
            FROM
            (
                SELECT count(crmi.id) AS spam_count, 0 AS all_count, 0 AS converted_count FROM crm_inquiry crmi, distinct_id did WHERE crmi.distinct_id = did.id AND crmi.inquiry_status IN ('Spam', 'Not Interested') AND did.created_date_time >= NOW() - INTERVAL 3 MONTH
                UNION
                SELECT 0 AS spam_count, 0 AS all_count, count(crmi.id) AS converted_count FROM crm_inquiry crmi, distinct_id did WHERE crmi.distinct_id = did.id AND crmi.inquiry_status IN ('Converted') AND did.created_date_time >= NOW() - INTERVAL 3 MONTH
                UNION
                SELECT 0 AS spam_count, count(crmi.id) AS all_count, 0 AS converted_count FROM crm_inquiry crmi, distinct_id did WHERE crmi.distinct_id = did.id AND did.created_date_time >= NOW() - INTERVAL 3 MONTH
            ) AS tmp
        ");

        return DBQM::fetch_assoc($Select);
    }

    function getCityById($InquiryId)
    {
        $Select = DBQM::query("SELECT get_crm_inquiry_location(".escape($InquiryId).")");
        return DBQM::result($Select, 0);
    }

    function getPanelHTML($RowInquiry)
    {
        $NextMeeting = $RowInquiry['next_meeting_date']." ".$RowInquiry['next_meeting_time'];
        $isToday = false;

        $Background = '';
        if (strtotime($NextMeeting) < strtotime(date("Y-m-d"))) // meeting date passed
            $Background = 'bg-soft-warning';

        elseif ($RowInquiry['inquiry_status'] == 'Imported - New')
            $Background = 'bg-soft-info';

        elseif (strtotime($NextMeeting) >= strtotime(date("Y-m-d")) and strtotime($NextMeeting) <= strtotime(date("Y-m-d 23:59:59"))) // meeting today
        {
            $isToday = true;
            $Background = 'bg-soft-danger';
        }
        ?>
        <div class="row pb-2 pt-2 border-bottom">
            <div class="col-12 <?=$Background?> rounded-sm p-2">
                <div class="row">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-8 text-danger font-weight-bold"><?=$RowInquiry['name']?></div>
                            <div class="col-3 text-right"><?=DateDecode($RowInquiry['date_comment'], 'M d')?></div>
                            <div class="col-1"><img alt="flag" src="../assets/images/flag/<?=str_starts_with($RowInquiry['mobile1'], "+92") ? 'pk' : 'ae'?>.png" class="img-fluid"></div>
                        </div>
                        <div class="row">
                            <div class="col-12"><?=$RowInquiry['comment']?></div>
                        </div>
                        <div class="row text-info pt-2">
                            <div class="col-1"><? if (!empty($RowInquiry['crm_sales_pitch_id'])) { ?><a class="p-1 bg-purple text-white rounded-sm" target="_blank" href="../../crm_sales_pitch/print_voucher.php?id=<?=$RowInquiry['id']?>"><i class="mdi mdi-file-pdf"></i></a><? } ?></div>
                            <div class="col-5"><a class="text-info" href="tel:<?=$RowInquiry['mobile1']?>"><?=$RowInquiry['mobile1']?></a>  <a target="_blank" href="<?=Utils::getWhatsAppLink($RowInquiry['mobile1'])?>"><i class="mdi mdi-whatsapp"></i></a></div>
                            <div class="col-5 text-right <?=$isToday ? 'font-weight-bold' : '' ?>"><?=DateDecode($NextMeeting, $isToday ? "h:i a" : "M d - h:i a")?></div>
                            <div class="col-1"><a class="p-1 bg-success text-white rounded-sm" href="<?=VoucherType::getLink(VoucherType::CRMI, $RowInquiry['id'])?>"><i class="mdi mdi-phone-in-talk"></i></a></div>
                        </div>
                        <? if (!empty($RowInquiry['tags'])) { ?>
                            <div class="row crm-badges">
                                <div class="col-12">
                                    <div class="badge-wrapper bg-white pt-1 pl-1 pr-1 mt-2"><?=$this->getFormattedTagsForDisplay($RowInquiry['tags'])?></div>
                                </div>
                            </div>
                        <? } ?>
                        <? if (!empty($RowInquiry['city'])) { ?>
                            <div class="row text-muted pt-1 font-size-12">
                                <div class="col-6">
                                    <?=$RowInquiry['city']?>
                                </div>
                                <div class="col-6 text-right">

                                </div>
                            </div>
                        <? } ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    static function updateStage($LeadId, $NewStageId)
    {
        DBQM::query("UPDATE crm_inquiry SET stage_id = ".escape($NewStageId)." WHERE id = ".escape($LeadId));
    }

    static function updateSortNumber(array|null $LeadIds, $NewStageId)
    {
        if (empty($LeadIds)) return;

        $CaseStatement = '';
        $Ids = [];

        foreach ($LeadIds as $SortNumber => $LeadId) {
            $CaseStatement .= "WHEN id = {$LeadId} THEN " . ($SortNumber + 1) . " ";
            $Ids[] = $LeadId;
        }

        // Convert the IDs array into a comma-separated string
        $IdList = implode(',', $Ids);

        // Construct the final SQL query
        DBQM::query("
            UPDATE crm_inquiry
            SET sort_number = CASE
                $CaseStatement
                ELSE sort_number END
            WHERE id IN ($IdList) AND stage_id = ".escape($NewStageId)."
        ");
    }
}