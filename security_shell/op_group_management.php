<?php
require_once('../connection/localhost.php');
SecurityShell::redirectIfNotAllowedFormSubmission();

$oSecurityShellGroup = new SecurityShellGroup();

if ($oSecurityShellGroup->isValidFormSubmission())
{
    $oSecurityShellGroup->initForSave();

    $oSecurityShellGroup->saveMainTableData([
        $_POST['group_name']
    ]);

    foreach ($_POST['area_id'] as $AreaId)
        $oSecurityShellGroup->saveDataTableData([
            $AreaId,
            null
        ]);

    foreach ($_POST['voucher_type_id'] as $SelectedVoucherTypeId)
        $oSecurityShellGroup->saveDataTableData([
            null,
            $SelectedVoucherTypeId,
            isset($_POST['voucher_type_edit'][$SelectedVoucherTypeId]) ? 'Y' : null
        ]);

    foreach ($_POST['pivot_table_report_id'] as $SelectedPivotReportId)
        $oSecurityShellGroup->saveDataTableData([
            null,
            null,
            null,
            $SelectedPivotReportId
        ]);


    if ($oSecurityShellGroup->isDelete())
        $oSecurityShellGroup->deleteFormData();

	$oSecurityShellGroup->notifyStatusMessage();

    Utils::redirect("view_groups.php");
}
else
	ErrorHandler::UnauthorizedAccess();