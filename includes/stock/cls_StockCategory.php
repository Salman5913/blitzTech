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

class StockCategory extends BasicForm
{
    public function __construct()
    {
        parent::__construct("st_stock_category", "", "", "Stock Category");
        $this->setAccessAreas(CompanyModule::STOCK, "Category");
    }

	function getCategoryIdByName($Name)
	{
		$Select = DBQM::query("SELECT id FROM st_stock_category WHERE name = ".escape($Name)." AND company_id = ".escape(Company::getLoggedInCompanyId()));
		return DBQM::result($Select, 0);
	}

    function isCategoryAvailable($Name)
    {
        $Select = DBQM::query("SELECT id FROM st_stock_category WHERE name = ".escape($Name));
        return DBQM::result($Select, 0);
    }

	static function getName($CategoryId)
	{
		$Select = DBQM::query("SELECT name FROM st_stock_category WHERE id = ".escape($CategoryId));
		return DBQM::result($Select, 0);
	}

    function searchStockCategory($Name)
    {
        return DBQM::query("SELECT * FROM st_stock_category WHERE name LIKE ".escape("%".$Name."%")." AND company_id = ".escape(Company::getLoggedInCompanyId(), 'int')." ORDER BY name LIMIT 50");
    }

    function getReport()
    {
        $Query = "
                        SELECT
                            *
                        FROM
                            st_stock_category
                        ORDER BY
                            name
            ";

        return DBQM::query($Query);
    }

    function getAll($SearchTerm='')
    {
        return DBQM::query("SELECT * FROM st_stock_category WHERE name LIKE ".escape("%".$SearchTerm."%")." AND company_id = ".escape(Company::getLoggedInCompanyId())." ORDER BY name");
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
                            @pv := parent_category_id AS parent_id
                        FROM
                            st_stock_category
                            JOIN (SELECT @pv := ".escape($Id, 'int').") as tmp
                        WHERE
                            id = @pv
                        ORDER BY
                            id DESC;
	    ");
    }

    static function getStockIdByName($Name)
    {
        $Select = DBQM::query("SELECT id FROM st_stock_category WHERE name = ".escape($Name)." AND company_id = ".escape(Company::getLoggedInCompanyId(), 'int'));
        return DBQM::result($Select, 0);
    }
} 