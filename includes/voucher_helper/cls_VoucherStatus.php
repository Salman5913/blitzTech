<?php

class VoucherStatus
{
    const VOUCHER_STATUS_APPROVED = 'Approved';
    const VOUCHER_STATUS_DRAFT = 'Draft';
    const VOUCHER_STATUS_DELETED = 'Deleted';
    const VOUCHER_STATUS_UNAPPROVED = 'Pending Level Of Approval';


    const KEY_TEXT = 'text';
    const KEY_COLUMN_CLASS = 'status_report_column_text_class';
    const KEY_ROW_cLASS = 'highlight_report_row_class';
    const KEY_ALLOW_FOR_APPROVED = 'allow_for_approved';

    static $StatusArray = [
        self::VOUCHER_STATUS_APPROVED => [
            self::KEY_TEXT => self::VOUCHER_STATUS_APPROVED,
            self::KEY_COLUMN_CLASS => "text-success",
            self::KEY_ROW_cLASS => "",
            self::KEY_ALLOW_FOR_APPROVED => false
        ],
        self::VOUCHER_STATUS_DRAFT => [
            self::KEY_TEXT => self::VOUCHER_STATUS_DRAFT,
            self::KEY_COLUMN_CLASS => "text-info",
            self::KEY_ROW_cLASS => " bg-soft-info row-deleted ",
            self::KEY_ALLOW_FOR_APPROVED => true
        ],
        self::VOUCHER_STATUS_DELETED => [
            self::KEY_TEXT => self::VOUCHER_STATUS_DELETED,
            self::KEY_COLUMN_CLASS => "text-danger",
            self::KEY_ROW_cLASS => " bg-soft-danger row-deleted ",
            self::KEY_ALLOW_FOR_APPROVED => false
        ],
        self::VOUCHER_STATUS_UNAPPROVED => [
            self::KEY_TEXT => self::VOUCHER_STATUS_UNAPPROVED,
            self::KEY_COLUMN_CLASS => "text-warning",
            self::KEY_ROW_cLASS => " bg-soft-warning row-deleted ",
            self::KEY_ALLOW_FOR_APPROVED => true
        ],
    ];

    static function setVoucherRowStatus($Row, array $UnApprovedVouchersList)
    {
        $VoucherStatus = '';
        if ($Row['is_deleted'] == 'Y')
        {
            echo self::$StatusArray[self::VOUCHER_STATUS_DELETED][self::KEY_ROW_cLASS];
            $VoucherStatus = self::$StatusArray[self::VOUCHER_STATUS_DELETED][self::KEY_TEXT];
        }
        elseif ($Row['is_draft'] == 'Y')
        {
            echo self::$StatusArray[self::VOUCHER_STATUS_DRAFT][self::KEY_ROW_cLASS];
            $VoucherStatus = self::$StatusArray[self::VOUCHER_STATUS_DRAFT][self::KEY_TEXT];
        }
        elseif (in_array($Row['id'], $UnApprovedVouchersList))
        {
            echo self::$StatusArray[self::VOUCHER_STATUS_UNAPPROVED][self::KEY_ROW_cLASS];
            $VoucherStatus = self::$StatusArray[self::VOUCHER_STATUS_UNAPPROVED][self::KEY_TEXT];
        }
        else
        {
            $VoucherStatus = self::$StatusArray[self::VOUCHER_STATUS_APPROVED][self::KEY_TEXT];
        }

        return $VoucherStatus;
    }

    static function getColumnClass($VoucherStatus)
    {
        return self::$StatusArray[$VoucherStatus][self::KEY_COLUMN_CLASS];
    }

    static function isAllowedForApprove($VoucherStatus)
    {
        return self::$StatusArray[$VoucherStatus][self::KEY_ALLOW_FOR_APPROVED];
    }

}