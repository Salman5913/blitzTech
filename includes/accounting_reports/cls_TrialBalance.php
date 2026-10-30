<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 007, 1/7/2015
 * Time: 3:59 PM
 */

class TrialBalance extends AccountingReport
{
    private $Count;

    private $TotalParentOpening;
    private $TotalParentDebit;
    private $TotalParentCredit;
    private $TotalParentClosing;

    public $TotalOpening;
    public $TotalDebit;
    public $TotalCredit;
    public $TotalClosing;

    private $ISData = [];

    function __construct() {
        parent::__construct();
    }

    function getAllAccountsByCOAId($COAId)
    {
        $Query = "
                    SELECT
                        SUM(opening) AS opening,
                        SUM(opening_raw) AS opening_raw,
                        SUM(total_debit) AS total_debit,
                        SUM(total_credit) AS total_credit,
                        SUM(total_debit_raw) AS total_debit_raw,
                        SUM(total_credit_raw) AS total_credit_raw,
                        id,
                        ac_name AS name
                    FROM
                    (
                        SELECT
                            0 AS opening,
                            0 AS opening_raw,
                            SUM(ld.debit * ld.currency_rate) AS total_debit,
                            SUM(ld.credit * ld.currency_rate) AS total_credit,
                            SUM(ld.debit) AS total_debit_raw,
                            SUM(ld.credit) AS total_credit_raw,
                            a.id,
                            a.`ac_name`
                        FROM
                            ledger l,
                            ledger_data ld,
                            vw_ac_accounts a
                        WHERE
                            l.id = ld.ledger_id AND
                            a.id = ld.account_id AND
                            a.coa_id = ".escape($COAId)." AND
                            l.year_id = ".escape(Year::getYearId())." AND
                            l.date_time BETWEEN ".escape($this->EncodedDateFrom." 00:00:00")." AND ".escape($this->EncodedDateTill." 23:59:59")."
                            ".self::costCenterSql('l')."
                        GROUP BY
                            a.id

                        UNION

                        SELECT
                            SUM((ld.debit - ld.credit) * ld.currency_rate) AS opening,
                            SUM(ld.debit - ld.credit) AS opening_raw,
                            0 AS total_debit,
                            0 AS total_credit,
                            0 AS total_debit_raw,
                            0 AS total_credit_raw,
                            a.id,
                            a.`ac_name`
                        FROM
                            ledger l,
                            ledger_data ld,
                            vw_ac_accounts a
                        WHERE
                            l.id = ld.ledger_id AND
                            a.id = ld.account_id AND
                            a.coa_id = ".escape($COAId)." AND
                            l.year_id = ".escape(Year::getYearId())." AND
                            l.date_time < ".escape($this->EncodedDateFrom." 00:00:00")."
                            ".self::costCenterSql('l')."
                        GROUP BY
                            a.id
                    ) AS accounts_table
                    GROUP BY
                        id
                    ORDER BY
                        name
        ";

        return DBQM::query($Query);
    }

    function prepareDataNMakeRows($Level1Code, $Level1Name, $isGroupWise, $isZero='')
    {
        $ArraySubCategories = [];

        $SelectSubCategories = $this->getAllSubCategories($Level1Code);
        while($Row = DBQM::fetch_assoc($SelectSubCategories)) {
            $ArraySubCategories[$Row['parent_id']][$Row['id']] = $Row['name'];
        }

        $this->TotalParentOpening = $this->TotalParentDebit = $this->TotalParentCredit = $this->TotalParentClosing = [];

        $this->makeRow($ArraySubCategories[$Level1Code], 0, $ArraySubCategories, $Level1Code, $isGroupWise);

        $this->TotalOpening += array_sum($this->TotalParentOpening[0]);
        $this->TotalDebit += array_sum($this->TotalParentDebit[0]);
        $this->TotalCredit += array_sum($this->TotalParentCredit[0]);
        $this->TotalClosing += array_sum($this->TotalParentClosing[0]);
        ?>
        <tr class="bg-color">
            <td><?=$Level1Name?></td>
            <td class="text-right"><?=Utils::format(array_sum($this->TotalParentOpening[0]))?></td>
            <td class="text-right"><?=Utils::format(array_sum($this->TotalParentDebit[0]))?></td>
            <td class="text-right"><?=Utils::format(array_sum($this->TotalParentCredit[0]))?></td>
            <td class="text-right"><?=Utils::format(array_sum($this->TotalParentClosing[0]))?></td>
        </tr>
        <?
    }

