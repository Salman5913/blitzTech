<?php


class Notification
{
    const TYPE_ERROR = 'ER';
    const TYPE_YEAR_CLOSE = 'YC';
    const TYPE_VOUCHER_SAVE = 'VS';
    const TYPE_VOUCHER_EDIT = 'VE';
    const TYPE_VOUCHER_DELETE = 'VD';
    const TYPE_VOUCHER_COPY = 'CP';
    const TYPE_ACTIONABLE = 'AT';

    static function create($Title, $Message, $Type, $DistinctId='', $isFullDay='N', $UserId=null, $ActionURL=null, $isDismiss=null, $ActionTag=null)
    {
        DBQM::query("INSERT INTO nt_notification VALUES (
                                    0,
                                    ".escape($DistinctId, 'int', true).",
                                    ".escape($UserId ?? SecurityShell::getLoggedInUserId(), 'int', true).",
                                    ".escape(DatabaseHelper::NOW()).",
                                    ".escape($Type).",
                                    ".escape($Title).",
                                    ".escape($Message).",
                                    ".escape($isFullDay).",
                                    ".escape($ActionTag, 'text', true).",
                                    ".escape($ActionURL, 'text', true).",
                                    ".escape($isDismiss, 'text', true)."
        )");
    }

    static function createFromVoucher($Type, $DistinctId)
    {
        self::create('', '', $Type, $DistinctId);
    }

    function getLastFew($Count=5)
    {
        $Where = '';
        if (SecurityShell::isAccess("Settings", "Own Notification"))
            $Where .= UtilsQueryMaker::equal("user_id", SecurityShell::getLoggedInUserId());

        return DBQM::query("
                                    SELECT 
                                        nt.date_time,
                                        nt.type,
                                        CONCAT(vt.name,'-',did.voucher_number) AS voucher_info,
                                        nt.title,
                                        nt.message,
                                        shl.username,
                                        did.voucher_id,
                                        did.voucher_type_id
                                    FROM 
                                        sh_login shl,
                                        nt_notification nt
                                        LEFT JOIN 
                                            distinct_id did ON nt.distinct_id = did.id
                                        LEFT JOIN 
                                            vt_voucher_type vt ON did.voucher_type_id = vt.id
                                    WHERE 
                                        nt.user_id = shl.id AND
                                        is_full_day <> 'Y'
                                        {$Where}
                                    ORDER BY
                                        date_time DESC
                                    LIMIT 
                                        {$Count}
        ");
    }

    function getTodayNotification()
    {
        return DBQM::query("
                                    SELECT 
                                        * 
                                    FROM 
                                        nt_notification 
                                    WHERE 
                                        is_full_day = 'Y' AND
                                        user_id = ".escape(SecurityShell::getLoggedInUserId())." AND
                                        date_time LIKE ".escape(date("Y-m-d %"))."
        ");
    }

    function getHTML()
    {
        $Select = $this->getLastFew(15);
        while ($Row = DBQM::fetch_assoc($Select))
        {
            list($Icon, $IconWrapperClass) = $this->getIcon($Row['type']);
            ?>
            <a href="<?=$Row['voucher_info'] ? VoucherType::getLink($Row['voucher_type_id'], $Row['voucher_id']) : 'javascript:void(0)' ?>" target="_blank" class="text-reset notification-item">
                <div class="media">
                    <div class="avatar-xs mr-3">
                        <span class="avatar-title bg-primary rounded-circle font-size-16 <?=$IconWrapperClass?>">
                            <i class="<?=$Icon?>"></i>
                        </span>
                    </div>
                    <div class="media-body">
                        <h6 class="mt-0 mb-1"><?=$Row['voucher_info'] ?? $Row['title']?></h6>
                        <div class="font-size-12 text-muted">
                            <p class="mb-1"><?=$Row['voucher_info'] ? ($Row['type'] == 'VE' ? 'Edited By ' : 'Deleted By ').$Row['username'] : strip_tags($Row['message'])?></p>
                            <p class="mb-0"><i class="mdi mdi-clock-outline"></i> <?=DateTimeHelper::display($Row['date_time'], false)?></p>
                        </div>
                    </div>
                </div>
            </a>
            <?
        }
    }

    function getIcon($Type)
    {
        return match ($Type) {
            self::TYPE_ERROR => ["bx bx-error", "bg-danger"],
            self::TYPE_VOUCHER_SAVE => ["mdi mdi-content-save", "bg-success"],
            self::TYPE_VOUCHER_DELETE => ["mdi mdi-delete", "bg-danger"],
            self::TYPE_VOUCHER_EDIT => ["mdi mdi-pencil", "bg-info"],
            self::TYPE_YEAR_CLOSE => ["mdi mdi-calendar-plus", "bg-info"],
            default => true,
        };
    }
}