<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 27/12/2014
 * Time: 9:22 AM
 */

class StockBatch
{
    function createNewBatch($StockId, $AttributeId, $BatchNumber, $Qty, $Cost, $BatchId=0)
    {
        DBQM::query("INSERT INTO st_stock_batch VALUES (
                                                              ".escape($BatchId).",
                                                              ".escape(Year::getYearId(), 'int').",
                                                              ".escape($StockId, 'int').",
                                                              ".escape($AttributeId, 'int', true).",
                                                              ".escape($BatchNumber, 'int').",
                                                              ".escape($Qty, 'double').",
                                                              ".escape($Cost, 'double')."
        )");

        return DatabaseHelper::getLastInsertId();
    }

    function deleteBatch($BatchId)
    {
        DBQM::query("DELETE FROM st_stock_batch WHERE id = ".escape($BatchId, 'int'));
    }

    function getNextBatchNumber($StockId)
    {
        $Select = DBQM::query("SELECT MAX(batch_number) FROM st_stock_batch WHERE stock_id = ".escape($StockId, 'int')." AND year_id = ".escape(Year::getYearId(), 'int'));
        return (int)DBQM::result($Select, 0) + 1;
    }

    static function getBatchNumber($BatchId)
    {
        $Select = DBQM::query("SELECT batch_number FROM st_stock_batch WHERE id = ".escape($BatchId, 'int'));
        return DBQM::result($Select, 0);
    }

    static function getStockBatchDetails($BatchId)
    {
        $Select = DBQM::query("SELECT * FROM st_stock_batch WHERE id = ".escape($BatchId, 'int')." AND year_id = ".escape(Year::getYearId(), 'int'));
        return DBQM::fetch_assoc($Select);
    }

    function getStockQtyByBranch($StockName)
    {
        return DBQM::query("
									SELECT 
										SUM(sl.qty) AS branch_qty, 
										b.name 
									FROM 
										st_stock_ledger sl,
									    st_stock_batch sb,
										branch b,
										st_stock s 
									WHERE 
										sb.id = sl.batch_id AND 
										s.id = sb.stock_id AND 
										b.id = sl.branch_id AND 
										s.name = ".escape($StockName, 'int')." 
									GROUP BY 
										sl.branch_id 
									ORDER BY 
										b.name ASC
		");
    }

    function getStockQtyByBatch($StockName)
    {
    	return DBQM::query("
									SELECT 
										SUM(qty) AS batch_qty, 
										sb.batch_number
									FROM 
										st_stock_batch sb,
										st_stock s 
									WHERE 
										s.id = sb.stock_id AND 
										s.name = ".escape($StockName, 'int')." AND
										sb.year_id = ".escape(Year::getYearId())."
									GROUP BY 
										sb.batch_number 
									ORDER BY 
										sb.batch_number
		");
    }

    function getFullBranchStock($BranchId)
    {
        return DBQM::query("
                        SELECT
                            id,
                            stock_name,
                            category,
                            SUM(total_qty) AS total_qty,
                            SUM(selling_price) AS selling_price
                        FROM
                        (
                            SELECT
                                s.id,
                                s.name AS stock_name,
                                s.category,
                                SUM(sl.qty) AS total_qty,
                                0 AS selling_price
                            FROM
                                st_stock_ledger sl,
                                st_stock_batch sb,
                                st_stock s
                            WHERE
                                sb.id = sl.batch_id AND
                                s.id = sb.stock_id AND
                                sl.branch_id = ".escape($BranchId)."
                            GROUP BY
                                s.id

                            UNION

                            SELECT
                                s.id,
                                s.name AS stock_name,
                                s.category,
                                0 AS total_qty,
                                s.sp AS selling_price
                            FROM
                                st_stock_ledger sl,
                                st_stock_batch sb,
                                st_stock s
                            WHERE
                                sb.id = sl.batch_id AND
                                s.id = sb.stock_id AND
                                sl.voucher_type_id IN (".escape(VoucherType::OPS).", ".escape(VoucherType::PIV).")
                            GROUP BY
                                s.id
                        ) AS tmp
                        GROUP BY
                            id
                        HAVING
                            total_qty <> 0
                        ORDER BY
                            stock_name ASC
		");
    }

    function getReport($ItemName)
    {
        $Query = "
                        SELECT
                            sl.*,
                            b.name AS branch_name,
                            vt.abbr AS voucher_name,
                            vt.name AS voucher_type, 
                            '' AS customer_name,
                            sb.cost
                        FROM
                            st_stock s,
                            branch b, 
                            vt_voucher_type vt,
                            st_stock_ledger sl,
                            st_stock_batch sb
                        WHERE
                            sb.id = sl.batch_id AND 
                            s.id = sb.stock_id AND
                            b.id = sl.branch_id AND
                            vt.id = sl.voucher_type_id
            ";

        $Where = UtilsQueryMaker::equal("s.name", $ItemName);

        $Query .= $Where;
        $Query .= " GROUP BY sl.id ORDER BY sl.date_time ASC";

        return DBQM::query($Query);
    }

    function getPurchaseQtyOfBatch($BatchId)
    {
        $SelectPurchaseQty = DBQM::query("SELECT qty FROM st_stock_batch WHERE id = ".escape($BatchId)." AND year_id = ".escape(Year::getYearId()));
        return DBQM::result($SelectPurchaseQty, 0);
    }

    function getStockHoldingValue($CompanyName='')
    {
        $Where = UtilsQueryMaker::equal("s.brand", $CompanyName);
        return DBQM::query("
    	                    SELECT
    	                    	s.name,
    	                    	SUM(sl.qty) AS total_qty,
    	                        SUM(sl.qty * sb.cost) AS total_value
                            FROM
                            	st_stock_ledger sl,
                                st_stock_batch sb,
                            	st_stock s 
                            WHERE
                                sb.id = sl.batch_id AND
                            	s.id = sb.stock_id  
                            	{$Where}
                            GROUP BY 
                            	s.name ASC
    	");
    }

    function getStockOverviewReport($ItemName='')
    {
        $Where = '';
        $Where .= UtilsQueryMaker::equal('s.name', $ItemName);

        $Query = "
                    SELECT
                        s.name,
                        s.category,
                        SUM(sl.qty) AS qty,
                        s.sp,
                        get_avg_cost(s.id) AS cost_price
                    FROM
                        st_stock_ledger sl,
                        st_stock_batch sb,
                        st_stock s
                    WHERE
                        sb.id = sl.batch_id AND
                        s.id = sb.stock_id
                        {$Where}
                    GROUP BY
                        s.id
                    ORDER BY
                        sb.batch_number DESC
        ";

        return DBQM::query($Query);
    }
}