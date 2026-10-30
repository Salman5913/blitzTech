<?php
include_once("../connection/localhost.php");

if (!SecurityShell::isVoucherAccess(VoucherType::OIV))
    ErrorHandler::UnauthorizedAccess();

header('Content-Type: application/json');
echo json_encode(SalesPOS::getMenuStatus($_GET['branch_id'] ?? 0));
