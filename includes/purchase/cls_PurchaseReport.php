<?php


class PurchaseReport
{
    function getDateWiseReport($DateFrom, $DateTill, $SupplierId, $CombineStockId, $BranchId, $CategoryId, $Brand, $InvoiceType='')
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombineStockId);

        $Where = '';
        $Where .= UtilsQueryMaker::date("did.voucher_date", $DateFrom, $DateTill);
        $Where .= UtilsQueryMaker::equal("pd.stock_id", $StockId);
        $Where .= UtilsQueryMaker::equal("pd.attribute_id", $AttributeId);
        $Where .= UtilsQueryMaker::equal("p.supplier_account_id", $SupplierId);
        $Where .= UtilsQueryMaker::equal("p.branch_id", $BranchId);
        $Where .= UtilsQueryMaker::equal("category_id", $CategoryId);
        $Where .= UtilsQueryMaker::equal("s.brand", $Brand);
        $Where .= CostCenter::getFilterSql('did.cost_center_id', true);

        if ($InvoiceType == 'I')
            $Where .= " AND pd.stock_id IS NOT NULL";
        else if ($InvoiceType == 'N')
            $Where .= " AND pd.stock_id IS NULL AND item_description <> '' ";


        $Query = "
                    SELECT
                        did.voucher_number,
                        did.voucher_date,
                        did.voucher_type_id,
                        p.description,
                        pd.stock_id,
                        pd.attribute_id,   
                        pd.id,
                        pd.purchase_id,
                        pd.qty,
                        pd.qty_return,
                        pd.item_description,
                        pd.rate,
                        pd.discounted_rate,
                        p.invoice_gst_percentage,
                        b.name AS branch_name,
                        a.name AS supplier_name,
                        s.ac_stock AS item_name,
                        vt.name AS voucher_prefix
                    FROM
                        distinct_id did,
                        acc_accounts a,
                        vt_voucher_type vt,
                        pr_purchase p
                        LEFT JOIN 
                            branch b 
                            ON 
                                p.branch_id = b.id
                        LEFT JOIN 
                            pr_purchase_data pd
                            ON 
                                pd.purchase_id = p.id
                        LEFT JOIN 
                            vw_ac_stock s
                            ON 
                                s.id = pd.stock_id AND s.attribute_id <=> pd.attribute_id
                    WHERE 
                        p.supplier_account_id = a.id AND
                        p.distinct_id = did.id AND
                        did.voucher_type_id = vt.id AND
                        did.is_deleted <> 'Y' AND
                        did.voucher_type_id IN (".escape(VoucherType::PIV).", ".escape(VoucherType::PRV).")
                        {$Where}
                    ORDER BY
                        p.id
        ";

        return DBQM::query($Query);
    }

    function getAvgPriceNQty($DateFrom, $DateTill, $CombineStockId, $Brand, $CategoryId)
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombineStockId);

        $Where = '';
        $Where .= UtilsQueryMaker::date("did.voucher_date", $DateFrom, $DateTill);
        $Where .= UtilsQueryMaker::equal("pd.stock_id", $StockId);
        $Where .= UtilsQueryMaker::equal("pd.attribute_id", $AttributeId);
        $Where .= UtilsQueryMaker::like("s.brand", $Brand);
        $Where .= UtilsQueryMaker::like("ssc.id", $CategoryId);
        $Where .= CostCenter::getFilterSql('did.cost_center_id', true);

        $Query = "
                    SELECT
                        s.id,
                        s.ac_stock AS item_name,
                        SUM(pd.qty - pd.qty_return) AS total_quantity,
                        SUM(pd.discounted_rate * (pd.qty - pd.qty_return)) AS total_purchase_price
                    FROM
                        pr_purchase p,
                        distinct_id did,
                        pr_purchase_data pd,
                        vw_ac_stock s,
                        st_stock_category ssc 
                    WHERE 
                        s.id = pd.stock_id AND 
                        s.attribute_id <=> pd.attribute_id AND
                        pd.purchase_id = p.id AND
                        p.distinct_id = did.id AND
                        s.category_id = ssc.id AND  
                        did.voucher_type_id IN (".escape(VoucherType::PIV).", ".escape(VoucherType::PRV).") AND
                        did.is_deleted <> 'Y' AND
                        (pd.qty > 0 OR pd.qty_return > 0)
                        {$Where}
                    GROUP BY 
                        s.id, s.attribute_id
        ";

        return DBQM::query($Query);
    }
}