<?php
include_once ("../connection/localhost.php");

$oPivotTableVoucher = new PivotTableVoucher();
$oPivotTableVoucher->initForEdit();

$RowDetails = $oPivotTableVoucher->getDetails();

$oPivotTableView = new PivotTableView(
        new $RowDetails['pivot_table_class'](),
        $RowDetails,
        $oPivotTableVoucher->getData(PivotTableVoucher::IND_FILTER),
        $oPivotTableVoucher->getData(PivotTableVoucher::IND_SORT)
);
$SiteURL = ProjectHelper::SiteURL();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?=$RowDetails['report_name']?> - Finac-ERP.com</title>
    <link rel="shortcut icon" href="<?=$SiteURL?>images/favicon.ico" type="image/x-icon">
    <link rel="icon" href="<?=$SiteURL?>images/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="<?=$SiteURL?>assets/css/bootstrap.css" type="text/css">
    <link rel="stylesheet" href="<?=$SiteURL?>assets/css/print.css" type="text/css">
    <script src="<?=$SiteURL?>assets/libs/jquery/jquery.min.js"></script>
    <script src="<?=$SiteURL?>assets/js/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="<?=$SiteURL?>assets/css/app.css">
    <link rel="stylesheet" href="<?=$SiteURL?>assets/css/pivot-table.css">

    <script src="<?=$SiteURL?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?=$SiteURL?>assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="<?=$SiteURL?>assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script><!--
    <script src="<?/*=$SiteURL*/?>assets/libs/datatables.net-fixed-columns/js/fixedColumns.jqueryui.min.js"></script>
    <script src="<?/*=$SiteURL*/?>assets/libs/datatables.net-fixed-columns/js/fixedHeader.jqueryui.min.js"></script>-->
    <script src="<?=$SiteURL?>js/fnLengthChange.js"></script>
    <script src="<?=$SiteURL?>assets/js/jquery.table2excel.min.js"></script>
    <script src="<?=$SiteURL?>assets/libs/apexcharts/apexcharts.min.js"></script>
    <link rel="stylesheet" href="<?=$SiteURL?>assets/libs/apexcharts/apexcharts.css" type="text/css">
    <link href="<?=$SiteURL?>assets/css/app-font.css?v=1" rel="stylesheet" type="text/css" />
</head>
<body class="report customized-report container-fluid">
    <div class="row">
        <div class="col-md-2 col-4"><img src="<?=$SiteURL?>assets/images/blitztech.png" class="img-fluid" style="max-width: 160px;"></div>
        <div class="col-md-10 col-8 align-content-center text-left">
            <h3 class="popup-header_voucher"><?=$RowDetails['report_name']?></h3>
            <div class="subheading"><?=$oPivotTableView->getSubheading()?></div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <ul class="tabs">
                <li href="javascript:void(0)" data-class="PivotTableTabTable" data-target="tab-pivot_table">Pivot Table</li>
                <li href="javascript:void(0)" data-class="PivotTableTabTreeView" data-target="tab-tree_view_chart">Tree View</li>
                <li href="javascript:void(0)" data-class="PivotTableTabStackedColumn" data-target="tab-stacked_column_chart">Stacked Column</li>
                <li href="javascript:void(0)" data-class="PivotTableTabStackedColumnByRow" data-target="tab-stacked_column_by_row_chart">Stacked Column By Row</li>
                <li href="javascript:void(0)" data-class="PivotTableTabHeatmap" data-target="tab-heatmap_chart">Heatmap</li>
            </ul>
            <div class="tab-body">
                <div class="tab-data tab-pivot_table">
                    <div class="text-center">
                        <a class="btn btn-danger text-white load-data" data-class="PivotTableTabTable" href="javascript:void(0)">Click to Load Data</a>
                    </div>
                </div>
                <div class="tab-data tab-tree_view_chart">
                    <div class="text-center">
                        <a class="btn btn-danger text-white load-data" data-class="PivotTableTabTreeView" href="javascript:void(0)">Click to Load Data</a>
                    </div>
                </div>
                <div class="tab-data tab-stacked_column_chart">
                    <div class="text-center">
                        <a class="btn btn-danger text-white load-data" data-class="PivotTableTabStackedColumn" href="javascript:void(0)">Click to Load Data</a>
                    </div>
                </div>
                <div class="tab-data tab-stacked_column_by_row_chart">
                    <div class="text-center">
                        <a class="btn btn-danger text-white load-data" data-class="PivotTableTabStackedColumnByRow" href="javascript:void(0)">Click to Load Data</a>
                    </div>
                </div>
                <div class="tab-data tab-heatmap_chart">
                    <div class="text-center">
                        <a class="btn btn-danger text-white load-data" data-class="PivotTableTabHeatmap" href="javascript:void(0)">Click to Load Data</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
$(function() {
    $(".tab-data").hide();

    $("ul.tabs li").click(function () {
        $(".tab-data").hide();
        $("." + $(this).attr("data-target")).show();

        $("ul.tabs li").removeClass("active");
        $(this).addClass("active");
    });

    $("ul.tabs li:first-child").trigger("click")

    $("a.load-data").click(function () {
        var _this = $(this);
        _this.hide();

        $.get("../_pivot_table/ajax_get_tab_data.php",
            {
                class_name: $(this).attr("data-class"),
                id: <?=$_GET['id']?>,
                voucher_type_id: <?=$_GET['voucher_type_id']?>
            }, function (data) {
            _this.closest(".tab-data").html(data);
        });
    })
});
function round(value)
{
    var n = parseFloat(value);
    if (!isNaN(n))
        return n.toFixed(2);
    else
        return 0.00;
}
function thousandSep(input)
{
    var output = round(input);
    var isLessThanZero;

    if (parseFloat(input)) {
        isLessThanZero = input < 0;
        input = Math.abs(input);

        input = input.toString();
        var parts = input.split(".");
        parts[0] = parts[0].split("").reverse().join("").replace(/(\d{3})(?!$)/g, "$1,").split("").reverse().join("");
        output = parts.join(".");
    }

    return isLessThanZero ? "("+output+")" : output;
}

</script>
</html>