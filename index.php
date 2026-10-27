<?php
$_GET['_page_title'] = 'Dashboard';
$_GET['_page_title_text'] = 'Dashboard';

include_once("includes/inc_header.php");

$oDashBoard = new Dashboard();
$oStockReport = new StockReport();

list($ApplicationStatus, $StatusReason) = $oDashBoard->getApplicationStatus();
$SelectCashBankBalances = $oDashBoard->getCashBankBalances();
$CashFlow = [];
for ($i=0; $i<=6; $i++)
{
    $TimeInSec = strtotime("-{$i} month");
    $CashFlow[date("M Y", $TimeInSec)] = $oDashBoard->getCashFlow(date("Y-m-%", $TimeInSec));
}

/*$SelectZeroStockLedger = $oStockReport->getZeroStockLedger();*/
/*$SelectZeroAmountSave = $oStockReport->getZeroAmountSaveVoucher();*/

list($RowIconsData, $DateFromMain, $DateTillMain, $DateFromCompare, $DateTillCompare) = $oDashBoard->getIconsData();
list($Label, $Revenue, $Expense, $Income) = $oDashBoard->getIncomeByChartValues2();
list($Top5CustomersName, $Top5CustomersAmount) = $oDashBoard->getTopCustomersBySalesValue();
list($Top5ItemsName, $Top5ItemsAmount) = $oDashBoard->getTopItemsByProfitValue();

