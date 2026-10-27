<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 28/12/2014
 * Time: 11:49 AM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("connection/localhost.php");

class Branch extends BasicForm
{
    const BRANCH_TYPE_HEADQUARTER = 'h';
    const BRANCH_TYPE_WAREHOUSE = 'w';
    const BRANCH_TYPE_BRANCH = 'b';

    private $BranchId = 0;

    function __construct($BranchId='')
    {
        parent::__construct("branch", [], '', 'Branch');
        $this->BranchId = empty($BranchId) ? $_SESSION['__branch_id'] : $BranchId;

        $this->setAccessAreas(CompanyModule::STOCK, 'Branch');
    }

    static function setLoginBranchId($BranchId)
    {
        $_SESSION['__branch_id'] = $BranchId;

        $oBranch = new Branch();
        $_SESSION['__branch_name'] = $oBranch->getBranchName($BranchId);
    }

    static function removeLoginBranchId()
    {
        unset($_SESSION['__branch_id']);
    }

    /**
     * @return int|string
     */
    public function getBranchId()
    {
        return $this->BranchId;
    }

    function getBranchName($BranchId='')
    {
        if (!empty($BranchId))
        {
            $Select = DBQM::query("SELECT name FROM branch WHERE id = " . escape($BranchId, 'int'));
            return DBQM::result($Select, 0);
        }
        else
            return $_SESSION['__branch_name'];
    }

	static function getName($BranchId)
	{
		$Select = DBQM::query("SELECT name FROM branch WHERE id = ".escape($BranchId));
		return DBQM::result($Select, 0);
	}

    function isBranchDisabled()
    {
        $Select = DBQM::query("SELECT branch_disable FROM sh_login WHERE id = ".escape(SecurityShell::getLoggedInUserId()));
        return DBQM::result($Select, 0);
    }

    static function getBranchIdByUserId($UserId)
    {
        $Select = DBQM::query("SELECT branch_id FROM sh_login WHERE id = ".escape($UserId));
        return DBQM::result($Select, 0);
    }

	function getBranchIdByName($BranchName)
    {
        $Select = DBQM::query("SELECT id FROM branch WHERE name = ".escape($BranchName));
        return DBQM::result($Select, 0);
    }


    static function isAccessYourOwnData($Column)
    {
        $Where = '';
        if(SecurityShell::isAccess('Settings', 'View Assign Branch Data'))
            $Where = UtilsQueryMaker::equal($Column, SecurityShell::getLoggedInUserId());
        return $Where;
    }

    function getReport()
    {
        $Query = "
                        SELECT
                            *
                        FROM
                            branch
                        WHERE   
                            company_id = ".escape(Company::getLoggedInCompanyId(), 'int')."
                        ORDER BY
                            name
            ";

        return DBQM::query($Query);
    }

    function getTypeAbbr($Type)
    {
        switch ($Type)
        {
            case self::BRANCH_TYPE_BRANCH:
                return "Branch";
            case self::BRANCH_TYPE_HEADQUARTER:
                return "Headquarter";
            case self::BRANCH_TYPE_WAREHOUSE:
                return "Warehouse";
            default:
                return '';
        }
    }

    function getDetailsById($BranchId='')
    {
        $Select = DBQM::query("SELECT * FROM branch WHERE id = ".escape($BranchId, 'int'));
        return DBQM::fetch_assoc($Select);
    }

    function getBranchSelectOption($Selected='', $BranchOnly=false)
    {
        $Select = DBQM::query("SELECT * FROM branch WHERE company_id = ".escape(Company::getLoggedInCompanyId())." ORDER BY name");

        while ($Row = DBQM::fetch_assoc($Select))
        {
            ?>
            <option value="<?=$Row['id']?>" <?=$Row['id'] == $Selected ? 'selected' : '' ?>><?=$Row['name']?></option>
            <?
        }
    }
} 