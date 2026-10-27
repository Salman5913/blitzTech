<?php
include_once("../connection/localhost.php");
$CurrencyRate = Currency::getRateByAccountId($_GET['account_id']);
echo $CurrencyRate > 0 ? $CurrencyRate : 1;