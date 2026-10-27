<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 024, 12/24/2014
 * Time: 3:39 PM
 */
class VoucherType
{
    /**
     * Vouchers should not be created from more than one place,
     * like BRV should be created from manual vouchers,
     * SIV should be created from sales page only
     * OP should be created from just opening balance (not from close account also)
     */

    /**
     * Purchase Invoice Voucher
     */
    const PIV = 1;

    /**
     * Sales Invoice Voucher
     */
    const SIV = 2;

    /**
     * Bank Receipt Voucher
     */
    const BRV = 3;

    /**
     * Bank Payment Voucher
     */
    const BPV = 4;

    /**
     * Cash Receipt Voucher
     */
    const CRV = 5;

    /**
     * Cash Payment Voucher
     */
    const CPV = 6;

    /**
     * Opening Stock
     */
    const OPS = 7;

    /**
     * Journal Voucher
     */
    const JV = 8;


    /**
     * Stock Movement Voucher
     */
    const SMV = 9;

    /**
     * Stock Dispose Voucher
     */
    const STDV = 10;

    /**
     * Manufacturing Stock Recipe
     */
    const MFSR = 11;

    /**
     * Cheque Forward Voucher
     */
    const CQF = 12;

    /**
     * Cheque Return Voucher
     */
    const CQR = 13;

    /**
     * Cheque Voucher
     */
    const CQV = 14;

    /**
     * Sales Return Voucher
     */
    const SRV = 15;


    /**
     * Purchase Return Voucher
     */
    const PRV = 16;


    /**
     * Opening Account Balance
     */
    const OPA = 17;


    /**
     * Purchase Order Voucher
     */
    const POV = 18;


    /**
     * Sales Order Voucher
     */
    const SOV = 19;


    /**
     * Purchase Requisition Voucher
     */
    const PR = 22;


    /**
     * Basic Manufacturing Voucher
     */
    const BMV = 26;

    /**
     * Bank Statement Import Voucher
     */
    const BSIV = 33;

    /**
     * Purchase Import Consignment Payment Voucher
     */
    const PT = 34;

    /**
     * Bank Statement Import
     */
    const BSID = 60;

    /**
     * CRM Inquiry
     */
    const CRMI = 97;

    /**
     * CRM Sales Pitch
     */
    const CRSP = 98;

    /**
     * CRM Meeting
     */
    const CRMM = 99;


    /**
     * CRM Inquiry Type
     */
    const CRIT = 106;


    /**
     * Advance Information Voucher
     */
    const ADV = 43;

    /**
     * Loan Management
     */
    const LV = 44;

    /**
     * Salary Management
     */
    const SM = 45;

    /**
     * Loan Cancellation
     */
    const LC = 86;

    /**
     * Salary Payment Voucher
     */
    const SLRY = 48;

    /**
     * Project Management
     */
    const PM = 87;

    /**
     * Quotation Voucher
     */
    const QTV = 88;

    /**
     * Quotation Estimate Voucher
     */
    const QTEV = 89;

    /**
     * Employee WorkTime Voucher
     */
    const EWV = 90;

    /**
     * Stock Requisition Voucher
     */
    const STRV = 91;

    /**
     * Stock Requisition Return Voucher
     */
    const STRRV = 92;

    /**
     * Sales Wise Payment Voucher
     */
    const SPV = 93;

    /**
     * Project Wise Payment Voucher
     */
    const PWPV = 94;

    /**
     * Project Wise Payment Data Voucher
     */
    const PWPDV = 95;

    /**
     * Project Wise Advance Payment Voucher
     */
    const PWAPV = 96;


    /**
     * Contact Card
     */
    const CCRD = 100;


    const UPV = 107;

    const SIPV = 108;


    const PIPV = 109;




    private static $SalesVoucherTypes = [self::SIV];


    private $VoucherType;

    public function __construct($VoucherType)
    {
        $this->VoucherType = $VoucherType;
    }

    function getPrefix()
    {
        return self::getDefaultPrefix($this->VoucherType);
    }

    static function getDefaultPrefix($VoucherTypeId)
    {
        return VoucherTypeHelper::getInstance()->getCode($VoucherTypeId);
    }

    function getVoucherType()
    {
        return $this->VoucherType;
    }

    static function getName($VoucherTypeId)
    {
        return VoucherTypeHelper::getInstance()->getAbbr($VoucherTypeId);
    }

    static function getCode($VoucherTypeId)
    {
        return VoucherTypeHelper::getInstance()->getCode($VoucherTypeId);
    }

    static function isPurchaseVoucher($VoucherTypeId)
    {
        return ($VoucherTypeId == VoucherType::PIV OR $VoucherTypeId == VoucherType::OPS);
    }

