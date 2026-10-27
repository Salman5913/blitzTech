<?php
include_once ("../connection/localhost.php");

$oLoan = new Loan();
$LoanId = $_GET['id'];

$oLoan->cancelLoan($LoanId);

SuccessHandler::setMessage("Loan/Advance of ".ChartOfAccount::getName($oLoan->getAccountId($LoanId))." has been cancelled successfully.");
Utils::redirectToReferer();