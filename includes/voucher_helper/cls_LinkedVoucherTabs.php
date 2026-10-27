<?php


class LinkedVoucherTabs
{
    private $VoucherTypeId;
    private $ParentIndex = null;

//    private $Linked = [
//        [
//            "VoucherType" => [
//                VoucherType::SOV,
//                VoucherType::SIV,
//                VoucherType::SRV,
//            ],
//            'Other' => [
//                "sales/report_sales.php" => "Edit Voucher"
//            ]
//        ],
//        [
//            "VoucherType" => [
//                VoucherType::PR,
//                VoucherType::POV,
//                VoucherType::PIV,
//                VoucherType::PRV
//            ],
//            'Other' => [
//                "purchase/report_purchase.php" => "Edit Voucher"
//            ]
//        ],
//        [
//            "VoucherType" => [
//                VoucherType::CPV,
//                VoucherType::CRV,
//                VoucherType::BPV,
//                VoucherType::BRV,
//                VoucherType::CQV,
//                VoucherType::JV
//            ],
//            'Other' => [
//                "_voucher_defaults/report.php?voucher_class=Vouchers" => "Edit Voucher",
//                "_voucher_defaults/report.php?voucher_class=JournalVouchers" => "Edit JV",
//                "_voucher_defaults/report.php?voucher_type_id=14&voucher_class=Cheque" => "Edit Cheque"
//            ]
//        ]
//    ];

    public function __construct($VoucherTypeId)
    {
        $this->Linked[] = [
            "VoucherType" => [
                VoucherType::CRMI
            ],
            'Other' => [
                VoucherType::getLink(VoucherType::CRMM)."&crm_inquiry_id=".$_GET['id'] => "<i class='mdi mdi-plus'></i> New Meeting"
            ]
        ];


        $this->VoucherTypeId = $VoucherTypeId;

        foreach ($this->Linked as $Key => $LinkedArray)
        {
            foreach ($LinkedArray['VoucherType'] as $VoucherType)
                if ($VoucherType == $VoucherTypeId)
                    $this->ParentIndex = $Key;

            foreach ($LinkedArray['Other'] as $Link => $Name)
                if (strstr($_SERVER['PHP_SELF'], $Link))
                    $this->ParentIndex = $Key;
        }
    }

    function getTabs()
    {
        if ($this->ParentIndex !== null) {
            ?>
            <div class="bg-white text-center mb-3 rounded overflow-hidden d-none d-md-block">
                <div class="row">
                    <div class="col-lg-11 col-md-10">
                        <div class="row">
                            <?
                            foreach ($this->Linked[$this->ParentIndex]['VoucherType'] as $VoucherType)
                            {
                                if (!SecurityShell::isVoucherAccess($VoucherType))
                                    continue;

                                $VoucherName = VoucherType::getName($VoucherType);
                                $VoucherName = str_replace("Voucher", "", $VoucherName);
                                $VoucherName = str_replace("Vouhcer", "", $VoucherName);

                                if ($VoucherType == $this->VoucherTypeId)
                                {
                                    ?>
                                    <div class="col border-right bg-color p-2 text-white d-block"><?=$VoucherName?></div>
                                    <?
                                }
                                else
                                {
                                    ?>
                                    <div class="col border-right"><a href="<?=VoucherType::getLink($VoucherType)?>" class="p-2 d-block" target="_blank"><?=$VoucherName?></a></div>
                                    <?
                                }
                            }
                            foreach ($this->Linked[$this->ParentIndex]['Other'] as $Link => $Name)
                            {
                                if (!str_contains($Link, '://'))
                                    $Link = ProjectHelper::SiteURL().$Link;
                                ?>
                                <div class="col border-right <?=strstr($_SERVER['PHP_SELF'], $Link) ? 'bg-color' : '' ?>"><a href="<?=$Link?>" class="p-2 d-block <?=strstr($_SERVER['PHP_SELF'], $Link) ? 'text-white' : '' ?>" target="_blank"><?=$Name?></a></div>
                                <?
                            }
                            ?>
                        </div>
                    </div>
                    <?
                    $TransactionAccountValues = [];

                    if (isset($_GET['name']))
                    {
                        $TransactionAccountValues['name'] = $_GET['name'];
                        $TransactionAccountValues['mob'] = $_GET['mob'];
                        $TransactionAccountValues['city'] = $_GET['city'];
                        $TransactionAccountValues['email1'] = $_GET['email1'];
                        $TransactionAccountValues['coa_id'] = $_GET['coa_id'];
                    }
                    ?>
                    <div class="col-lg-1 col-md-2 font-size-18">
                        <div class="row">
                            <div class="col border-right"><a href="<?=ProjectHelper::SiteURL()?>chart_of_account/manage_accounts.php<?=!empty($TransactionAccountValues) ? '?'.http_build_query($TransactionAccountValues) : ''?>" title="Create New Transaction Account" class="p-1 d-block text-success" target="_blank"><i class='mdi mdi-account-plus'></i></a></div>
                            <div class="col border-right"><a href="<?=ProjectHelper::SiteURL()?>stock/stock_management.php" title="Create New Stock" class="p-1 d-block text-warning" target="_blank"><i class='mdi mdi-cube'></i></a></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}