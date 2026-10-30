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

    /**
     * Dashboard indicators for a date range (default: this month till today), each with the same length period before it:
     * website sales (SIV), sales (OIV / POS), purchases, receipts, payments and gross profit on sales (sale price - cost, less returns).
     * @return array [Row with current_* and previous_*, DateFrom timestamp, DateTill timestamp, compare from, compare till]
     */
    function getIndicatorsData($DateFrom, $DateTill)
    {
        $From = $DateFrom != '' ? strtotime(DateEncode($DateFrom)) : strtotime(date('Y-m-01'));
        $Till = $DateTill != '' ? strtotime(DateEncode($DateTill)) : strtotime(date('Y-m-d'));
        if ($Till < $From)
            list($From, $Till) = [$Till, $From];

        $Days = (int)round(($Till - $From) / 86400) + 1;
        $CompareTill = strtotime("-1 day", $From);
        $CompareFrom = strtotime("-" . ($Days - 1) . " days", $CompareTill);

        $CashBank = escape(getConstantCategory()->getBank()) . ", " . escape(getConstantCategory()->getCashInHand());

        $Row = [];
        foreach (['current' => [$From, $Till], 'previous' => [$CompareFrom, $CompareTill]] as $Key => list($PeriodFrom, $PeriodTill))
        {
            $PeriodFrom = date("m/d/Y", $PeriodFrom);
            $PeriodTill = date("m/d/Y", $PeriodTill);
            $Ledger = UtilsQueryMaker::date("l.date_time", $PeriodFrom, $PeriodTill);
            $Voucher = UtilsQueryMaker::date("did.voucher_date", $PeriodFrom, $PeriodTill);
            $StockLedger = UtilsQueryMaker::date("stl.date_time", $PeriodFrom, $PeriodTill);

            $Values = DBQM::fetch_assoc(DBQM::query("
                SELECT
                    (SELECT SUM(did.voucher_total) FROM sl_sales s, distinct_id did WHERE s.distinct_id = did.id AND did.is_deleted <> 'Y' AND did.voucher_type_id = " . escape(VoucherType::SIV) . " {$Voucher}) AS website_sales,
                    (SELECT SUM(did.voucher_total) FROM sl_sales s, distinct_id did WHERE s.distinct_id = did.id AND did.is_deleted <> 'Y' AND did.voucher_type_id = " . escape(VoucherType::OIV) . " {$Voucher}) AS sales,
                    (SELECT SUM(did.voucher_total) FROM pr_purchase p, distinct_id did WHERE p.distinct_id = did.id AND did.is_deleted <> 'Y' {$Voucher}) AS purchase,
                    (SELECT SUM(debit - credit) FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND voucher_type_id IN (" . escape(VoucherType::CRV) . ", " . escape(VoucherType::BRV) . ") AND a.coa_id IN ({$CashBank}) {$Ledger}) AS receipt,
                    (SELECT SUM(credit - debit) FROM ledger l, ledger_data ld, acc_accounts a WHERE l.id = ld.ledger_id AND ld.account_id = a.id AND voucher_type_id IN (" . escape(VoucherType::CPV) . ", " . escape(VoucherType::BPV) . ") AND a.coa_id IN ({$CashBank}) {$Ledger}) AS payment,
                    (SELECT SUM(stl.qty * (stl.sp - stb.cost) * -1) FROM st_stock_batch stb, st_stock_ledger stl WHERE stb.id = stl.batch_id AND stl.voucher_type_id IN (" . VoucherType::getSalesVoucherTypes() . ", " . escape(VoucherType::SRV) . ") {$StockLedger}) AS profit
            "));

            foreach ($Values as $Name => $Value)
                $Row[$Key . '_' . $Name] = (float)$Value;
        }

        return [$Row, $From, $Till, $CompareFrom, $CompareTill];
    }

    /**
     * Indicator card filled with a gradient
     * @param string $Gradient green, blue, warning, teal, danger or purple
     */
    function designGradientStat($Name, $Gradient, $Icon, $CurrentStat, $PreviousStat, $Note = '', $isReverse = false)
    {
        $Gradients = [
            'green'   => ['#16a34a', '#22c55e', '#0d9488'],
            'blue'    => ['#1d4ed8', '#3b82f6', '#0ea5e9'],
            'warning' => ['#d97706', '#f59e0b', '#f97316'],
            'teal'    => ['#0f766e', '#14b8a6', '#06b6d4'],
            'danger'  => ['#be123c', '#e11d48', '#f43f5e'],
            'purple'  => ['#6d28d9', '#8b5cf6', '#c026d3'],
        ];
        list($ColorA, $ColorB, $ColorC) = $Gradients[$Gradient] ?? $Gradients['blue'];

        $PercentageChange = 0;
        if (!empty($PreviousStat))
            $PercentageChange = round((($CurrentStat - $PreviousStat) / abs($PreviousStat)) * 100);
        elseif (!empty($CurrentStat))
            $PercentageChange = 100;

        $isUp = $PercentageChange >= 0;
        $isGood = $PercentageChange == 0 || ($isReverse ? !$isUp : $isUp);
        ?>
        <?/* not a .card: the theme (finac-theme.css) forces a plain background on every .card */?>
        <div class="gradient-kpi-card" style="background: linear-gradient(135deg, <?=$ColorA?> 0%, <?=$ColorB?> 55%, <?=$ColorC?> 100%); box-shadow: 0 8px 22px <?=$ColorB?>4d;">
            <div class="gradient-kpi-glow"></div>
            <div class="gradient-kpi-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="gradient-kpi-text">
                        <span class="gradient-kpi-name"><?=$Name?></span>
                        <h3 class="gradient-kpi-value"><?=Utils::format($CurrentStat, 0)?></h3>
                        <? if ($Note) { ?><small class="gradient-kpi-note"><?=$Note?></small><? } ?>
                    </div>
                    <div class="gradient-kpi-icon"><i class="mdi mdi-<?=$Icon?>"></i></div>
                </div>
                <div class="gradient-kpi-trend" title="Compared with the same number of days before">
                    <span class="<?=$isGood ? 'good' : 'bad'?>"><i class="mdi mdi-<?=$isUp ? 'arrow-up-bold' : 'arrow-down-bold'?>"></i><?=abs($PercentageChange)?>%</span>
                    vs <?=Utils::format($PreviousStat, 0)?>
                </div>
            </div>
        </div>
        <?php
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