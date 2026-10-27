<?php

class VoucherHelper
{
    static function redirectIfVoucherEditNotPermitted($DistinctId)
    {
        if (SecurityShell::isAccess("Master Details", "Own Vouchers Only"))
        {
            $Select = DBQM::query("SELECT id FROM distinct_id WHERE created_by = ".escape(SecurityShell::getLoggedInUserId())." AND id = ".escape($DistinctId));

            if (DatabaseHelper::isRecordSetEmpty($Select))
                ErrorHandler::UnauthorizedAccess();
        }
    }

    static function getDefaultPrintLink($ClassName)
    {
        return ProjectHelper::SiteURL()."Print/{$ClassName}";
    }
}