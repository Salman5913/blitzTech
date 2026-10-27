<?php
include_once("../connection/localhost.php");

$oBranch = new Branch();

if ($oBranch->isValidFormSubmission())
{
    $oBranch->initForSave();

    if (!$oBranch->isDelete())
    {
        $oBranch->saveMainTableData([
            $_POST['branch_name'],
            $_POST['address'],
            $_POST['phone'],
            $_POST['fax'],
            $_POST['email'],
            $_POST['is_warehouse'],
            $_POST['is_active']
        ]);
    }
    else
        $oBranch->deleteFormData();

    $oBranch->notifyStatusMessage();
    Utils::redirectToReferer();
}
else
    ErrorHandler::UnauthorizedAccess();