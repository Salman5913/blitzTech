<?php

class BasicVoucherArchive
{
    static function getModificationList($Id, $VoucherTypeId)
    {
        $SelectDistinctId = DBQM::query("SELECT id FROM distinct_id WHERE voucher_id = ".escape($Id)." AND voucher_type_id = ".escape($VoucherTypeId));
        return DBQM::query("SELECT * FROM voucher_modification_tracker WHERE distinct_id = ".escape(DBQM::result($SelectDistinctId, 0))." ORDER BY id DESC");
    }
}