$LedgerLink = ProjectHelper::SiteURL().'accounting_reports/ledger.php?date_from='.date('m/d/Y', strtotime("-3 month")).'&date_till='.date("m/d/Y").'&account_id=[AccountId]&search=';
?>
<? if (/*!SecurityShell::isAccess("Others", "Hide Dashboard")*/ true) { ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="bg-soft-success p-3 mb-4 d-flex align-items-center" style="border-radius: 10px;">
                <i class="mdi mdi-calendar-clock mr-2" style="font-size: 1.3rem; "></i>
                <div style="font-size: 0.88rem; ">
                    Showing indicators from <?=date('M d, Y', $DateFromMain) ?></strong> to <strong "><?=date('M d, Y', $DateTillMain) ?></strong>, compared with <strong ><?=date('M d, Y', $DateFromCompare) ?></strong> to <strong ><?=date('M d, Y', $DateTillCompare) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-6">
            <? $oDashBoard->designStats("Sales", "primary", "tag-plus-outline", $RowIconsData['current_sales'], $RowIconsData['previous_sales']); ?>
        </div>
        <div class="col-lg-3 col-6">
            <? $oDashBoard->designStats("Purchases", "warning", "cart", $RowIconsData['current_purchase'], $RowIconsData['previous_purchase'], true); ?>
        </div>
        <div class="col-lg-3 col-6">
            <? $oDashBoard->designStats("Receipts", "success", "arrow-up", $RowIconsData['current_receipt'], $RowIconsData['previous_receipt']); ?>
        </div>
        <div class="col-lg-3 col-6">
            <? $oDashBoard->designStats("Payments", "danger", "arrow-down", $RowIconsData['current_payment'], $RowIconsData['previous_payment'], true); ?>
        </div>
        <div class="col-lg-4 col-12">
            <? $oDashBoard->designStats("Income", "warning", "cash", $RowIconsData['current_income'], $RowIconsData['previous_income']); ?>
        </div>
        <div class="col-lg-4 col-6">
            <? $oDashBoard->designStats("Receivable", "success", "arrow-left", $RowIconsData['current_receivable'], $RowIconsData['previous_receivable']); ?>
        </div>
        <div class="col-lg-4 col-6">
            <? $oDashBoard->designStats("Payable", "danger", "arrow-right", $RowIconsData['current_payable'], $RowIconsData['previous_payable'], true); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <? if (!$ApplicationStatus) { ?>
                <div class="card">
                    <h3 class="card-title">Status</h3>
                    <table width="100%">
                        <tr>
                            <td class="text-center"><i class="mdi mdi-emoticon-sad-outline fa-10x text-warning"></i></td>
                        </tr>
                        <tr>
                            <td class="text-center text-warning"><?=$StatusReason?></td>
                        </tr>
                    </table>
                </div>
            <? } ?>
            <div class="card animate-fade-in" style="border-radius: 12px; border: 1px solid #d4e3e1; box-shadow: 0 4px 20px rgba(11, 56, 102,0.05); overflow: hidden; margin-bottom: 1.5rem; background: #ffffff;">
                <div class="card-body" style="padding: 1.3rem 1.4rem;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0 font-weight-bold" style="color: #000000; font-size: 1.05rem;">
                            <i class="mdi mdi-bank-outline mr-2" style="font-size: 1.2rem; color: #0b3866; vertical-align: middle;"></i>Cash / Bank Balances
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-centered table-nowrap mb-0" style="font-size: 0.85rem;">
                            <thead style="background: #f2f7f6;">
                                <tr>
                                    <th style="color: #000000; font-weight: 700; font-size: 0.78rem; border-bottom: 2px solid #d4e3e1; padding: 0.75rem 0.85rem;">Cash / Bank</th>
                                    <th class="text-right" style="color: #000000; font-weight: 700; font-size: 0.78rem; border-bottom: 2px solid #d4e3e1; padding: 0.75rem 0.85rem;">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <? while ($RowCashBankBalances = DBQM::fetch_assoc($SelectCashBankBalances)) { ?>
                                    <tr>
                                        <td style="padding: 0.7rem 0.85rem; border-bottom: 1px solid #edf4f3;">
                                            <a href="<?=str_replace("[AccountId]", $RowCashBankBalances['id'], $LedgerLink)?>" target="_blank" style="color: #0b3866; font-weight: 600; text-decoration: none;">
                                                <?=$RowCashBankBalances['name']?>
                                             </a>
                                        </td>
                                        <td class="text-right font-weight-semibold" data-js="cash-bank-balance" style="padding: 0.7rem 0.85rem; border-bottom: 1px solid #edf4f3; color: #000000;">
                                            <?=Utils::format($RowCashBankBalances['balance'])?>
                                        </td>
                                    </tr>
                                <? } ?>
                            </tbody>
                            <tfoot style="background: #f2f7f6;">
                                <tr>
                                    <td class="font-weight-bold" style="padding: 0.85rem; border-top: 2px solid #d4e3e1; color: #000000;">Total Balance</td>
                                    <td class="text-right font-weight-bold" <? Utils::sumReportColumn('[data-js="cash-bank-balance"]'); ?> style="padding: 0.85rem; border-top: 2px solid #d4e3e1; color: #0b3866; font-size: 0.95rem;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card animate-fade-in" style="border-radius: 12px; border: 1px solid #d4e3e1; box-shadow: 0 4px 20px rgba(11, 56, 102,0.05); overflow: hidden; margin-bottom: 1.5rem; background: #ffffff;">
                <div class="card-body" style="padding: 1.3rem 1.4rem;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0 font-weight-bold" style="color: #000000; font-size: 1.05rem;">
                            <i class="mdi mdi-swap-horizontal mr-2" style="font-size: 1.2rem; color: #0b3866; vertical-align: middle;"></i>Cash Flow
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-centered table-nowrap mb-0" style="font-size: 0.85rem;">
                            <thead style="background: #f2f7f6;">
                                <tr>
                                    <th style="color: #000000; font-weight: 700; font-size: 0.78rem; border-bottom: 2px solid #d4e3e1; padding: 0.75rem 0.85rem;">Month</th>
                                    <th class="text-right" style="color: #000000; font-weight: 700; font-size: 0.78rem; border-bottom: 2px solid #d4e3e1; padding: 0.75rem 0.85rem;">Cash In</th>
                                    <th class="text-right" style="color: #000000; font-weight: 700; font-size: 0.78rem; border-bottom: 2px solid #d4e3e1; padding: 0.75rem 0.85rem;">Cash Out</th>
                                    <th class="text-right" style="color: #000000; font-weight: 700; font-size: 0.78rem; border-bottom: 2px solid #d4e3e1; padding: 0.75rem 0.85rem;">Difference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <? foreach ($CashFlow as $Name => $Balance) { 
                                    $Difference = $Balance[0] - $Balance[1];
                                    $diffColor = '#000000';
                                    $diffPrefix = ($Difference >= 0) ? '+' : '';
                                ?>
                                    <tr>
                                        <td style="padding: 0.7rem 0.85rem; border-bottom: 1px solid #edf4f3;">
                                            <a href="<?=ProjectHelper::SiteURL()?>accounting_reports/cashflow.php?month_year=<?=$Name?>&search=" target="_blank" style="color: #0b3866; font-weight: 600; text-decoration: none;">
                                                <?=$Name?>
                                            </a>
                                        </td>
                                        <td class="text-right" style="padding: 0.7rem 0.85rem; border-bottom: 1px solid #edf4f3; color: #000000;">
                                            <?=Utils::format($Balance[0])?>
                                        </td>
                                        <td class="text-right" style="padding: 0.7rem 0.85rem; border-bottom: 1px solid #edf4f3; color: #000000;">
                                            <?=Utils::format($Balance[1])?>
                                        </td>
                                        <td class="text-right font-weight-semibold" style="padding: 0.7rem 0.85rem; border-bottom: 1px solid #edf4f3; color: <?=$diffColor?>;">
                                            <?=$diffPrefix?><?=Utils::format($Difference)?>
                                        </td>
                                    </tr>
                                <? } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0 font-weight-bold" style="color: #000000; font-size: 1.05rem;">
                            <i class="mdi mdi-trending-up mr-2" style="font-size: 1.2rem; color: #0b3866; vertical-align: middle;"></i>Top Profitable Items
                        </h5>
                        <small class="badge badge-soft-success">Last 30 Days</small>
                    </div>
                    <div id="top_profitable_items_chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3 font-weight-bold" style="color: #000000; font-size: 1.05rem;">
                        <i class="mdi mdi-chart-bell-curve mr-2" style="font-size: 1.2rem; color: #0b3866; vertical-align: middle;"></i>Revenue to Expense Ratio
                    </h5>
                    <div id="revenue"></div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0 font-weight-bold" style="color: #000000; font-size: 1.05rem;">
                            <i class="mdi mdi-account-group mr-2" style="font-size: 1.2rem; color: #0b3866; vertical-align: middle;"></i>Top Customers
                        </h5>
                        <small class="badge badge-soft-secondary">Last 30 Days</small>
                    </div>
                    <div id="top_customer_chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>
    </div>
<? } ?>
<script src="assets/libs/apexcharts/apexcharts.min.js"></script>
<script>
/* ******************** Income To Expense Ratio *********************** */
var income_options = {
    series: [{
        name: 'Revenue',
        type: 'column',
        data: [<? echo implode(", ", $Revenue); ?>]
    }, {
        name: 'Expense',
        type: 'column',
        data: [<? echo implode(", ", $Expense); ?>]
    }, {
        name: 'Profit',
        type: 'column',
        data: [<? echo implode(", ", $Income); ?>]
    }],
    colors: ['#b57bee', '#0b3866', '#061d38'],
    chart: {
        height: 350,
        type: 'bar',
        stacked: false,
        toolbar: {
            show: false
        },
        fontFamily: 'Inter, sans-serif'
    },
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: '52%'
        }
    },
    stroke: {
        width: [0, 0, 0],
        curve: 'smooth'
    },
    fill: {
        opacity: [0.95, 0.95, 0.95]
    },
    dataLabels: {
        enabled: false
    },
    grid: {
        borderColor: '#d4e3e1',
        strokeDashArray: 4,
        padding: {
            bottom: 10
        }
    },
    xaxis: {
        categories: [<? echo implode(", ", array_map("escape", $Label)); ?>],
        labels: {
            style: {
                colors: '#000000',
                fontFamily: 'Inter, sans-serif'
            }
        }
    },
    yaxis: [{
        title: {
            text: 'Amount (' + '<?=ProjectHelper::DEFAULT_CURRENCY?>' + ')',
            style: {
                color: '#000000',
                fontFamily: 'Inter, sans-serif',
                fontWeight: 600
            }
        },
        labels: {
            formatter: function (value) {
                return value.toLocaleString();
            },
            style: {
                colors: '#000000',
                fontFamily: 'Inter, sans-serif'
            }
        }
    }],
    tooltip: {
        shared: true,
        intersect: false,
        y: {
            formatter: function (y) {
                if (typeof y !== "undefined") {
                    return y.toLocaleString() + " <?=ProjectHelper::DEFAULT_CURRENCY?>";
                }
                return y;
            }
        }
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '13px',
        fontFamily: 'Inter, sans-serif',
        labels: {
            colors: '#000000'
        }
    }
};

