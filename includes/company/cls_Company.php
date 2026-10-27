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

class Company
{
    private $CompanyId = 0;
    private $IMAGE_PATH  = 'images/company_logo/';

    function __construct($CompanyId='')
    {
        $this->CompanyId = empty($CompanyId) ? $_SESSION['__company_id'] : $CompanyId;
        $this->IMAGE_PATH = "../".$this->IMAGE_PATH;
    }

    static function setLoginCompanyId($CompanyId)
    {
        $_SESSION['__company_id'] = $CompanyId;
        $_SESSION['__company_name'] = Company::getName($CompanyId);
    }

    static function removeLoginCompanyId()
    {
        unset($_SESSION['__company_id']);
    }

    static function getLoggedInCompanyId()
    {
        return 2;
        /*return $_SESSION['__company_id'];*/
    }

    /**
     * @param $Name
     * @param $Email
     * @param $Web
     * @param $Address
     * @param $Phone
     * @param $Mob1
     * @param $Mob2
     * @param $TagLine
     * @param $NTN
     * @param $CNIC
     * @return int
     */
    function createNew($Name, $Email, $Web, $Address, $Phone, $Mob1, $Mob2, $TagLine, $NTN, $CNIC)
    {
        $RandomId = $this->getCompanyRandomId();
        DBQM::query("INSERT INTO cp_company VALUES (
                                                    0,
                                                    ".escape($Name).",
                                                    ".escape($Email).",
                                                    ".escape($Web).",
                                                    ".escape($Address).",
                                                    ".escape($Phone).",
                                                    ".escape($Mob1).",
                                                    ".escape($Mob2).",
                                                    ".escape($NTN).",
                                                    ".escape($CNIC).",
                                                    '',
                                                    ".escape($TagLine).",
                                                    ".escape(DatabaseHelper::NOW()).",
                                                    ".escape($RandomId, 'int').",
                                                    'Y'
        )");

        return DatabaseHelper::getLastInsertId();
    }

    function saveCompanyModules($CompanyId, $ModuleId)
    {
        DBQM::query("INSERT INTO cp_company_module VALUES (
                                                    0,
                                                    ".escape($CompanyId, 'int').",
                                                    ".escape($ModuleId, 'int')."
        )", true);
    }

    function saveCompanyUser($CompanyId, $UserId)
    {
        $UserType = !empty(Company::getUsersCount($CompanyId)) ? 'S' : 'M';

        DBQM::query("INSERT INTO cp_company_user VALUES (
                                                    0,
                                                    ".escape($UserId, 'int').",
                                                    ".escape($CompanyId, 'int').",
                                                    ".escape($UserType)."
        )", true);
    }

