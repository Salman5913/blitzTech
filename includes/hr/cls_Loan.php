<?php

class Loan extends BasicVoucher
{
    public function __construct()
    {
        parent::__construct("hr_loan","","", [VoucherType::LV, VoucherType::LC]);
    }
    
    function getAccountId($LoanId)
    {
        $Select = DBQM::query("SELECT account_id FROM hr_loan WHERE id = ".escape($LoanId));
        return DBQM::result($Select, 0);
    }

    function cancelLoan($LoanId)
    {
        $RowDetails = $this->getDetailsByVoucherId($LoanId);
        DBQM::query("UPDATE hr_loan SET is_cancelled = 'Y', cancelled_by_user_id = ".escape(SecurityShell::getLoggedInUserId())." WHERE id = ".escape($LoanId));

        $oSalary = new Salary();
        $Remaining = $oSalary->InsertCancelledLoanInstallment($RowDetails['distinct_id'], $LoanId, $RowDetails['amount']);

        if ($Remaining > 0)
        {
            $oLedger = new Ledger(
                $RowDetails['distinct_id'],
                DatabaseHelper::NOW(),
                VoucherType::LC,
                Ledger::getNewVoucherNumber(VoucherType::LC),
                $RowDetails['id']
            );
            $Description = $RowDetails['type'] == 'A' ? 'Advance': 'Loan';
            $oLedger->saveData($RowDetails['account_id'], "-".$Remaining, $Description.' Cancelled');
            $oLedger->saveData(getConstantAccount()->getLoanBadDebtsAccount(), $Remaining, $Description.' Cancelled');
        }
    }

	function getAllLoanByAccountId($AccountId)
	{
		return DBQM::query("SELECT * FROM hr_loan WHERE account_id = ".escape($AccountId));
	}