var income_chart = new ApexCharts(document.querySelector("#revenue"), income_options);
income_chart.render();
/* ******************** Income To Expense Ratio *********************** */



/* ******************** Top Customers *********************** */
var options_top_customer = {
    series: [<? echo implode(", ", $Top5CustomersAmount); ?>],
    chart: {
        type: 'donut',
        height: 310,
        fontFamily: 'Inter, sans-serif'
    },
    colors: ['#b57bee', '#d2adf5', '#3d9089', '#0b3866', '#082a4d'],
    labels: [<? echo implode(", ", array_map("escape", $Top5CustomersName)); ?>],
    dataLabels: {
        enabled: false
    },
    stroke: {
        width: 2,
        colors: ['#ffffff']
    },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '13px',
                        fontFamily: 'Inter, sans-serif',
                        color: '#000000',
                        offsetY: -5
                    },
                    value: {
                        show: true,
                        fontSize: '17px',
                        fontFamily: 'Inter, sans-serif',
                        fontWeight: '700',
                        color: '#000000',
                        offsetY: 5,
                        formatter: function (val) {
                            return parseInt(val).toLocaleString() + " <?=ProjectHelper::DEFAULT_CURRENCY?>";
                        }
                    },
                    total: {
                        show: true,
                        label: 'Total Sales',
                        color: '#000000',
                        formatter: function (w) {
                            return w.globals.seriesTotals.reduce((a, b) => {
                                return a + b;
                            }, 0).toLocaleString() + " <?=ProjectHelper::DEFAULT_CURRENCY?>";
                        }
                    }
                }
            }
        }
    },
    tooltip: {
        y: {
            formatter: function (val) {
                return val.toLocaleString() + " <?=ProjectHelper::DEFAULT_CURRENCY?>";
            }
        }
    },
    legend: {
        position: 'bottom',
        horizontalAlign: 'center',
        fontSize: '12px',
        fontFamily: 'Inter, sans-serif',
        labels: {
            colors: '#000000'
        },
        markers: {
            radius: 12
        }
    },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                height: 280
            },
            legend: {
                position: 'bottom'
            }
        }
    }]
};

