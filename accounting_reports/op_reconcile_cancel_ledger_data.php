<?php
include_once ("../connection/localhost.php");

$oLedgerReport = new LedgerReport();
$oLedgerReport->reconcileCancelLedgerData($_GET['ledger_data_id']);

SuccessHandler::setMessage("Reconciled Data Cancelled Successfully");
Utils::redirectToReferer();