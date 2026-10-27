<?
$_GET['_page_title'] = ucwords($_GET['type'])." Aging Report";
$_GET['_page_title_text'] = "View Aging of an Account";

include_once("../includes/inc_header.php");

$oLedgerReport = new LedgerReport();

if (isset($_GET['search']))
    $SelectAccounts = $oLedgerReport->getAllAccountsByCOAId($_GET['coa_id'], $_GET['account_id']);
?>
<style>
    table{
        font-size: 14px;
        font-weight: bolder;
    }
</style>
    <form action="" method="get" <? Utils::formValidate() ?>>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <h3 class="card-title">Filter Options</h3>
                    <div class="row">
                        <div class="col-xl-5 col-md-6">
                            <div class="form-group">
                                <label for="coa_id">COA Category</label>
                                <input type="text" id="coa_id" name="coa_id" <? Autocomplete::ACSearchCOA($_GET['coa_id']); ?> >
                            </div>
                        </div>
                        <div class="col-xl-5 col-md-6">
                            <div class="form-group">
                                <label for="account_id">Account Name</label>
                                <input type="text" id="account_id" name="account_id" <? Autocomplete::ACSearchAccount($_GET['account_id']); ?>>
                            </div>
                        </div>
                        <div class="col-xl-5 col-md-6">
                            <div class="form-group">
                                <label>Date Till</label>
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] =='' ? '' : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                        <div class="col-xl-2 text-right">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn button color form-control" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="type" value="<?=$_GET['type']?>">
    </form>
    <div class="report_complete">
        <div class="row">
            <div class="col-md-12 text-right mb-3">
                <a href="print_aging.php?coa_id=<?=$_GET['coa_id']?>&account_id=<?=$_GET['account_id']?>&type=<?=$_GET['type']?>" class="btn color button btn-sm" target="_blank"><i class="mdi mdi-printer"></i> Print</a>
            </div>
        </div>
        <?

        while($RowAccounts = DBQM::fetch_assoc($SelectAccounts)) {
            $oAging = new Aging($RowAccounts['id'], $_GET['type']);

            if (!$oAging->hasAgingData()) continue;
            ?>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <h3 class="card-title text-uppercase"><?=ChartOfAccount::getName($RowAccounts['id'])?></h3>
                        <div class="row">
                            <div class="col-md-4">
                                <?php
                                $Select = $oAging->getAging(0, 7, $_GET['date_till']);
                                ?>
                                <h5>0-7 Days</h5>
                                <div class="table-responsive">
                                    <table class="table color table-striped">
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Inv#</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Remaining</th>
                                        </tr>
                                        </thead>
                                        <?
                                        $TotalAmount = 0;
                                        $TotalRemaining = 0;

                                        while ($Row = DBQM::fetch_assoc($Select))
                                        {
                                            $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                            $TotalRemaining += $Remaining;

                                            if ($Row['sum_amount'] > 0)
                                                $TotalAmount += $Row['sum_amount'];

                                            if ($Remaining != 0)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?><br><small><?=$oAging->getDaysPassed($Row['voucher_date'])?> Days Old</small></td>
                                                    <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                    <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                    <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                </tr>
                                                <?
                                            }
                                        }
                                        ?>
                                        <tfoot>
                                        <tr class="font-weight-bold">
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td class="text-right"><?=Utils::format($TotalAmount, 2); ?></td>
                                            <td class="text-right"><?=Utils::format($TotalRemaining, 2); ?></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <?php
                                $Select = $oAging->getAging(8, 14);
                                ?>
                                <h5>8-14 Days</h5>
                                <div class="table-responsive">
                                    <table class="table color table-striped">
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Inv#</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Remaining</th>
                                        </tr>
                                        </thead>
                                        <?
                                        $TotalAmount = 0;
                                        $TotalRemaining = 0;

                                        while ($Row = DBQM::fetch_assoc($Select))
                                        {
                                            $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                            $TotalRemaining += $Remaining;

                                            if ($Row['sum_amount'] > 0)
                                                $TotalAmount += $Row['sum_amount'];

                                            if ($Remaining != 0)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?><br><small><?=$oAging->getDaysPassed($Row['voucher_date'])?> Days Old</small></td>
                                                    <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                    <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                    <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                </tr>
                                                <?
                                            }
                                        }
                                        ?>
                                        <tfoot>
                                        <tr class="font-weight-bold">
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td class="text-right"><?=Utils::format($TotalAmount, 2); ?></td>
                                            <td class="text-right"><?=Utils::format($TotalRemaining, 2); ?></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <?php
                                $Select = $oAging->getAging(15, 21);
                                ?>
                                <h5>15-21 Days</h5>
                                <div class="table-responsive">
                                    <table class="table color table-striped">
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Inv#</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Remaining</th>
                                        </tr>
                                        </thead>
                                        <?
                                        $TotalAmount = 0;
                                        $TotalRemaining = 0;

                                        while ($Row = DBQM::fetch_assoc($Select))
                                        {
                                            $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                            $TotalRemaining += $Remaining;
                                            if ($Row['sum_amount'] > 0)
                                                $TotalAmount += $Row['sum_amount'];

                                            if ($Remaining != 0)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?><br><small><?=$oAging->getDaysPassed($Row['voucher_date'])?> Days Old</small></td>
                                                    <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                    <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                    <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                </tr>
                                                <?
                                            }
                                        }
                                        ?>
                                        <tfoot>
                                        <tr class="font-weight-bold">
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td class="text-right"><?=Utils::format($TotalAmount, 0); ?></td>
                                            <td class="text-right"><?=Utils::format($TotalRemaining, 0); ?></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <?php
                                $Select = $oAging->getAging(22, 28);
                                ?>
                                <h5>22-28 Days</h5>
                                <div class="table-responsive">
                                    <table class="table color table-striped">
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Inv#</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Remaining</th>
                                        </tr>
                                        </thead>
                                        <?
                                        $TotalAmount = 0;
                                        $TotalRemaining = 0;

                                        while ($Row = DBQM::fetch_assoc($Select))
                                        {
                                            $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                            $TotalRemaining += $Remaining;
                                            if ($Row['sum_amount'] > 0)
                                                $TotalAmount += $Row['sum_amount'];

                                            if ($Remaining != 0)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?><br><small><?=$oAging->getDaysPassed($Row['voucher_date'])?> Days Old</small></td>
                                                    <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                    <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                    <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                </tr>
                                                <?
                                            }
                                        }
                                        ?>
                                        <tfoot>
                                        <tr class="font-weight-bold">
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td class="text-right"><?=Utils::format($TotalAmount, 0); ?></td>
                                            <td class="text-right"><?=Utils::format($TotalRemaining, 0); ?></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <?php
                                $Select = $oAging->getAging(29, 45);
                                ?>
                                <h5>28-45 Days</h5>
                                <div class="table-responsive">
                                    <table class="table color table-striped">
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Inv#</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Remaining</th>
                                        </tr>
                                        </thead>
                                        <?
                                        $TotalAmount = 0;
                                        $TotalRemaining = 0;

                                        while ($Row = DBQM::fetch_assoc($Select))
                                        {
                                            $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                            $TotalRemaining += $Remaining;
                                            if ($Row['sum_amount'] > 0)
                                                $TotalAmount += $Row['sum_amount'];

                                            if ($Remaining != 0)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?><br><small><?=$oAging->getDaysPassed($Row['voucher_date'])?> Days Old</small></td>
                                                    <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                    <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                    <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                </tr>
                                                <?
                                            }
                                        }
                                        ?>
                                        <tfoot>
                                        <tr class="font-weight-bold">
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td class="text-right"><?=Utils::format($TotalAmount, 0); ?></td>
                                            <td class="text-right"><?=Utils::format($TotalRemaining, 0); ?></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <?php
                                $Select = $oAging->getAging(46, 60);
                                ?>
                                <h5>45-60 Days</h5>
                                <div class="table-responsive">
                                    <table class="table color table-striped">
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Inv#</th>
                                            <th>Reference</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Remaining</th>
                                        </tr>
                                        </thead>
                                        <?
                                        $TotalAmount = 0;
                                        $TotalRemaining = 0;

                                        while ($Row = DBQM::fetch_assoc($Select))
                                        {
                                            $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                            $TotalRemaining += $Remaining;
                                            if ($Row['sum_amount'] > 0)
                                                $TotalAmount += $Row['sum_amount'];

                                            if ($Remaining != 0)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?><br><small><?=$oAging->getDaysPassed($Row['voucher_date'])?> Days Old</small></td>
                                                    <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                    <td class="font-weight-bold"><?=$Row['voucher_desc']?></td>
                                                    <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                    <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                </tr>
                                                <?
                                            }
                                        }
                                        ?>
                                        <tfoot>
                                        <tr class="font-weight-bold">
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td class="text-right"><?=Utils::format($TotalAmount, 2); ?></td>
                                            <td class="text-right"><?=Utils::format($TotalRemaining, 2); ?></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <?php
                                $Select = $oAging->getAging(61, 90);
                                ?>
                                <h5>60-90 Days</h5>
                                <div class="table-responsive">
                                    <table class="table color table-striped">
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Inv#</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Remaining</th>
                                        </tr>
                                        </thead>
                                        <?
                                        $TotalAmount = 0;
                                        $TotalRemaining = 0;

                                        while ($Row = DBQM::fetch_assoc($Select))
                                        {
                                            $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                            $TotalRemaining += $Remaining;
                                            if ($Row['sum_amount'] > 0)
                                                $TotalAmount += $Row['sum_amount'];

                                            if ($Remaining != 0)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?><br><small><?=$oAging->getDaysPassed($Row['voucher_date'])?> Days Old</small></td>
                                                    <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                    <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                    <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                </tr>
                                                <?
                                            }
                                        }
                                        ?>
                                        <tfoot>
                                        <tr class="font-weight-bold">
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td class="text-right"><?=Utils::format($TotalAmount, 2); ?></td>
                                            <td class="text-right"><?=Utils::format($TotalRemaining, 2); ?></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <?php
                                $Select = $oAging->getAging(91, 8000);
                                ?>
                                <h5>90+ Days</h5>
                                <div class="table-responsive">
                                    <table class="table color table-striped">
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Inv#</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Remaining</th>
                                        </tr>
                                        </thead>
                                        <?
                                        $TotalAmount = 0;
                                        $TotalRemaining = 0;

                                        while ($Row = DBQM::fetch_assoc($Select))
                                        {
                                            $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                            $TotalRemaining += $Remaining;
                                            if ($Row['sum_amount'] > 0)
                                                $TotalAmount += $Row['sum_amount'];

                                            if ($Remaining != 0)
                                            {
                                                ?>
                                                <tr>
                                                    <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?><br><small><?=$oAging->getDaysPassed($Row['voucher_date'])?> Days Old</small></td>
                                                    <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                    <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                    <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                </tr>
                                                <?
                                            }
                                        }
                                        ?>
                                        <tfoot>
                                        <tr class="font-weight-bold">
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td class="text-right"><?=Utils::format($TotalAmount, 2); ?></td>
                                            <td class="text-right"><?=Utils::format($TotalRemaining, 2); ?></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <? } ?>
    </div>
<? UtilsPrint::init(DateEncode(DatabaseHelper::NOW(), 'd/m/Y')) ?>
<? include_once("../includes/inc_footer.php"); ?>