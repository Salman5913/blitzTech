<?php
include_once ("../connection/localhost.php");

$oCurrency = new Currency();
$oCurrency->updateRates();