    function update($CompanyId, $Name, $Email, $Web, $Address, $Phone, $Mob1, $Mob2, $TagLine, $NTN, $CNIC)
    {
        DBQM::query("
                    UPDATE
                        cp_company
                    SET
                        name = ".escape($Name).",
                        address = ".escape($Address).",
                        phone = ".escape($Phone).",
                        web = ".escape($Web).",
                        email = ".escape($Email).",
                        mob1 = ".escape($Mob1).",
                        mob2 = ".escape($Mob2).",
                        tag_line = ".escape($TagLine).",
                        ntn = ".escape($NTN).",
                        cnic = ".escape($CNIC)."
                    WHERE
                        id = ".escape($CompanyId)."
        ");
    }

    function uploadImage($CompanyId, $FileTmpName, $FileName)
    {
        $FileName = $CompanyId."__".$FileName.".".ResizeImage::getExtension($FileName);
        move_uploaded_file($FileTmpName, $this->getImagePath().$FileName);

        $oResize = new ResizeImage($this->getImagePath().$FileName);
        $oResize->resizeTo(1080, 1080);
        $oResize->saveImage($this->getImagePath());

        DBQM::query("UPDATE cp_company SET logo = ".escape($FileName)." WHERE id = ".escape($CompanyId, 'int'));
    }

    function activeCompany($CompanyId)
    {
        DBQM::query("UPDATE cp_company SET is_block = 'N' WHERE id = ".escape($CompanyId, 'int'));
    }

    static function getCompanyByRandomId($RandomId)
    {
        $Select = DBQM::query("SELECT id FROM cp_company WHERE random_id = ".escape($RandomId, 'int'));
        return DBQM::result($Select, 0);
    }

    function deleteModules($CompanyId)
    {
        DBQM::query("DELETE FROM cp_company_module WHERE company_id = ".escape($CompanyId, 'int'));
    }

    function deleteUsers($CompanyId)
    {
        DBQM::query("DELETE FROM cp_company_user WHERE company_id = ".escape($CompanyId, 'int'));
    }

    function isAvailableRandomId($RandomId)
    {
        $Select = DBQM::query("SELECT id FROM cp_company WHERE random_id = ".escape($RandomId, 'int'));
        return DBQM::result($Select, 0);
    }

    static function getUsersCount($CompanyId)
    {
        $Select = DBQM::query("SELECT COUNT(id) FROM cp_company_user WHERE company_id = ".escape($CompanyId, 'int'));
        return DBQM::result($Select, 0);
    }

    function getCompanyRandomId()
    {
        $RandomId = mt_rand(123456, 999999);

        if (!empty($this->isAvailableRandomId($RandomId)))
            $RandomId = mt_rand(123456, 999999);

        return $RandomId;
    }

    /**
     * @return int|string
     */
    public function getCompanyId()
    {
        return $this->CompanyId;
    }

    static function getCompanyName($CompanyId='')
    {
        if (!empty($CompanyId))
        {
            $Select = DBQM::query("SELECT name FROM cp_company WHERE id = " . escape($CompanyId, 'int'));
            return DBQM::result($Select, 0);
        }
        else
            return $_SESSION['__company_name'];
    }

    function getDetails($CompanyId='')
    {
        $CompanyId = empty($CompanyId) ? $this->CompanyId : $CompanyId;

        $Select = DBQM::query("SELECT * FROM cp_company WHERE id = ".escape($CompanyId, 'int'));
        return DBQM::fetch_assoc($Select);
    }

    function getCompanyModule($CompanyId='')
    {
        return DBQM::query("SELECT * FROM cp_company_module WHERE id = ".escape($CompanyId, 'int'));
    }

    function getCompanyUsers($CompanyId='')
    {
        return DBQM::query("SELECT * FROM cp_company_user WHERE id = ".escape($CompanyId, 'int'));
    }

	function searchCompany($Name)
	{
		return DBQM::query("SELECT * FROM cp_company WHERE is_block <> 'Y' AND name LIKE ".escape("%".$Name."%")." ORDER BY name LIMIT 50");
	}

	function getCompanyIdByName($Name)
	{
		$Select = DBQM::query("SELECT id FROM cp_company WHERE name = ".escape($Name));
		return DBQM::result($Select, 0);
	}

	static function getName($CompanyId)
	{
		$Select = DBQM::query("SELECT name FROM cp_company WHERE id = ".escape($CompanyId));
		return DBQM::result($Select, 0);
	}

    public function getImagePath()
    {
        return $this->IMAGE_PATH;
    }

    function getReport()
    {
        $Query = "
                        SELECT
                            *
                        FROM
                            cp_company
                        ORDER BY
                            name
            ";

        return DBQM::query($Query);
    }

    function getCompanySelectOption($Selected='')
    {
        $Select = DBQM::query("SELECT * FROM cp_company WHERE is_block <> 'Y' ORDER BY name");

        while ($Row = DBQM::fetch_assoc($Select))
        {
            ?>
            <option value="<?=$Row['id']?>" <?=$Row['id'] == $Selected ? 'selected' : '' ?>><?=$Row['name']?></option>
            <?
        }
    }
} 