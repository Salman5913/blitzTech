<?php


class StockReportMIS extends Reporting
{
    public $DisplayType = [
        'ac_stock::ASC' => 'Default',
        'avg_profit::DESC' => 'Most Profitable (Value)',
        'profit_percentage::DESC' => 'Most Profitable (Percentage)',
        'avg_cost::DESC' => 'Most Expensive',
        'avg_sales_per_month::ASC' => 'Slowest Sales',
        'one_time_max_sales::DESC' => 'Largest Orders (By Qty per Sales Invoice)',
        'total_sales::DESC' => 'Fastest Selling',
        'last_sales_date::ASC' => 'Dead Stock'
    ];

    function getReport($CategoryId, $Brand, $SortBy, $CombinedStockId)
    {
        list($SortByColumn, $SortByStyle) = explode("::", $SortBy);
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombinedStockId);

        $Where = UtilsQueryMaker::date("stl.date_time", $this->DateFrom, $this->DateTill);
        $Where .= UtilsQueryMaker::equal("s.id", $StockId);
        $Where .= CostCenter::getFilterSql("stl.distinct_id");

        $WhereOuter = UtilsQueryMaker::equal("s.category_id", $CategoryId);
        $WhereOuter .= UtilsQueryMaker::equal("s.brand", $Brand);

        /**
         * Last Sales Count
         * Avg Profit
         * Avg Cost
         * Last Sales Date
         * Highest 1 time sales (by customer / sales)
         * Avg sales per month
         * Current Stock
         */

        $MaxQtyFromDate = empty($this->DateFrom) ? Settings::getOpeningDateTime() : DateEncode($this->DateFrom);
        $MaxQtyTillDate = empty($this->DateTill) ? DatabaseHelper::NOW() : DateEncode($this->DateTill);

        return DBQM::query("
            SELECT
                s.id, 
                s.attribute_id,
                s.ac_stock,
                total_sales,
                (avg_sp - avg_cost) AS avg_profit,
                avg_sp,
                avg_cost,
                (1 - (avg_cost / avg_sp)) AS profit_percentage,
                MAX(last_sales_date) AS last_sales_date,
                get_max_sales_qty(".escape($MaxQtyFromDate).", ".escape($MaxQtyTillDate." 23:59:59").", s.id, s.attribute_id, ".escape(implode(", ", VoucherType::getSalesVoucherTypes(false))).") AS one_time_max_sales,
                get_avg_sales_per_month(s.id, s.attribute_id, ".escape($MaxQtyFromDate).", ".escape($MaxQtyTillDate." 23:59:59").", ".escape(implode(", ", array_merge(VoucherType::getSalesVoucherTypes(false), [VoucherType::SRV]))).") AS avg_sales_per_month,
                s.available_qty
            FROM
                (
                    SELECT 
                        s.id, 
                        s.attribute_id,
                        SUM(stl.qty * -1) AS total_sales,
                        AVG(stl.sp) AS avg_sp,
                        get_avg_sales_cost(s.id, s.attribute_id, ".escape($MaxQtyFromDate).", ".escape($MaxQtyTillDate." 23:59:59").") AS avg_cost,
                        MAX(stl.date_time) AS last_sales_date
                    FROM  
                        vw_ac_stock s,
                        st_stock_ledger stl, 
                        st_stock_batch stb
                    WHERE
                        s.id = stb.stock_id AND
                        s.attribute_id <=> stb.attribute_id AND
                        stb.id = stl.batch_id AND
                        stl.voucher_type_id IN (".VoucherType::getSalesVoucherTypes().", ".escape(VoucherType::SRV).")
                        {$Where}
                    GROUP BY
                        s.id, s.attribute_id
                ) AS tmp,
                vw_ac_stock s
            WHERE
                tmp.id = s.id AND
                tmp.attribute_id <=> s.attribute_id
                {$WhereOuter}
            GROUP BY
                s.id, s.attribute_id
            ORDER BY
                {$SortByColumn} {$SortByStyle}
        ");
    }
}