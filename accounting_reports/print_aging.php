<?
include_once("../connection/localhost.php");

$oLedgerReport = new LedgerReport();
$SelectAccounts = $oLedgerReport->getAllAccountsByCOAId($_GET['coa_id'], $_GET['account_id']);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Aging Print </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?=ProjectHelper::SiteURL()?>assets/images/favicon.ico">
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/bootstrap.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/app.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>/assets/css/print.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="<?=ProjectHelper::SiteURL()?>assets/css/app-font.css?v=1" rel="stylesheet" type="text/css" />
</head>
<body data-layout="detached" data-topbar="colored" id="sales_print">
<style>
    @media print{@page {size: landscape}}
    table th, table td{
        font-size: 12px;
        font-weight: normal !important;
    }
    .aging-print{
        height: auto;
        width: 35cm !important;
        font-size: 13px;
        zoom: 95%;
    }
    .text-account{
        font-size: 17px;
        font-weight: bolder;
        border: 2px solid;
        /*page-break-after: always;*/
    }
    .table-border td, .table-border th{
        padding: 4px;
        border: 1px solid;
    }
    .text-days {
        font-size: 17px;
        font-weight: bolder;
        text-align: center;
    }
    .space-30 {
        height: 30px;
    }
</style>
<div class="row justify-content-center print-container">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body">
                <div class="aging-print">
                    <div class="row">
                        <div class="col-md-12">
                            <img src="../assets/images/blitztech.png" class="img-fluid" style="max-width: 160px;">
                        </div>
                    </div>
                    <?
                    while($RowAccounts = DBQM::fetch_assoc($SelectAccounts)) { $oAging = new Aging($RowAccounts['id'], $_GET['type']);
                        if (!$oAging->hasAgingData()) continue;
                        ?>
                        <table width="100%">
                            <tr>
                                <td colspan="15"><div class="space-30"></td>
                            </tr>
                            <tr>
                                <td colspan="15" class="text-center"><div class="text-account"><?=ChartOfAccount::getName($RowAccounts['id']).AccountingReport::costCenterHeading()?></div></td>
                            </tr>
                            <tr>
                                <td colspan="15"><div class="space-30"></td>
                            </tr>
                        </table>
                        <!--<div class="space-30">-->
                        <div class="row">
                            <?php
                            $Select = $oAging->getAging(0, 7, $_GET['date_till']);
                            $Select2 = $oAging->getAging(8, 14, $_GET['date_till']);
                            $Select3 = $oAging->getAging(15, 21, $_GET['date_till']);
                            $Select4 = $oAging->getAging(22, 28, $_GET['date_till']);
                            $Select5 = $oAging->getAging(29, 45, $_GET['date_till']);
                            $Select6 = $oAging->getAging(46, 60, $_GET['date_till']);
                            $Select7 = $oAging->getAging(61, 90, $_GET['date_till']);
                            $Select8 = $oAging->getAging(91, 8000, $_GET['date_till']);

                            $TotalAmount = 0;
                            $TotalRemaining = 0;
                            $TotalRemaining2 = 0;
                            $TotalRemaining3 = 0;
                            $TotalRemaining4 = 0;
                            $TotalRemaining5 = 0;
                            $TotalRemaining6 = 0;
                            $TotalRemaining7 = 0;
                            $TotalRemaining8 = 0;
                            ?>
                            <div class="col-md-12">
                                <table class="table-border" width="100%">
                                    <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Dated</th>
                                        <th>Days</th>
                                        <th class="text-right">Amount</th>
                                        <th class="text-right">Received</th>
                                        <th class="text-right">0-7 Days</th>
                                        <th class="text-right">8-14 Days</th>
                                        <th class="text-right">15-21 Days</th>
                                        <th class="text-right">22-28 Days</th>
                                        <th class="text-right">28-45 Days</th>
                                        <th class="text-right">45-60 Days</th>
                                        <th class="text-right">60-90 Days</th>
                                        <th class="text-right">90+ Days</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                    </thead>
                                    <?
                                    while ($Row = DBQM::fetch_assoc($Select))
                                    {
                                        $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                        $TotalRemaining += $Remaining;

                                        if ($Row['sum_amount'] > 0)
                                            $TotalAmount += $Row['sum_amount'];

                                        if ($Remaining != 0)
                                        {
                                            $Received = $Row['sum_amount'] - $Remaining;
                                            $TotalReceived += $Received;
                                            ?>
                                            <tr>
                                                <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?>
                                                    <!--<br><small><?php /*=$oAging->getDaysPassed($Row['voucher_date'])*/?> Days Old</small>-->
                                                </td>
                                                <td><?=$oAging->getDaysPassed($Row['voucher_date'])?></td>
                                                <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                <td class="text-right"><?=Utils::format($Received, 2); ?></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right "><?=Utils::format($Remaining, 2); ?></td>
                                            </tr>
                                            <?
                                        }
                                    }
                                    ?>
                                    <?
                                    while ($Row = DBQM::fetch_assoc($Select2))
                                    {
                                        $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                        $TotalRemaining2 += $Remaining;

                                        if ($Row['sum_amount'] > 0)
                                            $TotalAmount += $Row['sum_amount'];

                                        if ($Remaining != 0)
                                        {
                                            $Received = $Row['sum_amount'] - $Remaining;
                                            $TotalReceived += $Received;
                                            ?>
                                            <tr>
                                                <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?>
                                                    <!--<br><small><?php /*=$oAging->getDaysPassed($Row['voucher_date'])*/?> Days Old</small>-->
                                                </td>
                                                <td><?=$oAging->getDaysPassed($Row['voucher_date'])?></td>
                                                <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                <td class="text-right"><?=Utils::format($Received, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                            </tr>
                                            <?
                                        }
                                    }
                                    ?>
                                    <?
                                    while ($Row = DBQM::fetch_assoc($Select3))
                                    {
                                        $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                        $TotalRemaining3 += $Remaining;
                                        if ($Row['sum_amount'] > 0)
                                            $TotalAmount += $Row['sum_amount'];

                                        if ($Remaining != 0)
                                        {
                                            $Received = $Row['sum_amount'] - $Remaining;
                                            $TotalReceived += $Received;
                                            ?>
                                            <tr>
                                                <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?>
                                                    <!--<br><small><?php /*=$oAging->getDaysPassed($Row['voucher_date'])*/?> Days Old</small>-->
                                                </td>
                                                <td><?=$oAging->getDaysPassed($Row['voucher_date'])?></td>
                                                <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                <td class="text-right"><?=Utils::format($Received, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                            </tr>
                                            <?
                                        }
                                    }
                                    ?>
                                    <?
                                    while ($Row = DBQM::fetch_assoc($Select4))
                                    {
                                        $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                        $TotalRemaining4 += $Remaining;
                                        if ($Row['sum_amount'] > 0)
                                            $TotalAmount += $Row['sum_amount'];

                                        if ($Remaining != 0)
                                        {
                                            $Received = $Row['sum_amount'] - $Remaining;
                                            $TotalReceived += $Received;
                                            ?>
                                            <tr>
                                                <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?>
                                                    <!--<br><small><?php /*=$oAging->getDaysPassed($Row['voucher_date'])*/?> Days Old</small>-->
                                                </td>
                                                <td><?=$oAging->getDaysPassed($Row['voucher_date'])?></td>
                                                <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                <td class="text-right"><?=Utils::format($Received, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                            </tr>
                                            <?
                                        }
                                    }
                                    ?>
                                    <?
                                    while ($Row = DBQM::fetch_assoc($Select5))
                                    {
                                        $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                        $TotalRemaining5 += $Remaining;
                                        if ($Row['sum_amount'] > 0)
                                            $TotalAmount += $Row['sum_amount'];

                                        if ($Remaining != 0)
                                        {
                                            $Received = $Row['sum_amount'] - $Remaining;
                                            $TotalReceived += $Received;
                                            ?>
                                            <tr>
                                                <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?>
                                                    <!--<br><small><?php /*=$oAging->getDaysPassed($Row['voucher_date'])*/?> Days Old</small>-->
                                                </td>
                                                <td><?=$oAging->getDaysPassed($Row['voucher_date'])?></td>
                                                <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                <td class="text-right"><?=Utils::format($Received, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                            </tr>
                                            <?
                                        }
                                    }
                                    ?>
                                    <?
                                    while ($Row = DBQM::fetch_assoc($Select6))
                                    {
                                        $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                        $TotalRemaining6 += $Remaining;
                                        if ($Row['sum_amount'] > 0)
                                            $TotalAmount += $Row['sum_amount'];

                                        if ($Remaining != 0)
                                        {
                                            $Received = $Row['sum_amount'] - $Remaining;
                                            $TotalReceived += $Received;
                                            ?>
                                            <tr>
                                                <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?>
                                                    <!--<br><small><?php /*=$oAging->getDaysPassed($Row['voucher_date'])*/?> Days Old</small>-->
                                                </td>
                                                <td><?=$oAging->getDaysPassed($Row['voucher_date'])?></td>
                                                <td class="font-weight-bold"><?=$Row['voucher_desc']?></td>
                                                <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                <td class="text-right"><?=Utils::format($Received, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                            </tr>
                                            <?
                                        }
                                    }
                                    ?>
                                    <?
                                    while ($Row = DBQM::fetch_assoc($Select7))
                                    {
                                        $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                        $TotalRemaining7 += $Remaining;
                                        if ($Row['sum_amount'] > 0)
                                            $TotalAmount += $Row['sum_amount'];

                                        if ($Remaining != 0)
                                        {
                                            $Received = $Row['sum_amount'] - $Remaining;
                                            $TotalReceived += $Received;
                                            ?>
                                            <tr>
                                                <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?>
                                                    <!--<br><small><?php /*=$oAging->getDaysPassed($Row['voucher_date'])*/?> Days Old</small>-->
                                                </td>
                                                <td><?=$oAging->getDaysPassed($Row['voucher_date'])?></td>
                                                <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                <td class="text-right"><?=Utils::format($Received, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
                                            </tr>
                                            <?
                                        }
                                    }
                                    ?>
                                    <?
                                    while ($Row = DBQM::fetch_assoc($Select8))
                                    {
                                        $Remaining = $oAging->getRemainingAmountByDistinctId($Row['distinct_id']);
                                        $TotalRemaining8 += $Remaining;
                                        if ($Row['sum_amount'] > 0)
                                            $TotalAmount += $Row['sum_amount'];

                                        if ($Remaining != 0)
                                        {
                                            $Received = $Row['sum_amount'] - $Remaining;
                                            $TotalReceived += $Received;
                                            ?>
                                            <tr>
                                                <td><?=$Row['voucher_type']?>-<?=$Row['voucher_number']?></td>
                                                <td><?=DateDecode($Row['voucher_date'], 'd/m/y')?>
                                                    <!--<br><small><?php /*=$oAging->getDaysPassed($Row['voucher_date'])*/?> Days Old</small>-->
                                                </td>
                                                <td><?=$oAging->getDaysPassed($Row['voucher_date'])?></td>
                                                <td class="text-right"><?=Utils::format($Row['sum_amount'], 2);?></td>
                                                <td class="text-right"><?=Utils::format($Received, 2); ?></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"></td>
                                                <td class="text-right"><?=Utils::format($Remaining, 2); ?></td>
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
                                        <td class="text-right"><?=Utils::format($TotalReceived, 2); ?></td>
                                        <td class="text-right"><?=Utils::format($TotalRemaining, 2); ?></td>
                                        <td class="text-right"><?=Utils::format($TotalRemaining2, 2); ?></td>
                                        <td class="text-right"><?=Utils::format($TotalRemaining3, 2); ?></td>
                                        <td class="text-right"><?=Utils::format($TotalRemaining4, 2); ?></td>
                                        <td class="text-right"><?=Utils::format($TotalRemaining5, 2); ?></td>
                                        <td class="text-right"><?=Utils::format($TotalRemaining6, 2); ?></td>
                                        <td class="text-right"><?=Utils::format($TotalRemaining7, 2); ?></td>
                                        <td class="text-right"><?=Utils::format($TotalRemaining8, 2); ?></td>
                                        <td class="text-right"><?=Utils::format($TotalRemaining + $TotalRemaining2 + $TotalRemaining3 + $TotalRemaining4 + $TotalRemaining5 + $TotalRemaining6 + $TotalRemaining7 + $TotalRemaining8, 2); ?></td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    <? } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>window.print();</script>
</body>
</html>