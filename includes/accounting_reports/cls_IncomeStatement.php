<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 007, 1/7/2015
 * Time: 2:13 PM
 */

class IncomeStatement extends AccountingReport
{
    private $TotalExpenses;

    function __construct() {
        parent::__construct();
    }

    function getCOGS()
    {
        $WherePurchases = UtilsQueryMaker::date("l.date_time", $this->DateFrom, $this->DateTill);
        $WhereClosing = UtilsQueryMaker::date("stl.date_time", '', $this->DateTill);

        // Cost center filter: purchases on the ledger, opening / closing stock on the stock ledger (both carry distinct_id)
        $WherePurchases .= self::costCenterSql('l');
        $WhereClosing .= self::costCenterSql('stl');
        $WhereOpening = self::costCenterSql('stl');

        $SelectOpeningStock = DBQM::query("SELECT SUM(stb.cost * stl.qty) FROM st_stock_ledger stl, st_stock_batch stb WHERE stb.id = stl.batch_id AND stl.date_time < ".escape(DateEncode($this->DateFrom))." {$WhereOpening}");
        $OpeningStock = DBQM::result($SelectOpeningStock, 0);

        $SelectPurchases = DBQM::query("SELECT SUM(debit - credit) FROM ledger l, ledger_data ld WHERE l.id = ld.ledger_id AND ld.account_id = ".escape(getConstantAccount()->getPurchaseAccount())." AND l.year_id = ".escape(Year::getYearId())." {$WherePurchases}");
        $Purchases = DBQM::result($SelectPurchases, 0);

        $SelectClosing = DBQM::query("SELECT SUM(stb.cost * stl.qty) FROM st_stock_ledger stl, st_stock_batch stb WHERE stb.id = stl.batch_id {$WhereClosing}");
        $ClosingStock = DBQM::result($SelectClosing, 0);

        return [$OpeningStock, $Purchases, $ClosingStock];
    }

    function getHeads($COAId, $ShowInverseAmount=false, $ExcludeAccounts=[])
    {
        $ArraySubCategories = [];
        $this->TotalExpenses = 0;

        $SelectSubCategories = $this->getAllSubCategories($COAId);
        while($Row = DBQM::fetch_assoc($SelectSubCategories)) {
            $ArraySubCategories[$Row['parent_id']][$Row['id']] = $Row['name'];
        }

        $this->makeRow($ArraySubCategories[$COAId], 0, $ArraySubCategories, $COAId, $ShowInverseAmount, $ExcludeAccounts);

        return $this->TotalExpenses;
    }

    function makeRow($Array, $Count, $ArraySubCategories, $Level1Code, $ShowInverseAmount, $ExcludeAccount=[])
    {
        foreach ($Array as $Key => $Name)
        {
            if (in_array($Key, $ExcludeAccount)) continue;

            $Amount = $this->getAmountByCOAId($Key) * ($ShowInverseAmount ? -1 : 1);
            $this->TotalExpenses += $Amount;

            ?>
            <tr>
                <td><?=str_repeat(" &nbsp;", $Count*3)?>- <a href="expanded_report.php?date_from=<?=$this->DateFrom?>&date_till=<?=$this->DateTill?>&coa_id=<?=$Key?><?=self::costCenterUrlParam()?>"><?=$Name?></a></td>
                <td class="text-right"><?=Utils::format($Amount)?></td>
            </tr>
            <?
            $this->makeRow($ArraySubCategories[$Key], $Count + 1, $ArraySubCategories, $Level1Code, $ShowInverseAmount, $ExcludeAccount);
        }
    }

    function getAmountByCOAId($COAId)
    {
        $Query = "
                    SELECT
                        SUM((ld.debit - ld.credit) * ld.currency_rate) AS amount
                    FROM
                        ledger l,
                        ledger_data ld,
                        acc_accounts a
                    WHERE
                        l.id = ld.ledger_id AND
                        a.id = ld.account_id AND
                        a.coa_id = ".escape($COAId)." AND
                        l.year_id = ".escape(Year::getYearId())." AND
                        l.date_time BETWEEN ".escape($this->EncodedDateFrom)." AND ".escape($this->EncodedDateTill." 23:59:59")."
                        ".self::costCenterSql('l')."
                    GROUP BY
                        a.coa_id
        ";

        $Select = DBQM::query($Query);
        return DBQM::result($Select, 0);
    }
}