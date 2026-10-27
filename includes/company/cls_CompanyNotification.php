<?php


class CompanyNotification
{
    const TYPE_ERROR = 'ER';
    const TYPE_YEAR_CLOSE = 'YC';
    const TYPE_VOUCHER_SAVE = 'VS';
    const TYPE_VOUCHER_EDIT = 'VE';
    const TYPE_VOUCHER_DELETE = 'VD';

    static function create($Title, $Message, $Type, $Link='', $ErrorLogId=null)
    {
        DBQM::query("INSERT INTO cp_notification VALUES (
                                    0,
                                    ".escape(SecurityShell::getLoggedInUserId(), 'int', true).",
                                    ".escape(Company::getLoggedInCompanyId(), 'int', true).",
                                    ".escape(DatabaseHelper::NOW()).",
                                    ".escape($Type).",
                                    ".escape($Title).",
                                    ".escape($Message).",
                                    ".escape($Link).",
                                    ".escape(self::getShowToSameUser($Type) ? 'Y' : 'N').",
                                    ".escape($ErrorLogId, 'int', true)."
        )");
    }

    private static function getShowToSameUser($Type)
    {
        switch ($Type)
        {
            case self::TYPE_ERROR:
            case self::TYPE_VOUCHER_SAVE:
                return false;

            case self::TYPE_VOUCHER_DELETE:
            case self::TYPE_VOUCHER_EDIT:
            default:
                return true;
        }
    }

    function getLastFew($Count=5)
    {
        return DBQM::query("
                                    SELECT 
                                        * 
                                    FROM 
                                        cp_notification 
                                    WHERE 
                                        company_id = ".escape(Company::getLoggedInCompanyId())." AND (
                                            (
                                                show_to_same_user = 'Y' 
                                            ) 
                                            OR 
                                            (
                                                show_to_same_user = 'N' AND
                                                user_id <> " . escape(SecurityShell::getLoggedInUserId()) . "
                                            )
                                        ) 
                                    ORDER BY 
                                        date_time DESC 
                                    LIMIT 
                                        {$Count}
        ");
    }

    function getHTML()
    {
        $Select = $this->getLastFew();
        while ($Row = DBQM::fetch_assoc($Select))
        {
            list($Icon, $IconWrapperClass) = $this->getIcon($Row['type']);
            ?>
            <a href="javascript:void(0)" class="text-reset notification-item">
                <div class="media">
                    <div class="avatar-xs mr-3">
                        <span class="avatar-title bg-primary rounded-circle font-size-16 <?=$IconWrapperClass?>">
                            <i class="<?=$Icon?>"></i>
                        </span>
                    </div>
                    <div class="media-body">
                        <h6 class="mt-0 mb-1"><?=$Row['title']?></h6>
                        <div class="font-size-12 text-muted">
                            <p class="mb-1"><?=$Row['message']?></p>
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
        switch ($Type)
        {
            case self::TYPE_ERROR:
                return ["bx bx-error", "bg-danger"];

            case self::TYPE_VOUCHER_SAVE:
                return ["mdi mdi-content-save", "bg-success"];

            case self::TYPE_VOUCHER_DELETE:
                return ["mdi mdi-delete", "bg-warning"];

            case self::TYPE_VOUCHER_EDIT:
                return ["mdi mdi-pencil", "bg-info"];

            case self::TYPE_YEAR_CLOSE:
                return ["mdi mdi-calendar-plus", "bg-info"];

            default:
                return true;
        }
    }
}