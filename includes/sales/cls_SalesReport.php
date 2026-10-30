<?php


class SalesReport extends Sales
{
    function getReport($DateFrom, $DateTill, $VoucherNumber, $ItemName, $AccountName, $Description, $VoucherTypeId, $isDeleted='', $InvoiceType='', $isProjectSales=false)
    {
        $oBranch = new Branch();
        $Query = "
                    SELECT
                    	did.voucher_number,
                        did.voucher_date,
                        did.voucher_total,
                        did.is_draft,
                        shl.username as created_by,
                        sl.id,
                        sl.distinct_id,
                        sl.description,
                        a.name AS customer_name,
                        did.voucher_type_id,
                        did.is_deleted,
                        vt.name AS voucher_prefix
                    FROM
                        vt_voucher_type vt,
                        distinct_id did,
                        sh_login shl,
                        acc_accounts a,
                        sl_sales sl
                        left join sl_sales_data sd on sl.id = sd.sales_id
                        left join st_stock s on s.id = sd.stock_id
                    WHERE
                        did.id = sl.distinct_id AND
                        sl.customer_account_id = a.id AND
                        vt.id = did.voucher_type_id AND
                        shl.id = did.created_by AND
					    did.year_id = ".escape(Year::getYearId())." 
        ";

        $Where = '';

        if (empty($VoucherNumber))
        {
            $Where .= UtilsQueryMaker::date("did.voucher_date", $DateFrom, $DateTill);

            $Where .= UtilsQueryMaker::equal("s.id", $ItemName);
            $Where .= UtilsQueryMaker::like("sl.description", $Description);
            $Where .= UtilsQueryMaker::like("a.id", $AccountName);
            $Where .= UtilsQueryMaker::equal("did.is_deleted", $isDeleted);
        }
        else
        {
            $Where .= UtilsQueryMaker::like("did.voucher_number", $VoucherNumber);
        }

        $Where .= UtilsQueryMaker::equal("sl.invoice_type", $InvoiceType);
        $Where .= CostCenter::getFilterSql('did.cost_center_id', true);
        $Where .= Branch::isAccessYourOwnData('did.created_by');

        $Where .= UtilsQueryMaker::equal("did.voucher_type_id", $VoucherTypeId);
        $Where .= $isProjectSales ? " AND did.project_id IS NOT NULL" : "";
        $Query .= $Where;

        $Query .= " GROUP BY sl.id ORDER BY did.voucher_date, sl.id";

        return DBQM::query($Query);
    }

    function getDateWiseReport($DateFrom, $DateTill, $CustomerId, $CombineStockId, $BranchId, $CategoryId, $Brand, $VoucherTypeId='', $Description, $SalesmanId='', $InvoiceType='')
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombineStockId);

        $Where = '';
        $Where .= UtilsQueryMaker::date("did.voucher_date", $DateFrom, $DateTill);
        $Where .= UtilsQueryMaker::equal("sld.stock_id", $StockId);
        $Where .= UtilsQueryMaker::equal("s.attribute_id", $AttributeId);
        $Where .= UtilsQueryMaker::equal("sl.customer_account_id", $CustomerId);
        $Where .= UtilsQueryMaker::equal("sl.branch_id", $BranchId);
        $Where .= UtilsQueryMaker::equal("category_id", $CategoryId);
        $Where .= UtilsQueryMaker::equal("s.brand", $Brand);
        $Where .= UtilsQueryMaker::equal("did.voucher_type_id", $VoucherTypeId);
        $Where .= UtilsQueryMaker::equal("sl.sales_man_id", $SalesmanId);
        $Where .= CostCenter::getFilterSql('did.cost_center_id', true);
        $Where .= UtilsQueryMaker::like("sl.description", $Description);

        if ($InvoiceType == 'I')
            $Where .= " AND sld.stock_id IS NOT NULL";
        else if ($InvoiceType == 'N')
            $Where .= " AND sld.stock_id IS NULL AND sld.description <> '' ";

        $Query = "
                    SELECT
                        did.voucher_number,
                        did.voucher_date,
                        did.voucher_type_id, 
                        sl.description,
                        sl.invoice_discount_percentage,
                        sl.invoice_discount_amount,
                        sl.gst_percentage,
                        sl.transport_amount,
                        vt.name AS voucher_prefix,
                        sld.stock_id,
                        sld.id,
                        sld.sales_id AS sales_id,
                        sld.qty,
                        sld.item_disc,
                        sld.qty_return,
                        sld.description AS item_des,
                        sld.selling_price AS selling_price,
                        sld.discounted_rate,
                        sld.attribute_id,
                        b.name AS branch_name,
                        a.name AS customer_name,
                        sl.name AS cash_name,
                        sl.customer_account_id,
                        s.ac_stock AS item_name
                    FROM
                        acc_accounts a,
                        distinct_id did,
                        vt_voucher_type vt,
                        branch b,
                        sl_sales sl
                        left join sl_sales_data sld on sld.sales_id = sl.id
                        left join vw_ac_stock s on s.id = sld.stock_id AND s.attribute_id <=> sld.attribute_id
                    WHERE 
                        did.voucher_type_id = vt.id AND
                        did.id = sl.distinct_id AND 
                        sl.customer_account_id = a.id AND
                        sl.branch_id = b.id AND
                        did.is_deleted <> 'Y' AND
                        did.voucher_type_id IN (".VoucherType::getSalesVoucherTypes().", ".escape(VoucherType::SRV).")
                        {$Where}	
                    ORDER BY
                        sl.id
        ";

        return DBQM::query($Query);
    }

    function getAvgPriceNQty($DateFrom, $DateTill, $CombineStockId, $Brand, $CategoryId)
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombineStockId);

        $Where = '';
        $Where .= UtilsQueryMaker::date("did.voucher_date", $DateFrom, $DateTill);
        $Where .= UtilsQueryMaker::equal("sld.stock_id", $StockId);
        $Where .= UtilsQueryMaker::equal("sld.attribute_id", $AttributeId);
        $Where .= UtilsQueryMaker::like("s.brand", $Brand);
        $Where .= UtilsQueryMaker::like("s.category_id", $CategoryId);
        $Where .= CostCenter::getFilterSql('did.cost_center_id', true);

        $Query = "
                SELECT
                    s.id,
                    s.ac_stock,
                    SUM(sld.qty - sld.qty_return) AS total_quantity,
                    SUM(sld.discounted_rate * (sld.qty - sld.qty_return)) AS total_sale_price
                FROM
                    sl_sales sl,
                    sl_sales_data sld,
                    distinct_id did,
                    vw_ac_stock s
                WHERE 
                    s.id = sld.stock_id AND 
                    s.attribute_id <=> sld.attribute_id AND
                    sld.sales_id = sl.id AND
                    did.id = sl.distinct_id AND
                    did.voucher_type_id IN (".VoucherType::getSalesVoucherTypes().", ".escape(VoucherType::SRV).") AND
                    did.is_deleted <> 'Y' AND
                    (sld.qty > 0 OR sld.qty_return > 0)
                    {$Where}
                GROUP BY 
                    s.id, s.attribute_id   
        ";

        return DBQM::query($Query);
    }
}