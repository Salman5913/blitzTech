<?php


class DashboardStats
{
    function getIconsData()
    {
        /* ******************* Get COA Ids ********************* */
        $SelectRevenueCOAIds = DBQM::query("SELECT get_children_ids_full(4)");
        $SelectExpenseCOAIds = DBQM::query("SELECT get_children_ids_full(5)");
        $SelectReceivableCOAIds = DBQM::query("SELECT get_children_ids_full(".getConstantCategory()->getReceivable().")");
        $SelectPayableCOAIds = DBQM::query("SELECT get_children_ids_full(".getConstantCategory()->getPayable().")");
        /* ******************* Get COA Ids ********************* */


        /* *************** Dates ************** */
        $DateFromMain = strtotime("-29 days");
        $DateTillMain = time();

        $DateFromCompare = strtotime("-60 days");
        $DateTillCompare = strtotime("-30 days");

        $MainDuration = UtilsQueryMaker::date("l.date_time", date("m/d/Y", $DateFromMain), date("m/d/Y"));
        $CompareDuration = UtilsQueryMaker::date("l.date_time", date("m/d/Y", $DateFromCompare), date("m/d/Y", $DateTillCompare));

        $MainDurationDistinctId = UtilsQueryMaker::date("did.voucher_date", date("m/d/Y", $DateFromMain), date("m/d/Y"));
        $CompareDurationDistinctId = UtilsQueryMaker::date("did.voucher_date", date("m/d/Y", $DateFromCompare), date("m/d/Y", $DateTillCompare));

        $MainDurationTill = UtilsQueryMaker::date("l.date_time", '', date("m/d/Y"));
        $CompareDurationTill = UtilsQueryMaker::date("l.date_time", '', date("m/d/Y", $DateTillCompare));
        /* *************** Dates ************** */

        $Query = "
            SELECT
               (SELECT SUM(credit - debit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND a.coa_id IN (".DBQM::result($SelectRevenueCOAIds, 0).") {$MainDuration}) AS current_sales,
               (SELECT SUM(credit - debit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND a.coa_id IN (".DBQM::result($SelectRevenueCOAIds, 0).") {$CompareDuration}) AS previous_sales,
               
               
               (SELECT SUM(debit - credit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND voucher_type_id IN (".escape(VoucherType::CRV).", ".escape(VoucherType::BRV).") AND a.coa_id IN (".escape(getConstantCategory()->getBank()).", ".escape(getConstantCategory()->getCashInHand()).") {$MainDuration}) AS current_receipt,
               (SELECT SUM(debit - credit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND voucher_type_id IN (".escape(VoucherType::CRV).", ".escape(VoucherType::BRV).") AND a.coa_id IN (".escape(getConstantCategory()->getBank()).", ".escape(getConstantCategory()->getCashInHand()).") {$CompareDuration}) AS previous_receipt,
               
               
               (SELECT SUM(credit - debit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND voucher_type_id IN (".escape(VoucherType::CPV).", ".escape(VoucherType::BPV).") AND a.coa_id IN (".escape(getConstantCategory()->getBank()).", ".escape(getConstantCategory()->getCashInHand()).") {$MainDuration}) AS current_payment,
               (SELECT SUM(credit - debit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND voucher_type_id IN (".escape(VoucherType::CPV).", ".escape(VoucherType::BPV).") AND a.coa_id IN (".escape(getConstantCategory()->getBank()).", ".escape(getConstantCategory()->getCashInHand()).") {$CompareDuration}) AS previous_payment,
               
               
               (SELECT SUM(credit - debit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND (a.coa_id IN (".DBQM::result($SelectRevenueCOAIds, 0).") OR a.coa_id IN (".DBQM::result($SelectExpenseCOAIds, 0).")) AND a.coa_id <> ".escape(getConstantCategory()->getCostOfGoodSold())." {$MainDuration}) AS current_income,
               (SELECT SUM(credit - debit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND (a.coa_id IN (".DBQM::result($SelectRevenueCOAIds, 0).") OR a.coa_id IN (".DBQM::result($SelectExpenseCOAIds, 0).")) AND a.coa_id <> ".escape(getConstantCategory()->getCostOfGoodSold())." {$CompareDuration}) AS previous_income,
               
               
               (SELECT SUM(did.voucher_total) AS sum_amount FROM pr_purchase p, distinct_id did WHERE p.distinct_id = did.id AND did.is_deleted <> 'Y' {$MainDurationDistinctId}) AS current_purchase,
               (SELECT SUM(did.voucher_total) AS sum_amount FROM pr_purchase p, distinct_id did WHERE p.distinct_id = did.id AND did.is_deleted <> 'Y' {$CompareDurationDistinctId}) AS previous_purchase,
               
               
               (SELECT SUM(debit - credit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND a.coa_id IN (".DBQM::result($SelectReceivableCOAIds, 0).") {$MainDurationTill}) AS current_receivable,
               (SELECT SUM(debit - credit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND a.coa_id IN (".DBQM::result($SelectReceivableCOAIds, 0).") {$CompareDurationTill}) AS previous_receivable,
               
               
               (SELECT SUM(credit - debit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND a.coa_id IN (".DBQM::result($SelectPayableCOAIds, 0).") {$MainDurationTill}) AS current_payable,
               (SELECT SUM(credit - debit) AS sum_amount FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND a.coa_id IN (".DBQM::result($SelectPayableCOAIds, 0).") {$CompareDurationTill}) AS previous_payable
        ";

        $Select = DBQM::query($Query);
        $Row = DBQM::fetch_assoc($Select);

        $oIncomeStatement = new IncomeStatement();
        $oIncomeStatement->setDate(date("m/d/Y", strtotime("-29 days")), date("m/d/Y"));
        list($Opening, $Purchases, $Closing) = $oIncomeStatement->getCOGS();
        $CurrentCOGS = $Opening + $Purchases - $Closing;


        $oIncomeStatement->setDate(date("m/d/Y", strtotime("-60 days")), date("m/d/Y", strtotime("-30 days")));
        list($Opening, $Purchases, $Closing) = $oIncomeStatement->getCOGS();
        $PreviousCOGS = $Opening + $Purchases - $Closing;

        $Row['current_income'] -= $CurrentCOGS;
        $Row['previous_income'] -= $PreviousCOGS;

        return [$Row, $DateFromMain, $DateTillMain, $DateFromCompare, $DateTillCompare];
    }

    function designStats($Name, $CSSClass, $Icon, $CurrentStat, $PreviousStat, $isReverse=false)
    {
        $PercentageChange = 0;
        if (!empty($PreviousStat))
        {
            $ValueChange = $CurrentStat - $PreviousStat;
            $PercentageChange = $PreviousStat > 0 ? round(($ValueChange / $PreviousStat) * 100) : 0;
        }
        elseif (!empty($CurrentStat))
            $PercentageChange = 100;

        $isPositive = ($PercentageChange >= 0);
        $isGood = ($isReverse) ? !$isPositive : $isPositive;
        $trendColor = $isGood ? '#166534' : '#b91c1c';
        $trendBg = $isGood ? 'rgba(22, 163, 74, 0.14)' : 'rgba(220, 38, 38, 0.12)';
        $trendIcon = $isPositive ? 'arrow-up-bold' : 'arrow-down-bold';

        // One colour family per indicator: [solid, soft tint start, soft tint end, text]
        $palette = [
            'Sales'      => ['#1f9d8f', '#e3f6f3', '#f6fcfb', '#0f5f56'],
            'Purchases'  => ['#f59e0b', '#fff3da', '#fffbf1', '#8a5200'],
            'Receipts'   => ['#16a34a', '#e0f6e8', '#f5fcf8', '#14532d'],
            'Payments'   => ['#e11d48', '#ffe4ea', '#fff7f9', '#9f1239'],
            'Income'     => ['#2563eb', '#e1ebff', '#f6f9ff', '#1e3a8a'],
            'Receivable' => ['#0891b2', '#dcf4fa', '#f4fcfe', '#0e5a6e'],
            'Payable'    => ['#ea580c', '#ffe9dc', '#fff8f3', '#9a3412'],
        ];
        $fallback = ['#1f9d8f', '#e3f6f3', '#f6fcfb', '#0f5f56'];
        list($solid, $tintA, $tintB, $textColor) = isset($palette[$Name]) ? $palette[$Name] : $fallback;
        ?>
        <div class="card premium-kpi-card" style="border-radius: 14px; border: 1px solid <?=$solid?>33 !important; background: linear-gradient(135deg, <?=$tintA?> 0%, <?=$tintB?> 100%) !important; box-shadow: 0 4px 16px <?=$solid?>1f !important; overflow: hidden; position: relative; margin-bottom: 1.5rem;">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: <?=$solid?>;"></div>
            <div class="card-body" style="padding: 1.4rem 1.4rem 1.2rem 1.4rem;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: <?=$textColor?>; display: block; margin-bottom: 0.35rem;"><?=$Name?></span>
                        <h3 style="font-size: 1.6rem; font-weight: 800; color: #0b2240; margin: 0;"><?=$CurrentStat > 0 ? Utils::format($CurrentStat, 0) : '0'?></h3>
                    </div>
                    <div style="width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: <?=$solid?>; color: #ffffff; font-size: 1.45rem; box-shadow: 0 6px 14px <?=$solid?>55;">
                        <i class="mdi mdi-<?=$Icon?>"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge" style="font-size: 0.74rem; padding: 0.32rem 0.65rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; border-radius: 30px; background-color: <?=$trendBg?> !important; color: <?=$trendColor?> !important; border: none !important;">
                        <i class="mdi mdi-<?=$trendIcon?>" style="font-size: 0.8rem;"></i>
                        <?=abs($PercentageChange)?>%
                    </span>
                </div>
            </div>
        </div>
        <?php
    }
}