	function getAllEmpLoans($IgnoreSalaryId='', $ForDashboard=false, $BranchId='', $Date)
	{
		$Where = '';
		$Where .= empty($IgnoreSalaryId) ? '' : (" AND sd.salary_id < ".escape($IgnoreSalaryId));
		$MainWhere = UtilsQueryMaker::equal("b.id", $BranchId);

		$Select = DBQM::query("
										SELECT
											t.account_id,
											a.name,
											SUM(total_loan) AS total_loan,
											SUM(total_paid + get_total_paid_loan_by_vouchers(t.account_id, ".escape($Date).")) AS total_paid,
											ad.emp_id,
											b.name AS branch_name
										FROM 
											(
												SELECT
													l.account_id,
													SUM(l.amount) AS total_loan,
													0 AS total_paid
												FROM
													hr_loan l,
													distinct_id di
												WHERE  
												    l.distinct_id = di.id AND
												    di.is_deleted <> 'Y' AND
												    date <= ".escape($Date.' 23:59:59')." 
												GROUP BY 
													l.account_id
													
												UNION
												
												SELECT
													sd.account_id,
													0 AS total_loan,
													(SUM(sd.loan_deduction + sd.advance_deduction) + get_total_cancelled_loan(sd.account_id)) AS total_paid
												FROM
													hr_salary s,
													distinct_id d,
													hr_salary_data sd
												WHERE
													s.id = sd.salary_id AND
													d.id = s.distinct_id AND
													d.is_deleted <> 'Y' AND
													sd.id <> ''
													{$Where}
												GROUP BY 
													sd.account_id
											) AS t,
											acc_accounts a,
											hr_accounts_data ad,
											branch b
										WHERE
											t.account_id = a.id AND 
											a.id = ad.account_id AND 
											ad.branch_id = b.id
											{$MainWhere}
										GROUP BY
											t.account_id
										ORDER BY 
											a.name
		");

		if (!$ForDashboard)
		{
			$Loans = [];
			while ( $Row = DBQM::fetch_assoc( $Select ) ) {
				if ( $Row['total_loan'] - $Row['total_paid'] > 0 ) {
					$Loans[ $Row['account_id'] ] = $Row['total_loan'] - $Row['total_paid'];
				}
			}

			return $Loans;
		}
		else
			return $Select;
	}

	function getInstallmentAmountByAccountId($AccountId, $LoanAmount, $LoanType)
	{
		$MonthlyInstallment = 0;
		$LoanType = $LoanType == 'L' ? ' AND l.type = "L" ' : ' AND l.type = "A" ';

        $Select = DBQM::query("
                                        SELECT
											l.id AS loan_id,
											l.amount,
											l.monthly_installment,
											l.amount - SUM(IFNULL(li.installment_amount, 0)) AS remaining_amount
										FROM
										    distinct_id di,
											hr_loan l
											LEFT JOIN
												hr_salary_data_loan_installment li
												ON
													l.id = li.loan_id
										WHERE
										    di.id = l.distinct_id AND
											l.account_id = ".escape($AccountId)." AND
											(di.is_deleted <> 'Y')
											{$LoanType}
										GROUP BY
											l.id
										HAVING
											remaining_amount > 0
										ORDER BY
											date ASC
        ");

		while ($Row = DBQM::fetch_assoc($Select))
		{
            $InstallmentAmount = $Row['remaining_amount'] > $Row['monthly_installment'] ? $Row['monthly_installment'] : $Row['remaining_amount'];
            $MonthlyInstallment += $InstallmentAmount;
		}

		return $MonthlyInstallment;
	}

	function getReport($AccountId)
	{
		$Where = UtilsQueryMaker::like("account_id", $AccountId);

		return DBQM::query("
                                    SELECT 
                                        voucher_date,
                                        is_deleted,
                                        is_draft,
										l.*, 
										a.name AS emp_name
									FROM    
										acc_accounts a,
										vw_loan l
									WHERE 
										l.account_id = a.id AND 
									    l.type <> 'A' AND  
									    l.is_cancelled <> 'Y'
										{$Where}
									GROUP BY
										l.id
									HAVING
										l.amount <> sum_amt
		");
	}

	function getAdvanceReport($AccountId)
	{
		$Where = UtilsQueryMaker::like("account_id", $AccountId);

		return DBQM::query("
									SELECT
									    voucher_date,
									    is_deleted,
									    is_draft,
										l.*, 
										a.name AS emp_name
									FROM
										acc_accounts a,
										vw_loan l
									WHERE 
										l.account_id = a.id AND 
										l.amount = l.monthly_installment AND
									    l.type = 'A' AND
									    l.is_cancelled <> 'Y'
										{$Where}
									GROUP BY
										l.id
		");
	}

	function getLoanHistory($AccountId)
	{
		$SelectLoanTaken = DBQM::query("
													SELECT
														l.*,
                                                        sum_amt AS loan_paid
													FROM
														vw_loan l 
													WHERE
														l.account_id = ".escape($AccountId, 'int')." AND
														l.type <> 'A'
													GROUP BY
														l.id
													ORDER BY
														l.id DESC
		");

        $SelectLoanPayBack = DBQM::query("
                                            SELECT
                                                date_time,
                                                l.distinct_id,
                                                l.is_cancelled,
                                                l.amount,
                                                sli.loan_id,
                                                d.is_deleted,
                                                IFNULL(salary_data_id, '') AS salary_data_id,
                                                SUM( sli.installment_amount ) AS loan_deduction 
                                            FROM
                                                hr_loan l,
                                                hr_salary_data_loan_installment sli,
                                                distinct_id d 
                                            WHERE
                                                l.distinct_id = d.id 
                                                AND l.id = sli.loan_id
                                                AND l.account_id = ".escape($AccountId)." 
                                                AND l.type = 'L' 
                                            GROUP BY
                                                l.id 
                                            ORDER BY
                                                date_time DESC
	    ");

		return [$SelectLoanTaken, $SelectLoanPayBack];
	}

    function getLoanDeductionHistory($LoanId)
    {
        return DBQM::query("SELECT l.amount, sli.installment_amount AS loan_deduction, lid.is_deleted, lid.voucher_number, vt.name FROM hr_loan l, hr_salary_data_loan_installment sli, distinct_id lid, vt_voucher_type vt WHERE sli.loan_id = l.id AND lid.id = sli.distinct_id AND vt.id = lid.voucher_type_id AND l.type = 'L' AND l.id = ".escape($LoanId));
    }

    function getAdvanceDeductionHistory($SalaryDataId)
    {
        return DBQM::query("SELECT l.amount, sli.installment_amount AS advance_deduction FROM hr_loan l, hr_salary_data_loan_installment sli WHERE sli.salary_data_id = ".escape($SalaryDataId)." AND sli.loan_id = l.id AND l.type = 'A' ");
    }

    function getAdvanceHistory($AccountId)
    {
        $SelectAdvanceTaken = DBQM::query("
													SELECT
														l.*,
														l.sum_amt as advance_paid,
														l.is_deleted
													FROM
														vw_loan l
													WHERE
														l.account_id = ".escape($AccountId, 'int')." AND
														l.type = 'A' 
													GROUP BY
														l.id
													ORDER BY
														l.id DESC
		");

        $SelectAdvancePayBack = DBQM::query("
                                            SELECT
                                                date_time,
                                                d.is_deleted,
                                                l.is_cancelled,
                                                l.amount,
                                                IFNULL(sli.salary_data_id, '') as salary_data_id,
                                                SUM(sli.installment_amount) AS advance_deduction
                                            FROM
                                                distinct_id d,
                                                hr_loan l
                                                LEFT JOIN 
                                                    hr_salary_data_loan_installment sli 
                                                        ON 
                                                            sli.loan_id = l.id 
                                            WHERE
                                                l.distinct_id = d.id AND
                                                l.account_id = ".escape($AccountId)." AND 
                                                l.type = 'A'
                                            GROUP BY
                                                l.id
                                            ORDER BY
                                                date_time DESC
	    ");

        return [$SelectAdvanceTaken, $SelectAdvancePayBack];
    }

    protected function saveTransactionEntries()
    {
        $oLedger = $this->getLedgerObject();
        $RowDetails = $this->getDetails();

        $oLedger->saveData($RowDetails['cash_bank_id'], "-".$RowDetails['amount'],"Monthly Installment Set at Rs. ".$RowDetails['monthly_installment'], $RowDetails['account_id']);
        $oLedger->saveData($RowDetails['account_id'], $RowDetails['amount'],"Monthly Installment Set at Rs. " . $RowDetails['monthly_installment'], $RowDetails['cash_bank_id']);

        $this->updateVoucherTotal($RowDetails['amount']);
    }
}