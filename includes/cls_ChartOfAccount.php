<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 024, 12/24/2014
 * Time: 2:09 PM
 */

class ChartOfAccount extends BasicForm
{
    const PROVIDENT_ACCOUNT = ' - Provident Fund';
    const MANAGEMENT_STAFF_VAL = 'M';
    const WORKER_STAFF_VAL = 'W';
    const WAREHOUSE_STAFF_VAL = 'WH';
    const SALES_PERSON = 'SP';

    const IND_BIO_DATA = "bio_data";
    const IND_HR_ACCOUNTS_DATA = "account_data";
    const IND_ACC_ACCOUNTS = 'acc_accounts';
    const IND_BANK_STATEMENT_IDENTIFIER = 'BI';

    const HOURS_IN_A_DAY = 8;

    public function __construct()
    {
        parent::__construct("acc_accounts",
            [
                self::IND_BIO_DATA => "acc_bio_data",
                self::IND_HR_ACCOUNTS_DATA => "hr_accounts_data",
                self::IND_BANK_STATEMENT_IDENTIFIER => "acc_bank_statement_identifier"
            ],
            "account_id", "Transaction Account");

        $this->setAccessAreas(CompanyModule::CHART_OF_ACCOUNT, 'Transaction Account');
    }

	function delete($AccountId)
	{
		DBQM::query("DELETE FROM acc_accounts WHERE id = ".escape($AccountId, 'int'));
		return DatabaseHelper::isRecordDeletedOrUpdatedSuccessfully();
	}

	function getAccountIdByName($Name)
	{
		$Select = DBQM::query("SELECT id FROM vw_ac_accounts WHERE ac_name = ".escape($Name)." AND company_id = ".escape(Company::getLoggedInCompanyId(), 'int'));
		return DBQM::result($Select, 0);
	}

    function getAccountIdByNameWithoutCode($Name)
    {
        $Select = DBQM::query("SELECT id FROM vw_ac_accounts WHERE name = ".escape($Name)." AND company_id = ".escape(Company::getLoggedInCompanyId(), 'int'));
        return DBQM::result($Select, 0);
    }

	function searchAccount($Name): mysqli_result
    {
        return DBQM::query("SELECT * FROM vw_ac_accounts WHERE show_in_ac = 'Y' AND ac_name LIKE ".escape("%".$Name."%")." AND company_id = ".escape(Company::getLoggedInCompanyId(), 'int')."  ORDER BY ac_name LIMIT 50");
	}

    static function getMobileNumber($AccountId)
    {
        $Select = DBQM::query("SELECT mob FROM acc_bio_data WHERE account_id = ".escape($AccountId));
        return DBQM::result($Select, 0);
    }


	function searchAccountByCOAId($Name, array $COAId): mysqli_result
    {
	    $COAs = [];
	    foreach ($COAId AS $Id)
        {
	        $Select = DBQM::query("SELECT get_children_ids_full(".escape($Id).")");
	        $COAs = array_merge($COAs, explode(",", DBQM::result($Select, 0)));
        }

		return DBQM::query("SELECT * FROM vw_ac_accounts WHERE show_in_ac = 'Y' AND ac_name LIKE ".escape("%".$Name."%")." AND coa_id IN (".implode(", ", array_map("escape", $COAs)).") AND company_id = ".escape(Company::getLoggedInCompanyId())." ORDER BY ac_name LIMIT 50");
	}

	function getReport($AccountId, $COAId, $Mobile, $Email)
	{
		$Query = "
                        SELECT
                            a.*,
                            coa.name AS category_name
                        FROM
                            acc_chart_of_account coa,
                            acc_accounts a
                            LEFT JOIN
                                acc_bio_data b
                                ON 
                                    b.account_id = a.id AND
                                    a.company_id = ".escape(Company::getLoggedInCompanyId())." AND
                                    (b.mob LIKE ".escape("%".$Mobile)." OR b.mob2 LIKE ".escape("%".$Mobile)." )
                        WHERE
                            a.coa_id = coa.id AND
                            a.is_editable = 'Y'
            ";

		$Where = '';

		$Where .= UtilsQueryMaker::equal("a.id", $AccountId);
        $Where .= UtilsQueryMaker::like("b.email1", $Email);

		if ($COAId != "")
        {
	        $SelectChildIds = DBQM::query("SELECT get_children_ids_full(".$COAId.")");
	        $ChildIds = DBQM::result($SelectChildIds, 0);

            $Where .= " AND a.coa_id IN (".$ChildIds.") ";
        }

		$Query .= $Where;

		$Query .= " ORDER BY a.name";

		return DBQM::query($Query);
	}