    static function isVoucherTypeReceipt($VoucherTypeId) {
        if ($VoucherTypeId == self::CRV or $VoucherTypeId == self::BRV)
            return true;
        return false;
    }

	static function getLink($VoucherTypeId, $Id='', $AjaxVoucherLink=false, $isReport=false)
	{
		switch ($VoucherTypeId)
		{
            case self::POV:
            case self::PIV:
            case self::PRV:
            case self::OPS:
                $Link = ProjectHelper::SiteURL()."purchase/purchase_voucher.php";
                break;

            case self::SOV:
            case self::SIV:
            case self::SRV:
                $Link = ProjectHelper::SiteURL()."sales/sales_voucher.php";
                break;

            case self::ADV:
                $Link = ProjectHelper::SiteURL()."loan/bulk_advances.php";
                break;

            case self::LV:
                $Link = ProjectHelper::SiteURL()."loan/manage_loan.php";
                break;

            case self::SM:
                $Link = $isReport ? ProjectHelper::SiteURL()."salary/salary_management.php" : ProjectHelper::SiteURL()."salary/salary_log_selection.php";
                break;

            case self::BMV:
                $Link = ProjectHelper::SiteURL()."manufacturing/pre_manufacture.php";
                break;

            case self::SIPV:
                $Link = ProjectHelper::SiteURL()."sales_invoice_payment/sales_invoice_payment.php";
                break;

            case self::PIPV:
                $Link = ProjectHelper::SiteURL()."purchase_invoice_payment/purchase_invoice_payment.php";
                break;


            case self::BSID:
            case self::BSIV:
                $VoucherTypeId = self::BSIV;
                if ($AjaxVoucherLink)
                    $Link = ProjectHelper::SiteURL() . "AjaxController/" . self::getVoucherClass($VoucherTypeId);
                else
                    $Link = ProjectHelper::SiteURL() . "Controller/" . self::getVoucherClass($VoucherTypeId);
                break;

            default:
                if ($AjaxVoucherLink)
                    $Link = ProjectHelper::SiteURL() . "AjaxController/" . self::getVoucherClass($VoucherTypeId);
                else
                    $Link = ProjectHelper::SiteURL() . "Controller/" . self::getVoucherClass($VoucherTypeId);
        }

        $Concatenation = str_contains($Link, "?") ? '&' : '?';
        return $Link . $Concatenation . "voucher_type_id=" . $VoucherTypeId . (empty($Id) ? '' : "&id=" . $Id);
    }

    static function getVoucherClass($VoucherTypeId)
    {
        return VoucherTypeHelper::getInstance()->getClass($VoucherTypeId);
    }

	function isApprovalRequired()
    {
        $RequiredApproval = [
            self::CPV,
            self::CRV,
            self::JV,
            self::BPV,
            self::BRV,
            self::CQV,
            self::SIV,
            self::SRV,
            self::PIV,
            self::PRV
        ];

        if (in_array($this->VoucherType, $RequiredApproval))
            return true;
        else
            return false;
    }

    static function getAllVoucherTypes()
    {
        return DBQM::query("SELECT vt.*, cm.module FROM cp_company_module cm, cp_company_module_app_areas cma, vt_voucher_type vt WHERE cm.id = cma.company_module_id AND vt.id = cma.voucher_type_id AND cm.is_active = 'Y' AND vt.is_active = 'Y' ORDER BY cm.module, abbr");
    }

    static function getSalesVoucherTypes($ForWhereClause=true)
    {
        if ($ForWhereClause)
            return implode(", ", array_map("escape", self::$SalesVoucherTypes));
        else
            return self::$SalesVoucherTypes;
    }

    static function getSalesVoucherTypesDropdown()
    {
        $Array = [];
        foreach (self::$SalesVoucherTypes as $VoucherTypeId)
        {
            $Array[$VoucherTypeId] = self::getName($VoucherTypeId);
        }

        return $Array;
    }

    static function getIdByCode($Code)
    {
        $Select = DBQM::query("SELECT id FROM vt_voucher_type WHERE name = ".escape($Code));
        return DBQM::result($Select, 0);
    }

    static function getDefaultReportLink($VoucherTypeId, $ManualClassName='')
    {
        $VoucherClass = self::getVoucherClass($VoucherTypeId);

        if (!empty($VoucherClass) AND empty($ManualClassName))
            return ProjectHelper::SiteURL()."_voucher_defaults/report.php?voucher_class=".$VoucherClass."&voucher_type_id=".$VoucherTypeId;
        elseif (!empty($ManualClassName))
            return BasicVoucherNavigation::getReportFileName($ManualClassName)."?voucher_type_id=".$VoucherTypeId;
        else
            return "";
    }
}