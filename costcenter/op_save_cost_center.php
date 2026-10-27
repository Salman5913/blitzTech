<?php
include_once("../connection/localhost.php");

$oCostCenter = new CostCenter();

if ($oCostCenter->isValidFormSubmission())
{
    $oCostCenter->initForSave();

    if (!$oCostCenter->isDelete())
    {
        $oCostCenter->saveMainTableData([
            $_POST['cost_center_name']
        ]);
    }
    else
        $oCostCenter->deleteFormData();

    $oCostCenter->notifyStatusMessage();
    Utils::redirectToReferer();
}
else
    ErrorHandler::UnauthorizedAccess();