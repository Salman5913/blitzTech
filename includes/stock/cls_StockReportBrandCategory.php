<?php
/**
 * Created by PhpStorm.
 * User: musta
 * Date: 12/12/2018
 * Time: 4:33 PM
 */

class StockReportBrandCategory
{
	function getStockBrand()
	{
		return DBQM::query("
    	                    SELECT
    	                        s.brand,
    	                        SUM(stl.qty) AS brand_qty 
                            FROM
                            	st_stock s,
                            	st_stock_ledger stl,
                                st_stock_batch stb
                            WHERE 
                            	s.id = stb.stock_id AND
                                stb.id = stl.batch_id
                            GROUP BY 
                            	s.brand
                            HAVING
                            	brand_qty > 0
    	");
	}

	function getAllCategories()
	{
		return DBQM::query("SELECT DISTINCT stc.name AS category FROM st_stock s, st_stock_category stc WHERE s.category_id = stc.id ORDER BY category");
	}

	function getStockCategory($BrandName)
	{
		return DBQM::query("
    	                    SELECT
    	                        stc.name AS category,
    	                        SUM(stl.qty) AS category_qty
                            FROM
                            	st_stock s,
                            	st_stock_ledger stl,
                                st_stock_batch stb, 
                                st_stock_category stc
                            WHERE 
                            	s.id = stb.stock_id AND
                                stb.id = stl.batch_id AND
                                s.category_id = stc.id AND
                            	s.brand = ".escape($BrandName)."
                            GROUP BY 
                            	stc.id
                            HAVING
                            	category_qty > 0
    	");
	}

	function getStockCategoryByVoucherType($BrandName, $DateFrom, $DateTill, $VoucherTypeId, $Column)
	{
		$Where = '';
		$Where .= UtilsQueryMaker::date("stl.date_time", $DateFrom, $DateTill);

		return DBQM::query("
    	                    SELECT
    	                        stc.name AS category,
    	                        SUM(stl.qty) AS category_qty,
    	                        SUM(stl.qty * {$Column}) AS stock_value
                            FROM
                            	st_stock s,
                            	st_stock_ledger stl,
                                st_stock_batch stb, 
                                st_stock_category stc
                            WHERE 
                            	s.id = stb.stock_id AND
                                stb.id = stl.batch_id AND
                                s.category_id = stc.id AND
                            	stl.voucher_type_id IN (".$VoucherTypeId.") AND
                            	s.brand = ".escape($BrandName)."
                            	{$Where}
                            GROUP BY 
                            	stc.id
    	");
	}
}