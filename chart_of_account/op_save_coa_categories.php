<?php
include_once("../connection/localhost.php");

$oChartOfAccountCategory = new ChartOfAccountCategory();

if ($oChartOfAccountCategory->isValidFormSubmission())
{
    $oChartOfAccountCategory->initForSave();
    $oChartOfAccountCategory->saveMainTableData([
        $_POST['parent_id'],
        $_POST['name'],
        'Y'
    ]);

    if ($oChartOfAccountCategory->isDelete())
        $oChartOfAccountCategory->deleteCategory($oChartOfAccountCategory->getVoucherId());

    $oChartOfAccountCategory->notifyStatusMessage();
    Utils::redirectToReferer();
}
else
    ErrorHandler::UnauthorizedAccess();