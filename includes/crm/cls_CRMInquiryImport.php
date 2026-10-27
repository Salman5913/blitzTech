<?php

class CRMInquiryImport
{
    private array $Leads = [];

    public function __construct()
    {
        $this->getImportData();
        $this->insertData();
    }

    function getImportData()
    {
        $Data = Utils::getDataFromURL('https://crm.3dm-sols.com/cronjob/get_client_leads.php?afegvsv=true&client_id='.base64_encode(1));
        $this->Leads = json_decode($Data, true);
    }

    function insertData()
    {
        foreach ($this->Leads as $Lead)
        {
            $SelectExisting = DBQM::query("SELECT * FROM crm_inquiry WHERE import_reference_id = ".escape($Lead['lead_id']));
            if (DatabaseHelper::getRecordCount($SelectExisting) > 0)
                continue;

            $_POST['voucher_type_id'] = VoucherType::CRMI;
            $_POST['voucher_date'] = DateDecode($Lead['date_time']);

            $oCRMInquiry = new CRMInquiry();
            $oCRMInquiry->initForSave();

            $InquiryType = 'ERP';
            foreach ($Lead['additional'] as $AdditionalInfo)
            {
                if (str_contains(str_replace("_", " ", $AdditionalInfo['label']), "erp software"))
                    $InquiryType = 'ERP';
                elseif (str_contains(str_replace("_", " ", $AdditionalInfo['label']), "digital marketing"))
                    $InquiryType = 'SMM';
            }


            /*if (str_starts_with($Lead['phone'], "00"))
                $Lead['phone'] = "+".ltrim($Lead['phone'],0);
            if (str_starts_with($Lead['phone'], "05"))
                $Lead['phone'] = "+971".ltrim($Lead['phone'],0);
            elseif (str_starts_with($Lead['phone'], "5"))
                $Lead['phone'] = "+971".$Lead['phone'];
            $Lead['phone'] = str_replace(" ", "", $Lead['phone']);*/

            $Lead['phone'] = ContactCard::numberFormat($Lead['phone']);
            $ContactCardId = ContactCard::isContactCardInDir($Lead['phone'], $Lead['name'], $Lead['email']);


            if ($Lead['client_id'] == 10)
                $InquiryType = 'ERP';

            $oCRMInquiry->saveMainTableData([
                $ContactCardId,
                null,
                CRMInquiry::SOURCE_SOCIAL_MEDIA,
                '',
                'Imported - New',
                $Lead['lead_id'],
                null,
                null,
                $InquiryType,
                '',
                CRMInquiry::STAGE_NEW,
                0
            ]);

            foreach ($Lead['additional'] AS $AdditionalInfo)
            {
                $oCRMInquiry->saveDataTableData([
                    str_replace("_", " ", $AdditionalInfo['label']),
                    $AdditionalInfo['value']
                ], CRMInquiry::IND_ADDITIONAL_INFO);
            }

            $oSMS = new SMSManager();
            if (str_starts_with($Lead['phone'], "+92"))
                $oSMS->setCompanyName(SMSManager::COMPANY_3DM);
            else
                $oSMS->setCompanyName(SMSManager::COMPANY_CREATIVE_ALIF);

            $oSMS->sendToNumberDirectly($Lead['phone'], 'We appreciate your interest in customizable Accounts/Stock Management and Social Media Management solutions. Sales team will contact you soon.');
        }
    }
}