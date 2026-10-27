<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 28/12/2014
 * Time: 9:35 AM
 */

class ChartOfAccountCategory extends BasicForm
{
    public function __construct()
    {
        parent::__construct("acc_chart_of_account", "", "", "Chart Of Account");
        $this->setAccessAreas(CompanyModule::CHART_OF_ACCOUNT, 'Categories Management');
    }

	function getAll($SearchTerm='')
	{
		return DBQM::query("SELECT * FROM acc_chart_of_account WHERE name LIKE ".escape("%".$SearchTerm."%")." AND company_id = ".escape(Company::getLoggedInCompanyId())." ORDER BY name");
	}

	/**
	 * Returns complete list of ancestors of given child (id, name, parent_id) in reverse order
	 * @param $Id
	 *
	 * @return mysqli_result
	 */
	function getAllAncestors($Id)
	{
		return DBQM::query("
                        SELECT
                            id,
                            `name`,
                            @pv := parent_id AS parent_id
                        FROM
                            acc_chart_of_account
                            JOIN (SELECT @pv := ".escape($Id, 'int').") as tmp
                        WHERE
                            id = @pv
                        ORDER BY
                            id DESC;
	    ");
	}

	/**
	 * Returns complete list of ancestors of given child (id, name, parent_id) in reverse order
	 *
	 * @param $AccountId
	 *
	 * @return mysqli_result
	 */
	function getAllAncestorsByAccountId($AccountId)
	{
		return DBQM::query("
                        SELECT
                            id,
                            `name`,
                            @pv := parent_id AS parent_id
                        FROM
                            acc_chart_of_account
                            JOIN (SELECT @pv := (SELECT coa_id FROM acc_accounts WHERE id = ".escape($AccountId, 'int').")) as tmp
                        WHERE
                            id = @pv
                        ORDER BY
                            id DESC;
	    ");
	}

	function deleteCategory($Id)
	{
		$SelectSubGroups = DBQM::query("SELECT get_children_ids_full(".escape($Id, 'int').")");
		$SubGroups = DBQM::result($SelectSubGroups, 0);

        if ($SubGroups != $Id) // there is no sub group
        {
            $Select = DBQM::query("SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM acc_chart_of_account WHERE FIND_IN_SET (id, ".escape($SubGroups).")");
            ErrorHandler::setError( "Cannot delete this because subgroup exists - ".DBQM::result($Select, 0) );

            return false;
        }
        else {
            DBQM::query( "DELETE FROM acc_chart_of_account WHERE id = " . escape( $Id ) );
            return true;
        }
    }

	static function getNameById($COAId)
	{
		$Select = DBQM::query("SELECT name FROM acc_chart_of_account WHERE id = ".escape($COAId, 'int'));
		return DBQM::result($Select, 0);
	}

	static function getCOAIdByName($Name)
	{
		$Select = DBQM::query("SELECT id FROM acc_chart_of_account WHERE name = ".escape($Name)." AND company_id = ".escape(Company::getLoggedInCompanyId(), 'int'));
		return DBQM::result($Select, 0);
	}

    static function getIdByNameNCompanyId($Name, $CompanyId)
    {
        $Select = DBQM::query("SELECT id FROM acc_chart_of_account WHERE name = ".escape($Name)." AND company_id = ".escape($CompanyId, 'int'));
        return DBQM::result($Select, 0);
    }

	static function isCOAAvailable($Name)
	{
		$Select = DBQM::query("SELECT id FROM acc_chart_of_account WHERE name = ".escape($Name)." AND company_id = ".escape(Company::getLoggedInCompanyId(), 'int'));

		return DatabaseHelper::isRecordSetEmpty($Select);
	}

	function getAllLevel1()
	{
		return [1 => "Asset", "Liability", "Equity", "Revenue", "Expense"];
	}
} 