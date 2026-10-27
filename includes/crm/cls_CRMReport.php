<?php

class CRMReport
{
    function getLeads()
    {
        $Where = '';

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
                cc.mobile1,
                cs.stage_name
            FROM 
                dir_contact_card cc,
                distinct_id di,
                crm_source cis,
                crm_stage cs,
                crm_inquiry_type t,
                crm_inquiry cr,
                crm_inquiry_data cid 
            WHERE 
                cs.id = cr.stage_id AND
                cc.id = cr.contact_card_id AND
                cr.distinct_id = di.id AND 
                cis.id = cr.source_id AND
                cr.id = cid.crm_inquiry_id AND
                cid.id = get_crm_last_comment_id(cr.id) AND
                t.id = cr.crm_inquiry_type_id AND
                is_deleted <> 'Y' AND 
                (
                    stage_id != 5
                    OR (stage_id = 5 AND DATE(di.updated_date_time) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                ) AND
                cs.id NOT IN (".implode(",", CRMInquiry::BIN_STAGES).")
                {$Where}
            ORDER BY
                sort_number, id DESC
        ");
    }

    function getPanelHTML($RowInquiry)
    {
        $NextMeeting = $RowInquiry['next_meeting_date']." ".$RowInquiry['next_meeting_time'];
        $Background = '';

        if (strtotime($RowInquiry['voucher_date']) >= strtotime(date("Y-m-d", strtotime("-7 days"))))
            $Background = 'bg-soft-info';

        elseif (strtotime($NextMeeting) >= strtotime(date("Y-m-d")) and strtotime($NextMeeting) <= strtotime(date("Y-m-d 23:59:59"))) // meeting today
        {
            $Background = 'bg-soft-danger';
        }
        ?>
        <li class="<?=$Background?>" data-id="<?=$RowInquiry['id']?>" data-crm-stage="<?=$this->cleanStageName($RowInquiry['stage_name'])?>">
            <div class="row">
                <div class="col-6 lead-id">
                    <a <?=Utils::AjaxLoadedVoucher(VoucherType::CRMI, $RowInquiry['id']); ?>>
                        #<?=$RowInquiry['voucher_number']?><small> - <?=DateDecode($RowInquiry['date_comment'], 'M d')?></small>
                    </a>
                </div>
                <div class="col-6 align-middle text-right nickname">
                    <small><?=$RowInquiry['nickname']?></small>
                </div>
            </div>
            <div class="row">
                <div class="col-10 customer-name">
                    <?=$RowInquiry['name']?>
                </div>
                <div class="col-2 text-right lead-type-icon">
                    <?=$this->getIconByInquiryType($RowInquiry['inquiry_type'])?>
                </div>
            </div>
            <div class="row additional-details">
                <div class="col-12 comment">
                    <?=$RowInquiry['comment']?>
                </div>
                <div class="col-12 text-center contact-icons">
                    <? if (!empty($RowInquiry['crm_sales_pitch_id'])) { ?>
                        <a class="btn btn-sm btn-purple" target="_blank" href="../../crm_sales_pitch/print_voucher.php?id=<?=$RowInquiry['id']?>"><i class="mdi mdi-file-pdf"></i> Pitch</a>
                    <? } ?>
                    <a class="btn btn-sm btn-info" href="tel:<?=$RowInquiry['mobile1']?>" title="<?=$RowInquiry['mobile1']?>"><i class="mdi mdi-phone"></i> Call</a>
                    <a class="btn btn-sm btn-success" target="_blank" href="<?=Utils::getWhatsAppLink($RowInquiry['mobile1'])?>"><i class="mdi mdi-whatsapp"></i> WhatsApp</a>
                </div>
                <div class="col-12 crm-badges">
                    <? if (!empty($RowInquiry['tags'])) { ?>
                        <div class="badge-wrapper bg-white pt-1 pl-1 pr-1 mt-2"><?=$this->getFormattedTagsForDisplay($RowInquiry['tags'])?></div>
                    <? } ?>
                </div>
            </div>
        </li>

        <?php
    }

    private function getFormattedTagsForDisplay($Tags)
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

    function getAllStages($IncludeBin=true)
    {
        $Where = '';
        if (!$IncludeBin)
            $Where = " AND id NOT IN (".implode(",", CRMInquiry::BIN_STAGES).")";

        return DBQM::query("SELECT * FROM crm_stage WHERE id IS NOT NULL {$Where} ORDER BY order_number");
    }

    function cleanStageName($StageName)
    {
        $StageName = strtolower($StageName);
        $StageName = str_replace(" ", "-", $StageName);
        $StageName = str_replace(["(",")"], "", $StageName);

        return $StageName;
    }

    private function getIconByInquiryType($InquiryType)
    {
        return match ($InquiryType) {
            'ERP' => "<img src='../../assets/images/favicon.png' class='img-fluid' />",
            'SMM' => '<i class="mdi mdi-instagram text-orange"></i>',
            'Website' => '<i class="mdi mdi-wordpress text-dark"></i>',
            default => ''
        };
    }
}