var top_customer_chart = new ApexCharts(document.querySelector("#top_customer_chart"), options_top_customer);
top_customer_chart.render();
/* ******************** Top Customers *********************** */



/* ******************** Top Profitable Items *********************** */
var options_top_profit_items = {
    series: [<? echo implode(", ", $Top5ItemsAmount); ?>],
    chart: {
        type: 'donut',
        height: 310,
        fontFamily: 'Inter, sans-serif'
    },
    colors: ['#b57bee', '#d2adf5', '#3d9089', '#0b3866', '#082a4d'],
    labels: [<? echo implode(", ", array_map("escape", $Top5ItemsName)); ?>],
    dataLabels: {
        enabled: false
    },
    stroke: {
        width: 2,
        colors: ['#ffffff']
    },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '13px',
                        fontFamily: 'Inter, sans-serif',
                        color: '#000000',
                        offsetY: -5
                    },
                    value: {
                        show: true,
                        fontSize: '17px',
                        fontFamily: 'Inter, sans-serif',
                        fontWeight: '700',
                        color: '#000000',
                        offsetY: 5,
                        formatter: function (val) {
                            return parseInt(val).toLocaleString() + " <?=ProjectHelper::DEFAULT_CURRENCY?>";
                        }
                    },
                    total: {
                        show: true,
                        label: 'Total Profit',
                        color: '#000000',
                        formatter: function (w) {
                            return w.globals.seriesTotals.reduce((a, b) => {
                                return a + b;
                            }, 0).toLocaleString() + " <?=ProjectHelper::DEFAULT_CURRENCY?>";
                        }
                    }
                }
            }
        }
    },
    tooltip: {
        y: {
            formatter: function (val) {
                return val.toLocaleString() + " <?=ProjectHelper::DEFAULT_CURRENCY?>";
            }
        }
    },
    legend: {
        position: 'bottom',
        horizontalAlign: 'center',
        fontSize: '12px',
        fontFamily: 'Inter, sans-serif',
        labels: {
            colors: '#000000'
        },
        markers: {
            radius: 12
        }
    },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                height: 280
            },
            legend: {
                position: 'bottom'
            }
        }
    }]
};

var top_profit_items = new ApexCharts(document.querySelector("#top_profitable_items_chart"), options_top_profit_items);
top_profit_items.render();
/* ******************** Top Profitable Items *********************** */
</script>
<!-- apexcharts -->

<!-- jquery.vectormap map -->
<script src="assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="assets/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-us-merc-en.js"></script>

<?php include_once("includes/inc_footer.php") ?>