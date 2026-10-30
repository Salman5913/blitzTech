<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 009, 1/9/2015
 * Time: 3:41 PM
 */

class StockReport extends Reporting
{
    function getReport($StockId, $AttributeId, $BranchId)
    {
        $Query = "


            SELECT
                stl.*,
                stb.cost,
                b.name AS branch_name,
                vt.abbr AS voucher_name,
                vt.name AS voucher_type,
                did.voucher_type_id,
                did.voucher_id,
                get_stock_used_in_name(did.voucher_type_id, distinct_id) AS customer_name,
                stb.cost
            FROM
                branch b,
                vt_voucher_type vt,
                distinct_id did, 
                st_stock_ledger stl
                left join st_stock_batch stb on stb.id = stl.batch_id
                left join st_stock s on s.id = stb.stock_id
            WHERE
                b.id = stl.branch_id AND
                stl.distinct_id = did.id AND
                vt.id = stl.voucher_type_id AND
                stl.year_id = ".escape(Year::getYearId())."
            ";

        $Where = UtilsQueryMaker::equal("s.id", $StockId);
        $Where .= UtilsQueryMaker::date("stl.date_time", $this->DateFrom, $this->DateTill);
        $Where .= UtilsQueryMaker::equal("stb.attribute_id", $AttributeId);
        $Where .= UtilsQueryMaker::equal("stl.branch_id", $BranchId);
        $Where .= CostCenter::getFilterSql("did.cost_center_id", true);

        $Query .= $Where;
        $Query .= " ORDER BY DATE(date_time), stl.voucher_type_id ASC";

        return DBQM::query($Query);
    }

