<?php

class HeaderUtils
{
    /**
     * @param $VoucherTypeId
     * @param $ReportLink string
     * @param $isReportViewAccess bool if user cannot create a voucher but can view all the vouchers
     * @return void
     */


    function menuVoucherType($VoucherTypeId, string $ReportLink='', bool $isReportViewAccess=false, $LinkLabel='')
    {
        if (!SecurityShell::isVoucherAccess($VoucherTypeId) and !$isReportViewAccess)
            return;

        $Abbr = VoucherTypeHelper::getInstance()->getAbbr($VoucherTypeId);
        $Abbr = str_replace(["Voucher", "Purchase", "Sales"], "", $Abbr);

        $ReportLink = !empty($ReportLink) ? $ReportLink : VoucherType::getDefaultReportLink($VoucherTypeId);
        $ReportLink .= (strpos($ReportLink, "?") ? "&" : "?")."voucher_type_id=".$VoucherTypeId;

        $Abbr = !empty($LinkLabel) ? $LinkLabel : $Abbr;

        if (SecurityShell::isVoucherAccess($VoucherTypeId))
        {
            $this->menuReport($Abbr, VoucherType::getLink($VoucherTypeId), $ReportLink);
        }
        else
        {
            $this->menuReport("View ".$Abbr, "", $ReportLink);
        }
    }

    function menuReport($Name, $Link, $ViewLink='', $NewTab=false, $MainAccessArea='', $SubAccessArea='')
    {
        if (SecurityShell::isAccess($MainAccessArea, $SubAccessArea) OR empty($SubAccessArea)) {

            $Link = ProjectHelper::SiteURL().str_replace(ProjectHelper::SiteURL(), "", $Link);
            if (!empty($ViewLink))
                $ViewLink = ProjectHelper::SiteURL().str_replace(ProjectHelper::SiteURL(), "", $ViewLink);
        ?>
            <li>
                <a href="<?=$Link?>" <?=$NewTab ? ' target="_blank" ' : '' ?>><?=$Name?></a>
                <? if (!empty($ViewLink)) { ?>
                    <span>
                        <a href="<?=$ViewLink?>" title="View All"><i class="mdi mdi-eye-outline"></i></a>
                    </span>
                <? } ?>
            </li>
        <?php
        }
    }

    function customizedReports()
    {
        if (!SecurityShell::isVoucherAccess(VoucherType::PT)) return;

        $Select = PivotTableVoucher::getAllReports();

        while ($Row = DBQM::fetch_assoc($Select))
        {
            if (SecurityShell::isReportAccess($Row['id']))
                $this->menuReport($Row['report_name'], "", 'CustomizedReport/' . Utils::clearStringForURL($Row['report_name']) . "?id=" . $Row['id'] . "&voucher_type_id=" . $Row['voucher_type_id'], '', true);
        }
    }

    function newCustomizedReport()
    {
        if (!SecurityShell::isVoucherAccess(VoucherType::PT)) return;

        $this->menuReport('New Ledger Report', VoucherType::getLink(VoucherType::PT)."&pivot_table_class=PivotTableLedger", VoucherType::getDefaultReportLink(VoucherType::PT));
    }

    function menuDefaultReport($VoucherTypeId, $Name, $NewTab=false)
    {
        if (SecurityShell::isVoucherAccess($VoucherTypeId)) {
        ?>
        <li>
            <a href="<?=VoucherType::getDefaultReportLink($VoucherTypeId);?>" <?=$NewTab ? ' target="_blank" ' : '' ?>><?=$Name?></a>
        </li>
        <?php
        }
    }
}