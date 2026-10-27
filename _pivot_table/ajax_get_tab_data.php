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
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <? $oPivotTableView->getTabData($_GET['class_name']); ?>
        </div>
    </div>
</div>
