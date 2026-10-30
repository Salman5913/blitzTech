<?php
require_once('../connection/localhost.php');
SecurityShell::redirectIfNotAllowedFormSubmission();

$oSecurityShellUser = new SecurityShellUser();

if ($oSecurityShellUser->isValidFormSubmission())
{
    $oSecurityShellUser->initForSave();

    $RowDetails = $oSecurityShellUser->getDetails();
    $Password = empty($_POST['password']) ? $RowDetails['password'] : md5($_POST['password']);

    $oSecurityShellUser->saveMainTableData([
        $_POST['username'],
        $Password,
        'N',
        $_POST['branch_id'],
        'N',
        NULL,
        $_POST['is_active']
    ]);

    foreach ($_POST['group_id'] as $GroupId)
    {
        $oSecurityShellUser->saveDataTableData([
            $GroupId
        ]);
    }


    // Cost centers of the user (none = every cost center); removed with the user
    CostCenter::saveUserCostCenters($oSecurityShellUser->getVoucherId(), $oSecurityShellUser->isDelete() ? [] : ($_POST['cost_center_ids'] ?? []));

    if ($oSecurityShellUser->isDelete())
        $oSecurityShellUser->deleteFormData();
		
	$oSecurityShellUser->notifyStatusMessage();

    header("Location: view_users.php");
}
else
    ErrorHandler::UnauthorizedAccess();