	/**
	 * Check if user can access cash, cheque and bank ledger accounts
	 * @param $AccountId
	 *
	 * @return bool
	 */
	static function canUserAccess($AccountId)
	{
	    $oConstantCategory = new ConstantCategory();

		if (!SecurityShell::isAccess(CompanyModule::ACCOUNTING, "Cheque And Bank Access"))
		{
			$oCharOfAccountCategory = new ChartOfAccountCategory();
			$Select = $oCharOfAccountCategory->getAllAncestorsByAccountId( $AccountId );

			while ( $Row = DBQM::fetch_assoc( $Select ) ) {
				if ( $Row['id'] == $oConstantCategory->getBank()) {
					return false;
				}
			}
		}

		if (!SecurityShell::isAccess(CompanyModule::ACCOUNTING, "Cash Ledger Access"))
		{
			$oCharOfAccountCategory = new ChartOfAccountCategory();
			$Select = $oCharOfAccountCategory->getAllAncestorsByAccountId( $AccountId );

			while ( $Row = DBQM::fetch_assoc( $Select ) ) {
				if ($Row['id'] == $oConstantCategory->getCashInHand()) {
					return false;
				}
			}
		}
		return true;
	}

    static function getName($AccountId)
	{
		$Select = DBQM::query("SELECT ac_name FROM vw_ac_accounts WHERE id = ".escape($AccountId));
		return DBQM::result($Select, 0);
	}

    static function getAdditionalInfo($AccountId)
    {
        $Select = DBQM::query("SELECT * FROM acc_bio_data WHERE account_id = ".escape($AccountId));
        return DBQM::fetch_assoc($Select);
    }

