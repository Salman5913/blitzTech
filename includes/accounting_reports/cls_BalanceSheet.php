<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 014, 1/14/2015
 * Time: 10:19 AM
 */

class BalanceSheet extends AccountingReport
{
    private $Level1Total = 0;
    private $COGS;
    private $ClosingStock;

    function __construct() {
        parent::__construct();
    }

    function init()
    {
        $oIncomeStatement = new IncomeStatement();
        $oIncomeStatement->setDate(Settings::getOpeningDateTime(), $this->DateTill);
        list($OpeningStock, $Purchases, $this->ClosingStock) = $oIncomeStatement->getCOGS();

        $this->COGS = $OpeningStock + $Purchases - $this->ClosingStock;
    }

    function getAllLevel1()
    {
        return [1 => "Asset", "Liability", "Equity"];
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
                        l.date_time <= ".escape($this->EncodedDateTill." 23:59:59")." AND
                        l.year_id = ".escape(Year::getYearId())."
                        ".self::costCenterSql('l')."
                    GROUP BY
                        a.coa_id
        ";

        $Select = DBQM::query($Query);
        return DBQM::result($Select, 0);
    }

    function getProfitForTheYear()
    {
        $SelectChildIds = DBQM::query("SELECT get_children_ids_full(4), get_children_ids_full(5)");
        $ChildIds = array_merge(explode(",", DBQM::result($SelectChildIds, 0)), explode(",", DBQM::result($SelectChildIds, 0, 1)));

        $Select = DBQM::query( "
                    SELECT
                        SUM((ld.debit - ld.credit) * ld.currency_rate) AS amount
                    FROM
                        ledger l,
                        ledger_data ld,
                        acc_accounts a,
                        acc_chart_of_account coa
                    WHERE
                        l.id = ld.ledger_id AND
                        a.id = ld.account_id AND
                        coa.id = a.coa_id AND
                        a.coa_id <> ".escape(getConstantCategory()->getCostOfGoodSold())." AND  
                        coa.id IN (".implode(", ", array_map("escape", $ChildIds)).") AND
                        l.date_time <= ".escape($this->EncodedDateTill." 23:59:59")." AND
                        l.year_id = ".escape(Year::getYearId())."
                        ".self::costCenterSql('l')."
        ");

        return ((DBQM::result($Select, 0) * -1) - ($this->COGS));
    }

    function prepareDataNMakeRows($Level1Code)
    {
        $this->Level1Total = 0;
        $ArraySubCategories = [];

        $SelectSubCategories = $this->getAllSubCategories($Level1Code);
        while($Row = DBQM::fetch_assoc($SelectSubCategories)) {
            $ArraySubCategories[$Row['parent_id']][$Row['id']] = $Row['name'];
        }

        $this->makeRow($ArraySubCategories[$Level1Code], 0, $ArraySubCategories, $Level1Code);

        return $this->Level1Total;
    }

    function makeRow($Array, $Count, $ArraySubCategories, $Level1Code)
    {
        foreach ($Array as $Key => $Name)
        {
            $Amount = $this->getAmountByCOAId($Key);

            /**
             * If Level 1 is liability or equity, inverse the amount
             */
            if ($Level1Code == 2 or $Level1Code == 3)
                $Amount *= -1;

            $this->Level1Total += $Amount;

            ?>
            <tr>
                <td><?=str_repeat(" &nbsp;", $Count*3)?><i class="mdi mdi-dots-horizontal"></i> <a href="expanded_report.php?date_from=<?=date("Y-m-d H:i:s", strtotime(Year::getOpeningDate()." -1 sec"))?>&date_till=<?=$this->DateTill?>&coa_id=<?=$Key?><?=self::costCenterUrlParam()?>"><?=$Name?></a></td>
                <td width="10%" class="text-right"><?=Utils::format($Amount)?></td>
            </tr>
            <?
            $this->makeRow($ArraySubCategories[$Key], $Count + 1, $ArraySubCategories, $Level1Code);

            if ($Key == getConstantCategory()->getCurrentAsset())
            {
                $this->Level1Total += $this->ClosingStock;
                ?>
                <tr>
                    <td><?=str_repeat(" &nbsp;", ($Count + 1)*3)?><i class="mdi mdi-dots-horizontal"></i> Stock In Hand</td>
                    <td width="10%" class="text-right"><?=Utils::format($this->ClosingStock)?></td>
                </tr>
                <?php
            }
        }
    }
}