    function makeRow($Array, $Count, $ArraySubCategories, $Level1Code, $isGroupWise)
    {
        foreach ($Array as $COAId => $Name)
        {
            $OpeningStock = $ISDebit = $ISCredit = $ClosingStock = 0;

            /*if ($COAId == 93) continue;*/

            if ($COAId == getConstantCategory()->getCurrentAsset())
            {
                $oIncomeStatement = new IncomeStatement();
                $oIncomeStatement->setDate($this->DateFrom, $this->DateTill);
                list($OpeningStock, $Purchases, $ClosingStock) = $oIncomeStatement->getCOGS();

                $Difference = $ClosingStock - $OpeningStock;
                $ISDebit = $Difference > 0 ? $Difference : 0;
                $ISCredit = $Difference < 0 ? abs($Difference) : 0;

                $this->ISData['opening'] = $OpeningStock;
                $this->ISData['purchases'] = $Purchases;
                $this->ISData['closing'] = $ClosingStock;
                $this->ISData['cogs'] = $OpeningStock + $Purchases - $ClosingStock;

                ?>
                <tr class="<?=(!$isGroupWise) ? 'bg-success text-white' : '' ?>">
                    <td><?=str_repeat(" &nbsp;", ($Count+1)*3)?><i class="mdi mdi-file-tree"></i> Stock In Hand</td>
                    <td class="text-right"><?=Utils::format($OpeningStock)?></td>
                    <td class="text-right"><?=Utils::format($ISDebit)?></td>
                    <td class="text-right"><?=Utils::format($ISCredit)?></td>
                    <td class="text-right"><?=Utils::format($ClosingStock)?></td>
                </tr>
                <?php
            }

            $TotalOpening = $TotalDebit = $TotalCredit = $TotalClosing = 0;

            if ($COAId == getConstantCategory()->getCostOfGoodSold())
            {
                $oIncomeStatement = new IncomeStatement();
                $oIncomeStatement->setDate(Settings::getOpeningDateTime(), $this->DateFrom);
                list($OpeningStock, $Purchases, $ClosingStock) = $oIncomeStatement->getCOGS();

                $OpeningCOGS = $OpeningStock + $Purchases - $ClosingStock;

                $TotalOpening += $OpeningCOGS;
                $TotalDebit += $this->ISData['cogs'] > 0 ? $this->ISData['cogs'] : 0;
                $TotalCredit += $this->ISData['cogs'] < 0 ? abs($this->ISData['cogs']) : 0;
                $TotalClosing += $OpeningCOGS + $this->ISData['cogs'];
            }
            else
            {
                $this->makeRow($ArraySubCategories[$COAId], $Count + 1, $ArraySubCategories, $Level1Code, $isGroupWise);

                list($TotalOpening, $TotalDebit, $TotalCredit, $TotalClosing) = $this->makeTransactionalAccountRows($COAId, $Count, $isGroupWise);

                $TotalOpening += $OpeningStock;
                $TotalDebit += $ISDebit;
                $TotalCredit += $ISCredit;
                $TotalClosing += $ClosingStock;
            }


            /**
             * Display SUM of transaction accounts of current category as well as all children categories
             */
            $this->TotalParentOpening[$Count][] = $TotalOpening;
            $this->TotalParentDebit[$Count][] = $TotalDebit;
            $this->TotalParentCredit[$Count][] = $TotalCredit;
            $this->TotalParentClosing[$Count][] = $TotalClosing;

            /**
             * If has child depth, convert all child sums to parent and remove child
             */
            if (isset($this->TotalParentDebit[$Count+1]))
            {
                $SumOpening = array_sum($this->TotalParentOpening[$Count+1]);
                $SumDebit = array_sum($this->TotalParentDebit[$Count+1]);
                $SumCredit = array_sum($this->TotalParentCredit[$Count+1]);
                $SumClosing = array_sum($this->TotalParentClosing[$Count+1]);

                $this->TotalParentOpening[$Count][] = $SumOpening;
                $this->TotalParentDebit[$Count][] = $SumDebit;
                $this->TotalParentCredit[$Count][] = $SumCredit;
                $this->TotalParentClosing[$Count][] = $SumClosing;

                $TotalOpening += $SumOpening;
                $TotalDebit += $SumDebit;
                $TotalCredit += $SumCredit;
                $TotalClosing += $SumClosing;

                unset($this->TotalParentOpening[$Count+1]);
                unset($this->TotalParentDebit[$Count+1]);
                unset($this->TotalParentCredit[$Count+1]);
                unset($this->TotalParentClosing[$Count+1]);
            }
            ?>
            <tr class="<?=(!$isGroupWise) ? 'bg-success text-white' : '' ?>">
                <td><?=str_repeat(" &nbsp;", $Count*3)?><i class="mdi mdi-file-tree"></i> <a class="<?=(!$isGroupWise) ? 'text-white' : '' ?>" href="expanded_report.php?date_from=<?=empty($this->DateFrom) ? Settings::getOpeningDateTime() : $this->DateFrom?>&date_till=<?=$this->DateTill?>&coa_id=<?=$COAId?><?=self::costCenterUrlParam()?>"><?=$Name?></a></td>
                <td class="text-right"><?=Utils::format($TotalOpening)?></td>
                <td class="text-right"><?=Utils::format($TotalDebit)?></td>
                <td class="text-right"><?=Utils::format($TotalCredit)?></td>
                <td class="text-right"><?=Utils::format($TotalClosing)?></td>
            </tr>
            <?
        }
    }

