<?php

class ContactCard extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherReport, DefaultVoucherPrint, DefaultVoucherFormAdditionalFormatting
{
    function __construct()
    {
        parent::__construct("dir_contact_card", "", "", VoucherType::CCRD, false);
    }

    protected function saveTransactionEntries()
    {
        $RowDetails = $this->getDetails();

        $Mobile1 = ContactCard::numberFormat($RowDetails['mobile1']);
        $Mobile2 = ContactCard::numberFormat($RowDetails['mobile2']);

        $this->updateFormattedNumber($RowDetails['id'], $Mobile1, $Mobile2);
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $oBasicVoucherForm = new BasicVoucherFormUtils();
        $oBasicVoucherForm->addElementGroup("Company", "col-md-3", "col-md-12", ['company', 'designation']);
        $oBasicVoucherForm->addElementGroup("Contact", "col-md-3", "col-md-12", ['email1', 'email2', 'mobile1', 'mobile2']);
        $oBasicVoucherForm->addElementGroup("Legal", "col-md-3", "col-md-12", ['cnic', 'ntn']);
        $oBasicVoucherForm->addElementGroup("Business Card", "col-md-3", "col-md-12", ['img_businesscard_front', 'img_businesscard_back']);
        $oBasicVoucherForm->addElementGroup("SMS", "col-md-3", "col-md-12", ['sms_company']);

        $oBasicVoucherForm->setAutocomplete('reference_contact', Autocomplete::TYPE_CONTACT_CARD);

        $oBasicVoucherForm->setColumnDisplayType('sms_company', new FormFieldTypeSelect(['3DM Solutions', 'CreativeAlif'], false));
        $oBasicVoucherForm->setColumnDisplayType('img_businesscard_front', new FormFieldTypeFile(new UploadPath(__CLASS__)));
        $oBasicVoucherForm->setColumnDisplayType('img_businesscard_back', new FormFieldTypeFile(new UploadPath(__CLASS__)));

        $oBasicVoucherForm->setRequiredFields(['name', 'mobile1']);

        return $oBasicVoucherForm;
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
        $oReportFilter = new BasicVoucherReportFilters();
        $oReportFilter->setFilter('company', 'Company', 'text');
        $oReportFilter->setFilter('id', 'Name', '', '', Autocomplete::TYPE_CONTACT_CARD);

        return $oReportFilter;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = '';
        $Where .= UtilsQueryMaker::like('cc.company', $_GET['company']);
        $Where .= UtilsQueryMaker::equal('cc.id', $_GET['id']);

        return DBQM::query("
            SELECT 
                cc.*,
                did.voucher_type_id,
                did.voucher_id,
                did.voucher_date,
                did.voucher_number,
                CONCAT(cc.email1, ' ', IFNULL(cc.email2, '')) AS email,
                CONCAT(cc.mobile1, ' ', IFNULL(cc.mobile2, '')) AS mobile,
                cref.name as reference_name
            FROM
                distinct_id did,
                dir_contact_card cc
                LEFT JOIN 
                    dir_contact_card cref ON cc.reference_contact = cref.id 
            WHERE
                did.id = cc.distinct_id
                {$Where}
            ORDER BY
                cc.id DESC
            LIMIT 100
        ");
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('name', "Name");
        $oTableColumn->addColumn('email', "Email");
        $oTableColumn->addColumn('mobile', "Mobile");
        $oTableColumn->addColumn('company', "Company");
        $oTableColumn->addColumn('reference_name', "Reference Name");

        return $oTableColumn;
    }

    static function isContactCardInDir($PhoneNumber, $Name, $Email)
    {
        $Select = DBQM::query("SELECT id FROM dir_contact_card WHERE (mobile1 = " . escape($PhoneNumber) . " OR mobile2 = " . escape($PhoneNumber) . ") ");
        $ContactCardId = DBQM::result($Select, 0);

        if (empty($ContactCardId)) {
            $oContactCard = new ContactCard();
            $oContactCard->setVariablesForSaveManually("", VoucherType::CCRD);
            $oContactCard->initForSave();

            $oContactCard->saveMainTableData([
                $Name,
                '',
                '',
                '',
                $Email,
                '',
                $PhoneNumber,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ]);

            $ContactCardId = $oContactCard->getVoucherId();
        }

        return $ContactCardId;
    }

    function getFormFormattedColumnLabel($ColumnName): string|null
    {
        return match ($ColumnName) {
            'email1' => 'Email 1',
            'email2' => 'Email 2',
            'mobile1' => 'Mobile 1',
            'mobile2' => 'Mobile 2',
            'img_businesscard_front' => 'Business Card (Front)',
            'img_businesscard_back' => 'Business Card (Back)',
            'cnic' => 'CNIC',
            'ntn' => 'NTN',
            default => null
        };
    }

    static function numberFormat($Number)
    {
        $Number = preg_replace('/\D/', '', $Number);

        if (empty($Number))
            return '';

        if (str_starts_with($Number, "00"))
            $Number = "+" . ltrim($Number, 0);
        elseif (str_starts_with($Number, "05"))
            $Number = "+971" . ltrim($Number, 0);
        elseif (str_starts_with($Number, "5"))
            $Number = "+971" . $Number;
        elseif (str_starts_with($Number, "92"))
            $Number = "+" . $Number;
        elseif (str_starts_with($Number, "03"))
            $Number = "+92" . ltrim($Number, 0);
        elseif (str_starts_with($Number, "3"))
            $Number = "+92" . $Number;
        elseif (str_starts_with($Number, "23"))
            $Number = "+9" . $Number;
        elseif (str_starts_with($Number, "9"))
            $Number = "+" . $Number;

        return str_replace(["(", ")", " ", "-"], "", $Number);
    }

    static function getContactCardDetails($Id)
    {
        $Select = DBQM::query("SELECT * FROM dir_contact_card WHERE id = " . escape($Id));
        return DBQM::fetch_assoc($Select);
    }

    function updateFormattedNumber($ContactCardId, $Number1, $Number2)
    {
        DBQM::query("UPDATE dir_contact_card SET mobile1 = " . escape($Number1) . ", mobile2 = " . escape($Number2) . " WHERE id = " . escape($ContactCardId));
    }
}