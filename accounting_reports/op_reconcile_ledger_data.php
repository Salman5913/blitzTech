<?php
include_once ("../connection/localhost.php");

$oLedgerReport = new LedgerReport();
$oLedgerReport->reconcileLedgerData($_GET['ledger_data_id']);

SuccessHandler::setMessage("Your data has been reconciled successfully");
Utils::redirectToReferer();