    function getBatchTransitionStockInStockLedger($DateFrom, $DateTill, $CombinedStockId, $isDeleted)
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombinedStockId);

        $Query = "
            SELECT
                CONCAT(vt.name,'-',did.voucher_number) as voucher_name,
                did.voucher_date,
                shl.username as last_updated_by,
                did.updated_date_time,
                did.is_deleted,
                get_stock_used_in_name(did.voucher_type_id, sbt.distinct_id) AS account_name
            FROM
               st_stock_batch_transition sbt,
               distinct_id did,
               vt_voucher_type vt,
                sh_login shl
            WHERE
                sbt.batch_id IN (SELECT sl.batch_id FROM st_stock_ledger sl) AND
                did.id = sbt.distinct_id AND
                did.voucher_type_id = vt.id AND
                did.updated_by = shl.id AND
                sbt.year_id = ".escape(Year::getYearId())."
            ";

        $Where = UtilsQueryMaker::equal("sbt.stock_id", $StockId);
        $Where .= UtilsQueryMaker::equal("sbt.attribute_id", $AttributeId);
        $Where .= UtilsQueryMaker::date("did.voucher_date", $DateFrom, $DateTill);
        $Where .= UtilsQueryMaker::equal("did.is_deleted", $isDeleted);
        $Where .= CostCenter::getFilterSql("did.cost_center_id", true);

        $Query .= $Where;
        $Query .= " GROUP BY sbt.distinct_id ORDER BY DATE(voucher_date) ASC";


        return DBQM::query($Query);
    }

    function getZeroStockLedger()
    {
        $Query = "
                SELECT
                    CONCAT(vt.`name`, ' - ', d.voucher_number) as voucher_name,
                    d.voucher_date,
                    d.voucher_type_id,
                    d.voucher_id,
                    d.updated_date_time,
                    shl.username as created_by,
                    get_stock_used_in_name(d.voucher_type_id, s.distinct_id) as customer_name
                FROM
                    distinct_id d,
                    sh_login shl,
                    vt_voucher_type vt,
                    sl_sales s
                WHERE
                    s.distinct_id = d.id AND 
                    distinct_id NOT IN ( SELECT distinct_id FROM st_stock_ledger WHERE voucher_type_id IN ( 2, 15, 27, 28, 29 ) ) AND 
                    d.is_deleted <> 'Y' AND 
                    d.voucher_type_id = vt.id AND 
                    d.created_by = shl.id AND 
                    s.id IN (select sd.sales_id from sl_sales_data sd) AND
                    voucher_type_id IN ( 2, 15, 27, 28, 29 ) AND
                    d.year_id = ".escape(Year::getYearId())."
                GROUP BY 
                    voucher_type_id, voucher_number
                ORDER BY
					d.voucher_date LIMIT 7
            ";

        return DBQM::query($Query);
    }

    function getZeroAmountSaveVoucher()
    {
        $Query = "
                SELECT
                    CONCAT(vt.`name`, ' - ', d.voucher_number) as voucher_name,
                    d.voucher_date,
                    d.voucher_type_id,
                    d.voucher_id,
                    d.updated_date_time,
                    shl.username as created_by,
                    get_stock_used_in_name(d.voucher_type_id, d.id) as customer_name
                FROM
                    distinct_id d,
                    sh_login shl,
                    vt_voucher_type vt
                WHERE
                    d.is_deleted <> 'Y' AND 
                    d.voucher_type_id = vt.id AND 
                    d.created_by = shl.id AND 
                    d.voucher_total = 0 AND
                    d.year_id = ".escape(Year::getYearId())."
                GROUP BY 
                    voucher_type_id, voucher_number
                ORDER BY 
                    voucher_date LIMIT 7
        ";

        return DBQM::query($Query);
    }

    function getStockHolding($CombineStockId, $DateTill, $BranchId, $CategoryId, $Brand)
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombineStockId);

        $Where = '';
        $Where .= UtilsQueryMaker::equal('s.id', $StockId);
        $Where .= UtilsQueryMaker::equal('s.attribute_id', $AttributeId);
        $Where .= UtilsQueryMaker::date('sl.date_time', '', $DateTill);
        $Where .= UtilsQueryMaker::equal('sl.branch_id', $BranchId);
        $Where .= UtilsQueryMaker::equal('stc.id', $CategoryId);
        $Where .= UtilsQueryMaker::equal('s.brand', $Brand);
        $Where .= CostCenter::getFilterSql('sl.distinct_id');

        return DBQM::query("
    	                    SELECT
    	                        s.id,
    	                        sb.attribute_id,
    	                    	s.ac_stock AS name,
    	                    	stc.name AS category,
    	                    	s.brand,
    	                    	SUM(sl.qty) AS total_qty,
    	                        SUM((sl.qty * sb.cost)) AS total_value
                            FROM
                            	st_stock_ledger sl,
                                st_stock_batch sb,
                            	vw_ac_stock s,
                                st_stock_category stc
                            WHERE
                                sb.id = sl.batch_id AND
                                stc.id = s.category_id AND
                            	s.id = sb.stock_id AND
                                s.company_id = ".escape(Company::getLoggedInCompanyId())." AND    
                                s.attribute_id <=> sb.attribute_id
    	                        {$Where}
                            GROUP BY 
                            	sb.stock_id, sb.attribute_id
    	                    HAVING
    	                        total_qty > 0
                            ORDER BY
                                name ASC
    	");
    }

    function getStockOverviewReport($CombineStockId, $CategoryId)
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombineStockId);

        $Where = '';
        $Where .= UtilsQueryMaker::equal('s.id', $StockId);
        $Where .= UtilsQueryMaker::equal('s.attribute_id', $AttributeId);
        $Where .= UtilsQueryMaker::equal('stc.id', $CategoryId);

        $Query = "
                    SELECT
                        s.ac_stock AS name,
                        stc.name AS category,
                        s.available_qty,
                        get_avg_cost(s.id, s.attribute_id) AS cost_price,
                        s.selling_price
                    FROM
                        vw_ac_stock s,
                        st_stock_category stc
                    WHERE
                        stc.id = s.category_id AND
                        s.company_id = ".escape(Company::getLoggedInCompanyId())." AND    
                        available_qty > 0
                        {$Where}
        ";

        return DBQM::query($Query);
    }

    static function getStockBranches($StockId, $AttributeId, $SearchTerm, $DistinctId)
    {
        $Where = UtilsQueryMaker::equal("attribute_id", $AttributeId);
        $Where .= UtilsQueryMaker::like("b.name", $SearchTerm);

        return DBQM::query("
                                    SELECT 
                                        branch_id, 
                                        name AS branch_name, 
                                        SUM(stl.qty) AS available_qty
                                    FROM 
                                         st_stock_ledger stl, 
                                         st_stock_batch sb, 
                                         branch b 
                                    WHERE 
                                        b.id = stl.branch_id AND 
                                        stl.batch_id = sb.id AND 
                                        stock_id = ".escape($StockId)." AND 
                                        stl.distinct_id <> ".escape($DistinctId)." AND
                                        stl.year_id = ".escape(Year::getYearId())." 
                                        {$Where}
                                    GROUP BY
                                        branch_id
                                    HAVING
                                        available_qty > 0
        ");
    }

    /**
     * @param $StockId
     * @param $AttributeId
     * @param string $Date format Y-m-d H:i:s
     * @param string $DateType full => date is passed with time, no need to add time <br> to => add 23:59:59 to date <br> from => add 00:00:00 to date
     * @param string $BranchId
     * @return bool|mixed returns stock balance
     */
    public static function getStockBalance($StockId, $AttributeId, $Date, $DateType = 'to', $BranchId='', $CostCenterId = '')
    {
        $Where = UtilsQueryMaker::equal("sb.attribute_id", $AttributeId);
        $Where .= UtilsQueryMaker::equal("sl.branch_id", $BranchId);
        $Where .= CostCenter::getFilterSql("sl.distinct_id", false, $CostCenterId);

        if (!empty($Date))
        {
            if ($DateType == 'to')
                $Date .= " 23:59:59";
            elseif ($DateType == 'from')
                $Date .= " 00:00:00";

            $Where .= " AND sl.date_time < " . escape(DateEncode($Date));
        }

        $Select = DBQM::query("
                              SELECT
                                  SUM(sl.qty)
                              FROM
                                  st_stock_ledger sl,
                                  st_stock_batch sb
                              WHERE
                                  sl.batch_id = sb.id AND
                                  sl.year_id = " . escape(Year::getYearId()) . " AND
                                  sb.stock_id = " . escape($StockId) . "
                                  {$Where}
        ");

        return DBQM::result($Select, 0);
    }

    function getAllStockByCategory($CategoryId)
    {
        $Where = '';
        $Where .= UtilsQueryMaker::equal('stc.id', $CategoryId);

        return DBQM::query("
            SELECT 
                s.*, 
                stc.name AS category,
                s.selling_price
            FROM 
                vw_ac_stock s, 
                st_stock_category stc 
            WHERE 
                stc.id = s.category_id AND
                s.company_id = ".escape(Company::getLoggedInCompanyId())." AND    
                available_qty > 0
                {$Where} 
        ");
    }

    function getReorderReport($CategoryId, $CombineStockId)
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombineStockId);

        $Where = '';
        $Where .= UtilsQueryMaker::like("s.id", $StockId);
        $Where .= UtilsQueryMaker::equal('s.attribute_id', $AttributeId);
        $Where .= UtilsQueryMaker::like("s.category_id", $CategoryId);

        return DBQM::query("
                    SELECT 
                        s.*,
                        stc.name AS category,
                        get_avg_cost(s.id, s.attribute_id) AS avg_cost
                    FROM
                        vw_ac_stock s,
                        st_stock_category stc
                    WHERE
                        stc.id = s.category_id AND
                        s.company_id = ".escape(Company::getLoggedInCompanyId())." AND
                        available_qty <= reorder_level AND
                        reorder_level <> 0
                        {$Where}
                    ORDER BY 
                        s.ac_stock
        ");
    }

    function getProfitReport($CategoryId, $CombineStockId, $DateFrom, $DateTill)
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombineStockId);

        $Where = '';
        $Where .= UtilsQueryMaker::like("s.id", $StockId);
        $Where .= UtilsQueryMaker::equal('s.attribute_id', $AttributeId);
        $Where .= UtilsQueryMaker::like("s.category_id", $CategoryId);
        $Where .= UtilsQueryMaker::date("stl.date_time", $DateFrom, $DateTill);
        $Where .= CostCenter::getFilterSql("stl.distinct_id");

        return DBQM::query("
            SELECT 
                s.*,
                stc.name AS category,
                SUM(stl.qty * -1) AS sold_qty,
                AVG(sb.cost) AS avg_cost,
                AVG(stl.sp) AS avg_sp
            FROM
                vw_ac_stock s,
                st_stock_category stc,
                st_stock_ledger stl,
                st_stock_batch sb
            WHERE
                stc.id = s.category_id AND
                s.id = sb.stock_id AND
                s.attribute_id <=> sb.attribute_id AND
                stl.batch_id = sb.id AND
                stl.voucher_type_id IN (".VoucherType::getSalesVoucherTypes().", ".escape(VoucherType::SRV).") AND
                s.company_id = ".escape(Company::getLoggedInCompanyId())."
                {$Where}
            GROUP BY
                s.id, s.attribute_id
            ORDER BY 
                s.ac_stock
        ");
    }

    function getCompleteStockQtyByBranch($DateTill)
    {
        $Where = '';
        if (!empty($DateTill))
            $Where .= UtilsQueryMaker::date('stl.date_time', '', DateEncode($DateTill));
        $Where .= CostCenter::getFilterSql('stl.distinct_id');

        $Select = DBQM::query("
									SELECT 
										stock_id,
										branch_id,
										attribute_id,
										SUM(stl.qty) AS branch_qty
									FROM 
										st_stock_ledger stl,
									    st_stock_batch stb
									WHERE
										stl.batch_id = stb.id
										{$Where}
									GROUP BY 
										stb.stock_id, stb.attribute_id, stl.branch_id
		");

        $StockId = [];
        while ($Row = DBQM::fetch_assoc($Select))
        {
            if (empty($Row['attribute_id'])) $Row['attribute_id'] = 0;

            $StockId[$Row['stock_id']][$Row['branch_id']][$Row['attribute_id']] = $Row['branch_qty'];
        }

        return $StockId;
    }

    function getBatchWiseStock($CombinedStockId='')
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombinedStockId);

        $Where = '';
        $Where .= UtilsQueryMaker::equal("st.id", $StockId);
        $Where .= UtilsQueryMaker::equal("st.attribute_id", $AttributeId);
        $Where .= CostCenter::getFilterSql("sl.distinct_id");

        return DBQM::query("
                            SELECT
                                st.ac_stock,
                                sb.batch_number,
                                (SELECT stl.voucher_number FROM st_stock_ledger stl WHERE stl.batch_id = sb.id AND stl.voucher_type_id = ".escape(VoucherType::PIV).") as invoice_number,
                                (SELECT stl.date_time FROM st_stock_ledger stl WHERE stl.batch_id = sb.id AND stl.voucher_type_id = ".escape(VoucherType::PIV).") as voucher_date,
                                SUM( sl.qty ) AS sum_qty,
                                sb.cost 
                            FROM
                                st_stock_batch sb,
                                st_stock_ledger sl,
                                vw_ac_stock st 
                            WHERE
                                sb.id = sl.batch_id AND 
                                st.id = sb.stock_id AND 
                                sb.attribute_id <=> st.attribute_id
                                {$Where}
                            GROUP BY
                                sb.stock_id,
                                sb.id 
                            HAVING
                                sum_qty > 0
                            ORDER BY 
                                sb.batch_number ASC 
        ");
    }

    function getDeletedBatchesSales()
    {
        $Query = "
            SELECT
                CONCAT(vt.name,'-',did.voucher_number) as voucher_name,
                did.voucher_date,
                did.voucher_type_id,
                did.voucher_id,
                shl.username as last_updated_by,
                did.updated_date_time,
                did.is_deleted,
                get_stock_used_in_name(did.voucher_type_id, sl.distinct_id) AS account_name
            FROM
               st_stock_ledger sl,
               distinct_id did,
               vt_voucher_type vt,
               sh_login shl
            WHERE
                sl.batch_id NOT IN (SELECT sb.id FROM st_stock_batch sb) AND
                did.id = sl.distinct_id AND
                did.voucher_type_id = vt.id AND
                did.updated_by = shl.id AND
                did.year_id = ".escape(Year::getYearId())."
                ".CostCenter::getFilterSql("did.cost_center_id", true)."
            GROUP BY
                sl.distinct_id 
            ORDER BY 
                DATE(voucher_date) ASC
            ";

        return DBQM::query($Query);
    }

    function getQtyNotAdjustedStock()
    {
        $Query = "
                SELECT 
                    stl.date_time,
                    stl.voucher_type_id,
                    did.voucher_id,
                    did.updated_date_time,
                    get_user_name(did.created_by) as created_by,
                    CONCAT(vt.name, '-', stl.voucher_number) as voucher_name,
                    REPLACE(tmp.stock_name, SUBSTRING(tmp.stock_name, -4, 4), '') as stock
                FROM
                    distinct_id did,
                    vt_voucher_type vt,
                    st_stock_ledger stl,
                    st_stock_batch stb,
                    vw_ac_stock st,
                    (
                        SELECT 
                            REPLACE( REPLACE ( error, 'Qty not adjusted properly, either stock ran out or some other problem occurred. Stock Name: ', '' ), ' Qty unadjusted!', '' ) as stock_name,
                            date_time 
                        FROM
                            error_log 
                        WHERE
                            error LIKE 'Qty not adjusted properly, either stock ran out or some other problem occurred. Stock Name: %'
                        GROUP BY 
                            id
                    ) AS tmp
                WHERE
                    stl.batch_id = stb.id AND
                    stb.stock_id = st.id AND
                    stl.distinct_id = did.id AND
                    stl.voucher_type_id = vt.id AND
                    stb.attribute_id <=> st.attribute_id AND
                    stl.voucher_type_id IN (".VoucherType::getSalesVoucherTypes(true).", ".escape(VoucherType::SRV).", ".escape(VoucherType::PRV).", ".escape(VoucherType::STDV).") AND
                    st.ac_stock LIKE CONCAT(REPLACE(tmp.stock_name, SUBSTRING(tmp.stock_name, -4, 4), ''), '%') AND
                    date(stl.date_time) LIKE date(tmp.date_time)
                ORDER BY 
                    tmp.date_time
        ";

        return DBQM::query($Query);
    }

    function getMonthlyProfit($DateFrom, $DateTill, $CustomerAccountId, $CombineStockId, $CategoryId, $Brand)
    {
        $WhereCustomer = UtilsQueryMaker::equal("customer_account_id", $CustomerAccountId);

        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombineStockId);

        $Where = '';
        $Where .= UtilsQueryMaker::like("s.id", $StockId);
        $Where .= UtilsQueryMaker::equal('s.attribute_id', $AttributeId);
        $Where .= UtilsQueryMaker::date("stl.date_time", $DateFrom, $DateTill);
        $Where .= UtilsQueryMaker::equal("st.category_id", $CategoryId);
        $Where .= UtilsQueryMaker::equal("st.brand", $Brand);
        $Where .= CostCenter::getFilterSql("d.cost_center_id", true);

        return DBQM::query("
                            SELECT
                                a.ac_name as customer_name,
                                s.customer_account_id,
                                sum(stl.qty * stl.sp * -1) as sales,
                                sum(stl.qty * stb.cost * -1) as cost
                            FROM
                                st_stock_batch stb,
                                vw_ac_stock st,
                                st_stock_ledger stl,
                                distinct_id d,
                                vw_ac_accounts a,
                                sl_sales s
                            where
                                stl.batch_id = stb.id AND 
                                stl.distinct_id = d.id AND 
                                d.voucher_id = s.id AND 
                                s.customer_account_id = a.id AND 
                                stb.stock_id = st.id AND 
                                stb.attribute_id <=> st.attribute_id AND
                                d.is_deleted <> 'Y' AND
                                stl.voucher_type_id in (".VoucherType::getSalesVoucherTypes().", ".escape(VoucherType::SRV)." )
                                {$Where}
                                {$WhereCustomer}
                            GROUP BY
                                s.customer_account_id
                            ORDER BY
                                a.name
        ");
    }

    function getCustomerWiseProfitBreakup($DateFrom, $DateTill, $CustomerAccountId, $StockId, $AttributeId, $CategoryId, $Brand)
    {
        $Where = UtilsQueryMaker::equal("st.stock_id", $StockId);
        $Where .= UtilsQueryMaker::equal("st.attribute_id", $AttributeId);
        $Where .= UtilsQueryMaker::date("stl.date_time", $DateFrom, $DateTill);
        $Where .= UtilsQueryMaker::equal("st.category_id", $CategoryId);
        $Where .= UtilsQueryMaker::equal("st.brand", $Brand);
        $Where .= CostCenter::getFilterSql("stl.distinct_id");

        return DBQM::query("
                            SELECT
                                vt.name as voucher_name,
                                stl.date_time as voucher_date,
                                stl.voucher_number,
                                st.ac_stock,
                                ABS(stl.qty) AS qty,
                                stl.sp,
                                stb.cost
                            FROM
                                st_stock_batch stb,
                                vw_ac_stock st,
                                vt_voucher_type vt,
                                st_stock_ledger stl
                            where
                                stl.batch_id = stb.id AND 
                                stb.stock_id = st.id AND 
                                stl.voucher_type_id = vt.id AND 
                                stb.attribute_id <=> st.attribute_id AND
                                stl.distinct_id IN 
                                (
                                    SELECT 
                                        s.distinct_id 
                                    FROM 
                                        sl_sales s, 
                                        distinct_id d 
                                    WHERE 
                                        s.distinct_id = d.id AND 
                                        d.is_deleted <> 'Y' AND
                                        s.customer_account_id = ".escape($CustomerAccountId)."
                                ) AND
                                stl.voucher_type_id in (".VoucherType::getSalesVoucherTypes().", ".escape(VoucherType::SRV)." )
                                {$Where}
                            GROUP BY
                                stl.id
                            ORDER BY
                                stl.date_time ASC
        ");
    }

    function getInventoryValuationSummary($DateTill, $BranchId)
    {
        $Where = '';
        if (!empty($DateTill))
            $Where .= UtilsQueryMaker::date('sl.date_time', '', $DateTill);
        
        $Where .= UtilsQueryMaker::equal('sl.branch_id', $BranchId);
        $Where .= CostCenter::getFilterSql('sl.distinct_id');

        return DBQM::query("
            SELECT
                stc.name AS category,
                SUM(sl.qty) AS total_qty,
                SUM((sl.qty * sb.cost)) AS total_value
            FROM
                st_stock_ledger sl,
                st_stock_batch sb,
                vw_ac_stock s,
                st_stock_category stc
            WHERE
                sb.id = sl.batch_id AND
                stc.id = s.category_id AND
                s.id = sb.stock_id AND
                s.company_id = " . escape(Company::getLoggedInCompanyId()) . " AND
                s.attribute_id <=> sb.attribute_id
                {$Where}
            GROUP BY 
                stc.id
            HAVING
                total_qty > 0
            ORDER BY
                category ASC
        ");
    }
}