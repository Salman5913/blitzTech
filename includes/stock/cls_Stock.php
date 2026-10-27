<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 024, 12/24/2014
 * Time: 5:17 PM
 */

class Stock extends BasicForm
{
    private $IMAGE_PATH  = 'assets/images/ProductImages/';

    const TABLE_RECIPE = 'st_mnf_recipe';
    const TABLE_ATTRIBUTE = 'st_stock_attribute';

    function __construct()
    {
        parent::__construct(
            "st_stock",
            [
                self::TABLE_ATTRIBUTE => 'st_stock_attribute'
            ],
            'stock_id',
            'Inventory Profile'
        );
        $this->IMAGE_PATH = "../".$this->IMAGE_PATH;
        $this->setAccessAreas(CompanyModule::STOCK, "Item Profile");
    }

    function delete($StockId)
	{
		DBQM::query("DELETE FROM st_stock WHERE id = ".escape($StockId));
	}

    function getStockIdByName($Name)
    {
        $Select = DBQM::query("SELECT id, attribute_id FROM vw_ac_stock WHERE (ac_stock = ".escape($Name)." OR barcode = ".escape($Name)." OR barcode = ".escape($Name).") AND company_id = ".escape(Company::getLoggedInCompanyId()));

        if (!DatabaseHelper::isRecordSetEmpty($Select))
            return self::joinStockIdAttributeId(DBQM::result($Select, 0), DBQM::result($Select, 0, 1));
        else
            return '';
    }

    function getIdByName($Name)
    {
        $Select = DBQM::query("SELECT id FROM st_stock WHERE name = ".escape($Name));
        return DBQM::result($Select, 0);
    }

    function searchStock($Name)
    {
        return DBQM::query("SELECT * FROM vw_ac_stock WHERE ac_stock LIKE ".escape("%".$Name."%")." AND company_id = ".escape(Company::getLoggedInCompanyId())." LIMIT 50");
    }

    static function getName($StockId, $AttributeId)
    {
        $Where = UtilsQueryMaker::equal("attribute_id", $AttributeId);

        $Select = DBQM::query("SELECT ac_stock FROM vw_ac_stock WHERE id = ".escape($StockId)." ".$Where);
        return DBQM::result($Select, 0);
    }

    static function getSP($StockId, $AttributeId)
    {
        $Where = UtilsQueryMaker::equal("attribute_id", $AttributeId);

        $Select = DBQM::query("SELECT selling_price FROM vw_ac_stock WHERE id = ".escape($StockId)." ".$Where);
        $Row = DBQM::fetch_assoc($Select);

        return $Row['selling_price'];
    }

    function getReport($CombinedStockId, $CategoryId, $Brand)
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombinedStockId);
        $Query = "
                        SELECT
                            s.*,
                            stc.name AS category_name
                        FROM
                            vw_ac_stock s,
                            st_stock_category stc
                        WHERE
                            stc.id = s.category_id AND 
                            s.company_id = ".escape(Company::getLoggedInCompanyId(), 'int')."
            ";

        $Where = '';

        $Where .= UtilsQueryMaker::equal("s.id", $StockId);
        $Where .= UtilsQueryMaker::like("s.attribute_id", $AttributeId);
        $Where .= UtilsQueryMaker::equal("stc.id", $CategoryId);
        $Where .= UtilsQueryMaker::equal("s.brand", $Brand);

        $Query .= $Where;

        $Query .= " ORDER BY ac_stock";

        return DBQM::query($Query);
    }

    function getAllStockWithoutAttribute($CategoryId, $Brand)
    {
        $Query = "
            SELECT
                st.id,
                st.name AS actual_stock_name,
                stc.name AS category_name
            FROM
                st_stock st
                LEFT JOIN st_stock_category stc ON stc.id = st.category_id
            WHERE
                st.company_id = ".escape(Company::getLoggedInCompanyId(), 'int')."
            ";

        $Where = '';

        $Where .= UtilsQueryMaker::equal("stc.id", $CategoryId);
        $Where .= UtilsQueryMaker::equal("st.brand", $Brand);

        $Query .= $Where;

        $Query .= " ORDER BY actual_stock_name";

        return DBQM::query($Query);
    }

    /**
     * @return string
     */
    public function getImagePath()
    {
        return $this->IMAGE_PATH;
    }

    function getStockAdditionalDetails($StockId, $AttributeId, $DistinctId, $BranchId): array
    {
        /* ************** get Quantity ************** */
        $Where = UtilsQueryMaker::equal("stb.attribute_id", $AttributeId);

        $SelectQty = DBQM::query(" 
                                      SELECT
                                            SUM(sl.qty) AS sum_qty,
                                            get_avg_cost(stb.stock_id, stb.attribute_id) AS cost
                                      FROM
                                            st_stock_ledger sl,
                                            st_stock_batch stb
                                      WHERE
                                            sl.batch_id = stb.id AND
                                            sl.branch_id = ".escape($BranchId)." AND
                                            sl.distinct_id <> ".escape($DistinctId)." AND
                                            stb.stock_id = ".escape($StockId)."
                                            {$Where}
        ");

        $SellingPrice = Stock::getSP($StockId, $AttributeId);
        $AvailableQty = DBQM::result($SelectQty, 0);
        $CostPrice = DBQM::result($SelectQty, 0, 1);

        /* ********** Return All Values ********** */
        return array($AvailableQty, $SellingPrice, $CostPrice);
    }

    function getAllAvailableAttributesByStockId($StockId)
    {
        return DBQM::query("SELECT * FROM st_stock_attribute WHERE stock_id = ".escape($StockId));
    }

    static function joinStockIdAttributeId($StockId, $AttributeId)
    {
        return $StockId."::".$AttributeId;
    }

    static function splitStockIdAttributeId($CombineId)
    {
        return explode("::", $CombineId);
    }

    static function getStockNameQtyNotAdjusted($Message)
    {
        $Message = explode("Stock Name: ","Qty not adjusted properly, either stock ran out or some other problem occurred. Stock Name: HAIER REF 622IBS/ICG/IBG - 1 Qty unadjusted!");
        $StocksQty = explode(" - ", $Message[1]);
        return $StocksQty[0];
    }
} 