    function makeTransactionalAccountRows($COAId, $Count, $isGroupWise)
    {
        $TotalOpening = $TotalDebit = $TotalCredit = $TotalClosing = 0;

        $SelectAccounts = $this->getAllAccountsByCOAId($COAId);
        while ($RowAccounts = DBQM::fetch_assoc($SelectAccounts))
        {
            $Closing = $RowAccounts['opening'] + $RowAccounts['total_debit'] - $RowAccounts['total_credit'];
            $ClosingRaw = $RowAccounts['opening_raw'] + $RowAccounts['total_debit_raw'] - $RowAccounts['total_credit_raw'];

            $TotalOpening += $RowAccounts['opening'];
            $TotalDebit += $RowAccounts['total_debit'];
            $TotalCredit += $RowAccounts['total_credit'];
            $TotalClosing += $Closing;

            if (!$isGroupWise)
            {
                ?>
                <tr>
                    <td><?=str_repeat(" &nbsp;", ($Count+1)*3)?><i class="mdi mdi-dots-horizontal"></i> <a href="ledger.php?date_from=<?=$this->DateFrom?>&date_till=<?=$this->DateTill?>&account_id=<?=$RowAccounts['id']?><?=self::costCenterUrlParam()?>&search="><?=$RowAccounts['name']?></a></td>
                    <td class="text-right" title="Ledger Opening: <?=Utils::format($RowAccounts['opening_raw'])?>"><?=Utils::format($RowAccounts['opening'])?></td>
                    <td class="text-right" title="Ledger Debit: <?=Utils::format($RowAccounts['total_debit_raw'])?>"><?=Utils::format($RowAccounts['total_debit'])?></td>
                    <td class="text-right" title="Ledger Credit: <?=Utils::format($RowAccounts['total_credit_raw'])?>"><?=Utils::format($RowAccounts['total_credit'])?></td>
                    <td class="text-right" title="Ledger Closing: <?=Utils::format($ClosingRaw)?>"><?=Utils::format($Closing)?></td>
                </tr>
                <?
            }
        }

        return [$TotalOpening, $TotalDebit, $TotalCredit, $TotalClosing];
    }
}