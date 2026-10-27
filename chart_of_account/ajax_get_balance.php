<?php
include_once("../connection/localhost.php");

if (ChartOfAccount::canUserAccess($_GET['account_id']))
	echo LedgerReport::getBalance($_GET['account_id'], DateEncode($_GET['date_time']), $_GET['voucher_type'], $_GET['voucher_number']);
else
	echo "0";