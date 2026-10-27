<?php
include_once("../connection/localhost.php");

$oChartOfAccount = new ChartOfAccount();

if ($oChartOfAccount->isValidFormSubmission())
{
    $oChartOfAccount->initForSave();
    $isCasLeavesError = false;
    $isMedLeavesError = false;

    $_POST['currency_code'] = ProjectHelper::DEFAULT_CURRENCY;

    if (!$oChartOfAccount->isDelete())
    {
        $oChartOfAccount->saveMainTableData([
            $_POST['category_id'],
            $_POST['name'],
            $_POST['currency_code'],
            'Y',
            'Y'
        ]);

        if ($oChartOfAccount->isVoucherCreatedSuccessfully())
        {
            $oChartOfAccount->saveDataTableData([
                $_POST['ph1'],
                $_POST['ph2'],
                $_POST['mob'],
                $_POST['mob2'],
                $_POST['email1'],
                $_POST['email2'],
                $_POST['city'],
                $_POST['cnic'],
                $_POST['ntn'],
                $_POST['credit_limit'],
                $_POST['sales_disc_per'],
                $_POST['address'],
                $_POST['strn']
            ], ChartOfAccount::IND_BIO_DATA);

            foreach ($_POST['identifier'] as $Identifier)
            {
                if (!empty($Identifier))
                {
                    $oChartOfAccount->saveDataTableData([
                        $Identifier
                    ], ChartOfAccount::IND_BANK_STATEMENT_IDENTIFIER);
                }
            }

            if (!empty($_POST['emp_id']) and $_POST['category_id'] == getConstantCategory()->getEmployee())
            {
                $oChartOfAccount->saveDataTableData([
                    $_POST['emp_id'],
                    $_POST['branch_id'],
                    $_POST['basic_salary'],
                    $_POST['can_overtime'],
                    $_POST['emp_cnic'],
                    DateEncode($_POST['emp_join_date']),
                    $_POST['is_active']
                ], ChartOfAccount::IND_HR_ACCOUNTS_DATA);

                $oChartOfAccount->createSalaryHistory($oChartOfAccount->getVoucherId(), $_POST['basic_salary']);
            }
        }
    }
    else
        $oChartOfAccount->deleteFormData();


	$oChartOfAccount->notifyStatusMessage();

	if ($oChartOfAccount->isDelete())
	    Utils::redirect("report_transaction_accounts.php");
	else
	    Utils::redirectToReferer();
}
else
    ErrorHandler::UnauthorizedAccess();