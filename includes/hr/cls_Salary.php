<?php

class Salary extends BasicVoucher
{
    const IND_SALARY_DATA = "salary_data";
    const IND_SALARY_LEAVES_DATA = "salary_leaves_data";
    const IND_SALARY_HOLIDAY = "salary_holiday";
    const IND_SALARY_PRINT_TICKBOZ = "salary_print_tickboz";

    function __construct()
    {
        parent::__construct(
            "hr_salary",
            [
                self::IND_SALARY_DATA => "hr_salary_data",
                self::IND_SALARY_HOLIDAY => "hr_salary_holiday",
            ],
            "salary_id", VoucherType::SM
        );

        $this->setDeleteDataUponSave(false);
        $this->setReportFilters(false);
    }

    function deleteSalaryLoanInstallmentByDistinctId($DistinctId)
    {
        DBQM::query("DELETE FROM hr_salary_data_loan_installment WHERE distinct_id = ".escape($DistinctId));
    }

    function deleteSalaryLoanInstallmentBySalaryDataId($SalaryDataId)
    {
        DBQM::query("DELETE FROM hr_salary_data_loan_installment WHERE salary_data_id = ".escape($SalaryDataId));
    }

	function registerLoanInstallment($AccountId, $SalaryDataId, $DistinctId, $LoanDeduction, $DateTime, $isRecurred=false)
	{
		$Select = DBQM::query("
										SELECT 
											l.id AS loan_id,
											l.amount,
											l.monthly_installment,
											l.amount - SUM(IFNULL(li.installment_amount, 0)) AS remaining_amount											 
										FROM 
										    distinct_id d,
											hr_loan l 
											LEFT JOIN 
												hr_salary_data_loan_installment li 
												ON 
													l.id = li.loan_id
										WHERE
											l.account_id = ".escape($AccountId)." AND
											l.type <> 'A' AND
											l.distinct_id = d.id AND
											(d.is_deleted <> 'Y')
										GROUP BY
											l.id
										HAVING
											remaining_amount > 0
										ORDER BY 
											date
		");

		while($Row = DBQM::fetch_assoc($Select))
		{
			if ($LoanDeduction > 0)
			{
                /**
                 * First deduct loan paid from all pending loans based on installment amount.
                 * If Still Loan amount is left, means user is willing to pay more than his installment amount.
                 * We would deduct complete remaining amounts one by one until loan amount is 0.
                 */
			    if (!$isRecurred)
                {
                    $InstallmentAmount = $Row['remaining_amount'] > $Row['monthly_installment'] ? $Row['monthly_installment'] : $Row['remaining_amount'];
                    $InstallmentAmount = $InstallmentAmount > $LoanDeduction ? $LoanDeduction : $InstallmentAmount;

                    $LoanDeduction -= $InstallmentAmount;
                }
			    else
                {
                    $InstallmentAmount = $Row['remaining_amount'] > $LoanDeduction ? $LoanDeduction : $Row['remaining_amount'];
                    $LoanDeduction -= $InstallmentAmount;
                }

				DBQM::query("INSERT INTO hr_salary_data_loan_installment VALUES (
														0,
														".escape($SalaryDataId, 'int', true).",
														".escape($DistinctId).",
														".escape($Row['loan_id'], 'int').",
														".escape($DateTime).",
														".escape($InstallmentAmount).",
														'N'
				)");
			}
		}

		if ($LoanDeduction > 0)
        {
            if (!$isRecurred)
                $this->registerLoanInstallment($AccountId, $SalaryDataId, $DistinctId, $LoanDeduction, $DateTime, true);
            else
                ErrorHandler::setError("Loan Amount of ".$LoanDeduction." is unadjusted for ".ChartOfAccount::getName($AccountId));
        }
	}


	function registerAdvanceLoanInstallment($AccountId, $SalaryDataId, $DistinctId, $AdvanceDeduction, $DateTime)
	{
		$Select = DBQM::query("
										SELECT 
											l.id AS loan_id,
											l.amount,
											l.monthly_installment,
											l.amount - SUM(IFNULL(li.installment_amount, 0)) AS remaining_amount											 
										FROM 
										    distinct_id d,
											hr_loan l 
											LEFT JOIN 
												hr_salary_data_loan_installment li 
												ON 
													l.id = li.loan_id
										WHERE
											l.account_id = ".escape($AccountId)." AND
											l.type = 'A' AND
											l.distinct_id = d.id AND
											(d.is_deleted <> 'Y')
										GROUP BY
											l.id
										HAVING
											remaining_amount > 0
										ORDER BY 
											date
		");

		while($Row = DBQM::fetch_assoc($Select))
		{
			if ( $AdvanceDeduction > 0)
			{
				$InstallmentAmount = $Row['remaining_amount'] > $Row['monthly_installment'] ? $Row['monthly_installment'] : $Row['remaining_amount'];
				$InstallmentAmount = $InstallmentAmount > $AdvanceDeduction ? $AdvanceDeduction : $InstallmentAmount;

				$AdvanceDeduction -= $InstallmentAmount;

				DBQM::query("INSERT INTO hr_salary_data_loan_installment VALUES (
														0,
														".escape($SalaryDataId, 'int', true).",
														".escape($DistinctId).",
														".escape($Row['loan_id'], 'int').",
														".escape($DateTime).",
														".escape($InstallmentAmount).",
														'N'
				)");
			}
		}

		if ( $AdvanceDeduction > 0)
			ErrorHandler::setError( "Advance Amount of " . $AdvanceDeduction . " is unadjusted for " . ChartOfAccount::getName($AccountId));
	}

    function registerLoanInstallmentViaVouchers($DistinctId, $LoanId, $LoanDeduction, $DateTime)
    {
        DBQM::query("INSERT INTO hr_salary_data_loan_installment VALUES (
                                                    0,
                                                    NULL,
                                                    ".escape($DistinctId).",
                                                    ".escape($LoanId).",
													".escape($DateTime).",
                                                    ".escape($LoanDeduction).",
                                                    'N'
        )");
    }

	function getAllEmployeeList($SalaryId, $AccountId='')
	{
		$Where = UtilsQueryMaker::equal("sd.account_id", $AccountId);

		return DBQM::query("
									SELECT 
										hrad.emp_id, 
										a.name AS emp_name, 
										hrad.basic_salary, 
										a.id AS account_id, 
										b.name AS branch_name,
										sd.* 
									FROM 
										hr_salary s,
										hr_salary_data sd, 
										acc_accounts a, 
										hr_accounts_data hrad,
										branch b,
										hr_attendance_log al
									WHERE 
										sd.account_id = a.id AND 
										hrad.account_id = a.id AND
										b.id = al.branch_id AND 
										al.id = s.log_id AND 
										s.id = sd.salary_id AND
										salary_id = ".escape($SalaryId).$Where
		);
	}

	function getHolidays($SalaryId)
	{
		return DBQM::query("SELECT * FROM hr_salary_holiday WHERE salary_id = ".escape($SalaryId));
	}

	function getReport()
	{
		return DBQM::query("
									SELECT 
										s.*, 
										al.month_name, 
										b.name AS branch_name,
										d.voucher_type_id,
										d.voucher_number,
										d.voucher_date,
										d.is_deleted,
										d.is_draft,
										COUNT(sd.id) AS emp_count 
									FROM 
										hr_salary s, 
										distinct_id d,
										hr_attendance_log al, 
										hr_salary_data sd,
										branch b 
									WHERE 
										s.log_id = al.id AND 
										s.id = sd.salary_id AND
										s.distinct_id = d.id AND
										b.id = al.branch_id 
									GROUP BY 
										s.id 
									ORDER BY 
										s.id DESC
	    ");
	}

	function markAsSalaryPaidUnpaid($AccountId, $SalaryId, $Value, $PaidFromAccountId)
	{
		DBQM::query("
							UPDATE 
								hr_salary_data 
							SET 
								is_paid = ".escape($Value).", 
								paid_from_account_id = ".escape($PaidFromAccountId, 'int', true)." 
							WHERE 
								account_id = ".escape($AccountId)." AND 
								salary_id = " . escape($SalaryId)
		);
	}

    function InsertCancelledLoanInstallment($DistinctId, $LoanId, $LoanAmount)
    {
        $Select = DBQM::query("SELECT SUM(installment_amount) FROM hr_salary_data_loan_installment WHERE loan_id = ".escape($LoanId));
        $DeductedAmount = (float)DBQM::result($Select, 0);

        $Remaining = ($LoanAmount - $DeductedAmount);
        if ($Remaining > 0)
        {
            DBQM::query("INSERT INTO hr_salary_data_loan_installment VALUES (
														0,
														NULL,
														".escape($DistinctId).",
														".escape($LoanId, 'int').",
														".escape(DatabaseHelper::NOW()).",
														".escape($Remaining, 'double').",
														'Y'
		    )");
        }

        return $Remaining;
    }

	function getSalaryIdByLogId($LogId)
	{
		$Select = DBQM::query("SELECT id FROM hr_salary WHERE log_id = ".escape($LogId));
		return DBQM::result($Select, 0);
	}

    protected function saveTransactionEntries()
    {
        $oLedger = $this->getLedgerObject();

        $RowDetails = $this->getDetails();
        $SelectData = $this->getData(Salary::IND_SALARY_DATA);

        $TotalSalary = 0;
        $SalaryExpense = 0;

        while ($Row = DBQM::fetch_assoc($SelectData))
        {
            $this->registerLoanInstallment($Row['account_id'], $Row['id'], $RowDetails['distinct_id'], $Row['loan_deduction'], $RowDetails['voucher_date']);
            $this->registerAdvanceLoanInstallment($Row['account_id'], $Row['id'], $RowDetails['distinct_id'], $Row['advance_deduction'], $RowDetails['voucher_date']);

            $oLedger->saveData($Row['account_id'], "-".($Row['net_salary'] + $Row['loan_deduction'] + $Row['advance_deduction']), "Salary", getConstantAccount()->getSalaryAccount());
            $TotalSalary += $Row['net_salary'];
            $SalaryExpense += ($Row['net_salary'] + $Row['loan_deduction'] + $Row['advance_deduction']);
        }

        $oLedger->saveData(getConstantAccount()->getSalaryAccount(), $SalaryExpense, "Salary");
        $this->updateVoucherTotal($TotalSalary);
    }
}