    function getAllEmployeeList()
    {
        return DBQM::query("
                                    SELECT 
                                        a.`name`, 
                                        hrad.*, 
                                        b.`name` AS branch_name 
                                    FROM 
                                        hr_accounts_data hrad, 
                                        acc_accounts a, 
                                        branch b 
                                    WHERE 
                                        hrad.account_id = a.id AND 
                                        b.id = hrad.branch_id AND 
                                        hrad.is_active <> 'N'
                                    ORDER BY 
                                      b.name ASC
        ");
    }

    function getAllEmployees()
    {
        return DBQM::query("SELECT a.id FROM vw_ac_accounts a, hr_accounts_data hrad  WHERE  hrad.account_id = a.id AND hrad.is_active <> 'N' AND  coa_id = ".escape(getConstantCategory()->getEmployee())." ");
    }

    static function getIdByAccountId($AccountId)
    {
        $Select = DBQM::query("SELECT id FROM hr_accounts_data WHERE account_id = ".escape($AccountId));
        return DBQM::result($Select, 0);
    }

    static function getEmployeeIdByAccountId($AccountId)
    {
        $Select = DBQM::query("SELECT id FROM hr_accounts_data WHERE account_id = ".escape($AccountId));
        return DBQM::result($Select, 0);
    }

    function getHRData($AccountId)
    {
        $Select = DBQM::query("SELECT * FROM hr_accounts_data WHERE account_id = ".escape($AccountId)." ");
        return DBQM::fetch_assoc($Select);
    }

    static function getCurrencyCode($AccountId)
    {
        $Select = DBQM::query("SELECT currency_code FROM acc_accounts WHERE id = ".escape($AccountId));
        return DBQM::result($Select, 0);
    }

    static function getSalary($AccountId)
    {
        $Select = DBQM::query("SELECT basic_salary FROM hr_accounts_data WHERE account_id = ".escape($AccountId));
        return (float)DBQM::result($Select, 0);
    }

    /********************* HR Data *********************************/

    function createAccountHRData($AccountId, $EmpId, $BranchId, $BasicSalary, $canOverTime='N', $CNIC='', $JoinDate='', $isActive='Y')
    {
        $EmpAccountId = self::getAccountIdByEmpId($EmpId, $BranchId);

        if (empty($EmpAccountId))
        {
            DBQM::query("INSERT INTO hr_accounts_data VALUES (
                                                        0,
                                                        " . escape($AccountId) . ",
                                                        " . escape($EmpId, 'int') . ",
                                                        " . escape($BranchId, 'int') . ",
                                                        " . escape($BasicSalary, 'double') . ",
                                                        " . escape($canOverTime) . ",
                                                        " . escape($CNIC) . ",
                                                        " . escape(DateEncode($JoinDate)) . ",
                                                        " . escape($isActive) . "
            )");

            return DatabaseHelper::getLastInsertId();
        }
        else
        {
            ErrorHandler::setError("Unable to create HR Data of ".ChartOfAccount::getName($AccountId)." as Account with same Employee ID and Branch Id already exists.");
            return false;
        }
    }

    function updateAccountHRData($AccountId, $EmpId, $BranchId, $BasicSalary, $canOverTime, $CNIC, $JoinDate, $isActive)
    {
        $EmpAccountId = self::getAccountIdByEmpId($EmpId, $BranchId);

        if (empty(self::getEmployeeIdByAccountId($AccountId)))
        {
            $this->createAccountHRData($AccountId, $EmpId, $BranchId, $BasicSalary, $canOverTime, $CNIC, $JoinDate, $isActive);
        }
        elseif (empty($EmpAccountId) or $EmpAccountId == $AccountId)
        {
            DBQM::query( " 
                    UPDATE
                        hr_accounts_data
                    SET
                        emp_id = " . escape( $EmpId ,'int' ).",
                        branch_id = " . escape( $BranchId, 'int' ).",
                        basic_salary = ". escape( $BasicSalary ).",
                        can_overtime = ". escape( $canOverTime ).",
                        cnic = " . escape( $CNIC ) . ",
                        join_date = " . escape( DateEncode($JoinDate )).",
                        is_active = ".escape($isActive)."
                    WHERE
                        account_id = ".escape( $AccountId, 'int' )."
            ");

            return DatabaseHelper::getLastInsertId();
        }
        else
        {
            ErrorHandler::setError("Unable to update HR Data of ".ChartOfAccount::getName($AccountId)." as Account with same Employee ID and Branch Id already exists.");
            return false;
        }
    }

    static function getAccountIdByEmpId($EmpId, $BranchId)
    {
        $Select = DBQM::query("SELECT account_id FROM hr_accounts_data WHERE emp_id = ".escape($EmpId)." AND branch_id = ".escape($BranchId));
        return DBQM::result($Select, 0);
    }

    function getAllEmployeesByBranchId($BranchId)
    {
        return DBQM::query("SELECT a.id FROM acc_accounts a, hr_accounts_data hrad WHERE hrad.account_id = a.id AND hrad.is_active <> 'N' AND hrad.branch_id = ".escape($BranchId)." AND coa_id = ".escape(getConstantCategory()->getEmployee())." ");
    }

    static function getBasicSalaryByAccountId($AccountId)
    {
        $Select = DBQM::query("SELECT basic_salary FROM hr_accounts_data WHERE account_id = ".escape($AccountId));
        return DBQM::result($Select, 0);
    }

    function createSalaryHistory($AccountId, $Salary)
    {
        $Select = DBQM::query("SELECT id FROM hr_accounts_salary_history WHERE account_id = ".escape($AccountId, 'int')." AND salary = ".escape($Salary, 'double')." ORDER BY id DESC LIMIT 1");

        if (DatabaseHelper::isRecordSetEmpty($Select) AND $Salary > 0)
        {
            DBQM::query("INSERT INTO hr_accounts_salary_history VALUES (
                                                                    0, 
                                                                    ".escape($AccountId).", 
                                                                    ".escape(SecurityShell::getLoggedInUserId()).", 
                                                                    ".escape(DatabaseHelper::NOW()).", 
                                                                    ".escape($Salary, 'double').")
            ");
        }
    }

    function getSalaryHistory($AccountId)
    {
        $Where = UtilsQueryMaker::equal("account_id", $AccountId);
        return DBQM::query("SELECT hs.*, a.ac_name as name FROM hr_accounts_salary_history hs, vw_ac_accounts a WHERE hs.account_id = a.id AND account_id <> '' {$Where} ORDER BY id DESC");
    }

    static function getAccountIdByNameANDEmpId($EmpId, $AccountName)
    {
        $Select = DBQM::query("SELECT hrad.id FROM hr_accounts_data hrad, acc_accounts a WHERE hrad.account_id = a.id AND a.name = ".escape($AccountName)." AND hrad.emp_id = ".escape($EmpId));
        return DBQM::result($Select, 0);
    }

    static function getNameByEmpId($EmpId)
    {
        $Select = DBQM::query("SELECT a.name FROM hr_accounts_data hrad, acc_accounts a WHERE hrad.account_id = a.id AND hrad.emp_id = ".escape($EmpId));
        return DBQM::result($Select, 0);
    }
    /********************* HR Data *********************************/
}