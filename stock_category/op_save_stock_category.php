<?php
include_once("../connection/localhost.php");

$oStockCategory = new StockCategory();

if ($oStockCategory->isValidFormSubmission())
{
    $oStockCategory->initForSave();
    $oStockCategory->saveMainTableData([
        $_POST['parent_id'],
        $_POST['name']
    ]);

    if ($oStockCategory->isDelete())
        $oStockCategory->deleteFormData();

    $oStockCategory->notifyStatusMessage();
    Utils::redirectToReferer();
}
else
    ErrorHandler::UnauthorizedAccess();