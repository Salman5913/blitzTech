<?php
include_once("../connection/localhost.php");
SecurityShell::redirectIfNotAllowedFormSubmission();

$oStockAttribute = new StockAttribute();
$oStockAttribute->initForSave();


if ($oStockAttribute->isValidFormSubmission())
{
    $oStockAttribute->saveMainTableData([
        $_POST['name']
    ]);

    if($oStockAttribute->isDelete())
        $oStockAttribute->deleteFormData();

    $oStockAttribute->notifyStatusMessage();

	Utils::redirectToReferer();
}
else
	ErrorHandler::UnauthorizedAccess();