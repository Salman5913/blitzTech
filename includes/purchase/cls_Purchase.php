<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 024, 12/24/2014
 * Time: 3:44 PM
 */

class Purchase extends BasicVoucher
{
    const TYPE_DELIVERY = 'D';
    const TYPE_INVOICE = 'I';
    const IND_DATA = 'DATA';
    const IND_PAYMENT = 'PAYMENT';

    public function __construct()
    {
        parent::__construct("pr_purchase", [
            self::IND_DATA => "pr_purchase_data",
            self::IND_PAYMENT => "pr_purchase_payment"
        ], "purchase_id", [
            VoucherType::PIV,
            VoucherType::PRV,
            VoucherType::POV,
            VoucherType::OPS
        ]);
    }

    function getReport($DateFrom, $DateTill, $VoucherNumber, $StockId, $AccountId, $Description, $VoucherTypeId)
    {
        $Query = "
                    SELECT
                        di.voucher_date,
                        p.id,
                        di.is_draft,
                        di.voucher_number,
                        di.is_deleted,
                        p.description,
                        voucher_type_id,
                        shl.username as created_by,
                        p.branch_id,
                        p.distinct_id,
                        vt.name AS voucher_prefix,
                        a.name AS supplier_name,
                        di.voucher_total
                    FROM 
                        distinct_id di,
                        sh_login shl,
                        vt_voucher_type vt,
                        pr_purchase p
                        LEFT JOIN 
                            pr_purchase_data pd 
                            ON 
                                pd.purchase_id = p.id
                        LEFT JOIN 
                            st_stock s 
                            ON 
                                pd.stock_id = s.id
                        LEFT JOIN
                            acc_accounts a
                            ON 
                                p.supplier_account_id = a.id      
                    WHERE
                        p.distinct_id = di.id AND
                        vt.id = di.voucher_type_id AND
                        shl.id = di.created_by
        ";

        $Where = '';

        if (empty($VoucherNumber))
        {
            if ($VoucherTypeId != VoucherType::OPS)
                $Where .= UtilsQueryMaker::date("di.voucher_date", $DateFrom, $DateTill);

            $Where .= UtilsQueryMaker::equal("s.id", $StockId);
            $Where .= UtilsQueryMaker::like("p.description", $Description);
            $Where .= UtilsQueryMaker::equal("a.id", $AccountId);
            $Where .= UtilsQueryMaker::equal("di.voucher_type_id", $VoucherTypeId);
        }
        else
        {
            $Where .= UtilsQueryMaker::like("di.voucher_number", $VoucherNumber);
            $Where .= UtilsQueryMaker::equal("di.voucher_type_id", $VoucherTypeId);
        }

        $Where .= Branch::isAccessYourOwnData('di.created_by');
        $Where .= CostCenter::getFilterSql('di.cost_center_id', true);

        $Query .= $Where;

        $Query .= " GROUP BY p.id ORDER BY di.voucher_date, p.id";
        return DBQM::query($Query);
    }

    static function updateBatchNumber($VoucherTypeId, $VoucherNumber, $BatchId, $StockId, $AttributeId, $PurchaseDataId)
    {
        $oPurchase = new Purchase();
        $PurchaseId = $oPurchase->getVoucherIdByVoucherNumber($VoucherTypeId, $VoucherNumber);

        DBQM::query("UPDATE pr_purchase_data SET batch_id = ".escape($BatchId, 'int')." WHERE purchase_id = ".escape($PurchaseId, 'int')." AND id = ".escape($PurchaseDataId, 'int'));
    }

    protected function saveTransactionEntries()
    {
        $VoucherTypeId = $this->getVoucherType();

        /* ************* Do not perform any transactional entries if Order voucher or Requisition voucher ************* */
        if ($VoucherTypeId == VoucherType::POV or $VoucherTypeId == VoucherType::PR)
            return;
        /* ************* Do not perform any transactional entries if Order voucher or Requisition voucher ************* */


        $RowDetails = $this->getDetails();
        $SelectInvoiceData = $this->getData(self::IND_DATA);

        $oStockLedger = $this->getStockLedgerObject();
        $oLedger = $this->getLedgerObject();

        $oSMSTemplates = new SMSTemplates();

        $isOpeningVoucher = $VoucherTypeId == VoucherType::OPS;
        $isInvoice = $VoucherTypeId == VoucherType::PIV;
        $isReturn = $VoucherTypeId == VoucherType::PRV;

        $TotalPaymentToSupplier = $InvoiceDiscountPercentage = 0;

        $isDeliveredNInvoiced = $RowDetails['invoice_type'];

        $Multiplier =  $this->getVoucherType() == VoucherType::PRV ? -1 : 1;

        if ($isOpeningVoucher)
            $RowDetails['supplier_account_id'] = getConstantAccount()->getEquityInvestmentAccount();

        $ItemCount = 0;


        /* ************ Calc Invoice Discount Percentage *************** */
        if (!empty($RowDetails['invoice_discount_calc_value']))
        {
            $TotalAmount = 0;
            while($RowData = DBQM::fetch_assoc($SelectInvoiceData))
            {
                $Rate = $RowData['rate'];
                $Qty = $RowData['qty'] - $RowData['qty_return'];

                $Rate -= Utils::calcPercentValue($Rate, $RowData['item_dis'], 0);

                $TotalAmount += $Qty * $Rate;
            }

            if ($TotalAmount > 0)
                $InvoiceDiscountPercentage = ($RowDetails['invoice_discount_calc_value'] / $TotalAmount) * 100;
        }
        /* ************ Calc Invoice Discount Percentage *************** */

        DBQM::reset($SelectInvoiceData);
        while($RowData = DBQM::fetch_assoc($SelectInvoiceData))
        {
            $Rate = $RowData['rate'];
            $Qty = $RowData['qty'] - $RowData['qty_return'];

            $Rate -= Utils::calcPercentValue($Rate, $RowData['item_dis'], 0);
            $Rate -= Utils::calcPercentValue($Rate, $InvoiceDiscountPercentage, 0);

            /* ***** Prevent Ledger Entries until its invoiced, else update ledger date to invoice date ******* */
//            if ($isDeliveredNInvoiced == self::TYPE_DELIVERY)
//                $Rate = 0;
            /*else
                $oLedger->updateDate($RowDetails['voucher_date']);*/
            /* ***** Prevent Ledger Entries until its invoiced, else update ledger date to invoice date ******* */

            $ItemCount += abs($Qty);

            $RowTotal = $Qty * $Rate;
            $TotalPaymentToSupplier += $RowTotal;

            if (!empty($RowData['stock_id']))
            {
                if ($isInvoice || $isOpeningVoucher)
                {
                    $oStockLedger->recordPurchase($RowData['stock_id'], $RowData['attribute_id'], $RowDetails['branch_id'], $RowData['qty'], $Rate, $RowDetails['supplier_account_id'], $RowData['id'], '',$RowDetails['invoice_type']);
                }
                else
                {
                    $oStockLedger->recordSales($RowData['stock_id'], $RowData['attribute_id'], $RowData['qty_return'], 'COST', $RowDetails['branch_id']);

                    $oLedger->saveData($RowDetails['supplier_account_id'], "-".$RowTotal,1,'Stock', getConstantAccount()->getPurchaseAccount(), '', 'N', $RowData['stock_id'], $RowData['attribute_id'], $RowData['qty_return'], $Rate,$RowDetails['invoice_type']);
                    $oLedger->saveData(getConstantAccount()->getPurchaseAccount(), $RowTotal,1,'Stock', $RowDetails['supplier_account_id'], '', 'N', $RowData['stock_id'], $RowData['attribute_id'], $RowData['qty_return'], $Rate,$RowDetails['invoice_type']);
                }
            }
            elseif (!empty($RowData['item_description']))
            {
                // Non inventory
                $Description = 'Purchase - ' . $RowData['item_description'] . ' x ' . ($RowData['qty_return'] > 0 ? $RowData['qty_return'] : $RowData['qty']) . ' @ ' . $Rate;

                $oLedger->saveData($RowDetails['supplier_account_id'], "-" . $RowTotal,1, $Description,'','','','','','','',$RowDetails['invoice_type']);
                $oLedger->saveData(getConstantAccount()->getNonInventoryPurchasesAccount(), $RowTotal,1, $Description,'','','','','','','',$RowDetails['invoice_type']);
            }

            $this->updateDiscountedRate($RowData['id'], $Rate);
        }


        // GST
        $oLedger->saveData($RowDetails['supplier_account_id'], '-'.$RowDetails['gst_calc_value'] * $Multiplier,1,ProjectHelper::TAX_LABEL, getConstantAccount()->getGSTAccount(),'','','','','','',$RowDetails['invoice_type']);
        $oLedger->saveData(getConstantAccount()->getGSTAccount(), ($RowDetails['gst_calc_value'] * $Multiplier),1,ProjectHelper::TAX_LABEL, $RowDetails['supplier_account_id'],'','','','','','',$RowDetails['invoice_type']);


        $PaymentAmount = 0;

        /* **************************** Payments ****************************** */
        $SelectPayments = $this->getData(self::IND_PAYMENT);
        while($RowPayment = DBQM::fetch_assoc($SelectPayments))
        {
            $oLedger->saveData($RowPayment['cash_bank_id'], "-".($RowPayment['amount'] * $Multiplier),1, $RowPayment['description'], $RowDetails['supplier_account_id'],'','','','','','',$RowDetails['invoice_type']);
            $oLedger->saveData($RowDetails['supplier_account_id'], $RowPayment['amount'] * $Multiplier,1, $RowPayment['description'], $RowPayment['cash_bank_id'],'','','','','','',$RowDetails['invoice_type']);

            $PaymentAmount += $RowPayment['amount'];
        }
        /* **************************** Payments ****************************** */
        if($isReturn)
        {
//            var_dump($TotalPaymentToSupplier,$RowDetails['gst_calc_value'],$RowDetails['invoice_discount_calc_value']);
            $this->updateVoucherTotal($TotalPaymentToSupplier-$RowDetails['invoice_discount_calc_value'] + $RowDetails['gst_calc_value'] + $RowDetails['advance_tax_percentage']);
        }

        else{
            $this->updateVoucherTotal($TotalPaymentToSupplier + $RowDetails['gst_calc_value'] + $RowDetails['advance_tax_percentage']);
        }

        if (!$this->isDelete())
            $oSMSTemplates->UserPurchaseRecorded($RowDetails['supplier_account_id'], $RowDetails['voucher_number'], $ItemCount, ($TotalPaymentToSupplier + $RowDetails['gst_calc_value']), $this->isEdit());
    }

    /**
     * Purchase rows whose stock batch has already been consumed by other vouchers (sales, returns, transfers etc.).
     * For such rows only rate / discount can be changed, qty / item / warehouse are locked.
     *
     * @param $PurchaseId
     * @return array pr_purchase_data.id => row (stock_id, attribute_id, qty, used_qty)
     */
    function getUsedStockRows($PurchaseId)
    {
        if (empty($PurchaseId))
            return [];

        $Select = DBQM::query("
            SELECT
                pd.id,
                pd.stock_id,
                pd.attribute_id,
                pd.qty,
                SUM(IF(l.qty < 0, -l.qty, 0)) AS used_qty
            FROM
                pr_purchase p,
                pr_purchase_data pd,
                st_stock_ledger l
            WHERE
                pd.purchase_id = p.id AND
                l.batch_id = pd.batch_id AND
                l.distinct_id <> p.distinct_id AND
                pd.batch_id > 0 AND
                p.id = ".escape($PurchaseId, 'int')."
            GROUP BY
                pd.id
        ");

        $Rows = [];
        while ($Row = DBQM::fetch_assoc($Select))
            $Rows[$Row['id']] = $Row;

        return $Rows;
    }

    private static function getStockKey($StockId, $AttributeId)
    {
        return (int) $StockId."::".(int) $AttributeId;
    }

    /**
     * Must be called before initForSave(), as initForSave() removes stock ledger entries of the voucher.
     * Returns list of errors, empty if voucher can be saved.
     *
     * @return array
     */
    function validateUsedStockBeforeSave()
    {
        $PurchaseId = $_POST['voucher_id'];
        $UsedRows = $this->getUsedStockRows($PurchaseId);

        if (empty($UsedRows))
            return [];

        if (isset($_POST['btn_delete']))
            return ["This voucher can't be deleted, its stock is already used in other vouchers."];

        if (isset($_POST['btn_draft']))
            return ["This voucher can't be saved as draft, its stock is already used in other vouchers."];

        $Errors = [];

        $RowDetails = DBQM::fetch_assoc(DBQM::query("SELECT branch_id FROM pr_purchase WHERE id = ".escape($PurchaseId, 'int')));
        if ((int) $_POST['branch_id'] != (int) $RowDetails['branch_id'])
            $Errors[] = "Warehouse can't be changed, stock of this voucher is already used in other vouchers.";

        /* ************ Qty must remain same for used items ************ */
        $OldQty = $NewQty = [];

        $Select = DBQM::query("SELECT stock_id, attribute_id, SUM(qty) AS qty FROM pr_purchase_data WHERE purchase_id = ".escape($PurchaseId, 'int')." GROUP BY stock_id, attribute_id");
        while ($Row = DBQM::fetch_assoc($Select))
            $OldQty[self::getStockKey($Row['stock_id'], $Row['attribute_id'])] = (float) $Row['qty'];

        $CountDataRows = count((array) $_POST['stock_id']);
        for ($i=0; $i<$CountDataRows; $i++)
        {
            if (empty($_POST['stock_id'][$i]))
                continue;

            list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_POST['stock_id'][$i]);
            $Key = self::getStockKey($StockId, $AttributeId);
            $NewQty[$Key] = ($NewQty[$Key] ?? 0) + (float) $_POST['qty'][$i];
        }

        $Checked = [];
        foreach ($UsedRows as $Row)
        {
            $Key = self::getStockKey($Row['stock_id'], $Row['attribute_id']);

            if (isset($Checked[$Key]))
                continue;
            $Checked[$Key] = true;

            if (round($OldQty[$Key] ?? 0, 4) != round($NewQty[$Key] ?? 0, 4))
                $Errors[] = "Qty of ".Stock::getName($Row['stock_id'], $Row['attribute_id'])." can't be changed or removed (".round($Row['used_qty'], 4)." already used in other vouchers). Only rate can be changed.";
        }
        /* ************ Qty must remain same for used items ************ */

        return $Errors;
    }

    function updateDiscountedRate($PurchaseDataId, $DiscountedRate)
    {
        DBQM::query("UPDATE pr_purchase_data SET discounted_rate = ".escape($DiscountedRate, 'double')." WHERE id = ".escape($PurchaseDataId));
    }

    function getSelectRequisitionsDropdownOptions($SelectedValue='')
    {
        $oPurchaseRequisition = new PurchaseRequisition();

        $Select = $this->getAllPendingRequisitions();
        echo "<option>Select...</option>";
        while ($RowRequisitions = DBQM::fetch_assoc($Select))
        {
            $RequisitionNumber = $oPurchaseRequisition->getVoucherNumberByRequisitionId($RowRequisitions['purchase_requisition_id']);
            Utils::getDropDownOptionsFromArray([$RowRequisitions['purchase_requisition_id'] => "PRV-".$RequisitionNumber], $SelectedValue);
        }
    }

    static function isAvailablePurchaseOrders()
    {
        $Select = DBQM::query("SELECT COUNT(p.id) FROM pr_purchase p, distinct_id d WHERE d.id = p.distinct_id AND d.is_deleted <> 'Y' AND d.voucher_type_id = ".escape(VoucherType::POV));

        return (int) DBQM::result($Select, 0);
    }

    static function isAvailablePurchaseRequisitions()
    {
        $Select = DBQM::query("SELECT COUNT(p.id) FROM pr_purchase_requisition p, distinct_id d WHERE d.id = p.distinct_id AND d.is_deleted <> 'Y' AND d.voucher_type_id = ".escape(VoucherType::PR));

        return (int) DBQM::result($Select, 0);
    }

    function getAllPendingOrders($SupplierAccountId)
    {
        return DBQM::query("
            SELECT
                DISTINCT purchase_order_id
            FROM
             (
                 SELECT 
                    voucher_number,
                    tmp.purchase_order_id,
                    SUM(qty_required - qty_used) AS qty
                FROM 
                    (
                        SELECT 
                            pr.id,
                            pr.id AS purchase_order_id,
                            stock_id, 
                            SUM(qty-qty_return) AS qty_required, 
                            0 AS qty_used 
                        FROM
                            pr_purchase pr,
                            `pr_purchase_data` prd,
                            distinct_id did
                        WHERE 
                            pr.id = prd.purchase_id AND
                            pr.distinct_id = did.id AND
                            did.is_deleted <> 'Y' AND
                            voucher_type_id = ".escape(VoucherType::POV, 'int')." AND
                            supplier_account_id = ".escape($SupplierAccountId, 'int')."
                        GROUP BY 
                            pr.id, stock_id
                        
                        UNION
                        
                        SELECT 
                            pr.id,
                            purchase_order_id,
                            stock_id, 
                            0 AS qty_required, 
                            SUM(qty-qty_return) AS qty_used 
                        FROM
                            pr_purchase pr,
                            `pr_purchase_data` prd,
                            distinct_id did
                        WHERE 
                            pr.id = prd.purchase_id AND
                            pr.distinct_id = did.id AND
                            purchase_order_id IS NOT NULL AND
                            did.is_deleted <> 'Y' AND
                            voucher_type_id IN (".escape(VoucherType::PIV).", ".escape(VoucherType::PRV).") AND
                            supplier_account_id = ".escape($SupplierAccountId, 'int')."
                        GROUP BY 
                            purchase_order_id, stock_id
                    ) AS tmp,
                    pr_purchase prl,
                    distinct_id did1
                WHERE
                    prl.id = tmp.id AND
                    prl.distinct_id = did1.id
                GROUP BY 
                    tmp.purchase_order_id, tmp.stock_id
                HAVING 
                    qty > 0
             ) AS tmp1
        ");
    }

    function getAllPendingRequisitions()
    {
        return DBQM::query("
            SELECT
                DISTINCT purchase_requisition_id
            FROM
             (
                 SELECT 
                    tmp.purchase_requisition_id,
                    SUM(qty_required - qty_used) AS qty
                FROM 
                    (
                        SELECT 
                            pr.id,
                            pr.id AS purchase_requisition_id,
                            stock_id, 
                            SUM(qty) AS qty_required, 
                            0 AS qty_used 
                        FROM
                            pr_purchase_requisition pr,
                            pr_purchase_requisition_data prd,
                            distinct_id did
                        WHERE 
                            pr.id = prd.purchase_requisition_id AND
                            pr.distinct_id = did.id AND
                            did.is_deleted <> 'Y' AND
                            voucher_type_id = ".escape(VoucherType::PR, 'int')."
                        GROUP BY 
                            pr.id, stock_id
                        
                        UNION
                        
                        SELECT 
                            pr.id,
                            purchase_requisition_id,
                            stock_id, 
                            0 AS qty_required, 
                            SUM(qty-qty_return) AS qty_used 
                        FROM
                            pr_purchase pr,
                            `pr_purchase_data` prd,
                            distinct_id did
                        WHERE 
                            pr.id = prd.purchase_id AND
                            pr.distinct_id = did.id AND
                            purchase_order_id IS NOT NULL AND
                            did.is_deleted <> 'Y' AND
                            voucher_type_id = ".escape(VoucherType::POV)."
                        GROUP BY 
                            purchase_requisition_id, stock_id
                    ) AS tmp,
                    pr_purchase_requisition prl,
                    distinct_id did1
                WHERE
                    prl.id = tmp.id AND
                    prl.distinct_id = did1.id
                GROUP BY 
                    tmp.purchase_requisition_id, tmp.stock_id
                HAVING 
                    qty > 0
             ) AS tmp1
        ");
    }

    function getRemainingStockFromOrder($PurchaseOrderId)
    {
        return DBQM::query("
                SELECT 
                    tmp.stock_id, 
                    tmp.attribute_id,
                    prd.item_dis,
                    SUM(qty_required - qty_used) AS qty,
                    prd.rate
                FROM 
                    (
                        SELECT 
                            stock_id,
                            attribute_id,
                            SUM(qty-qty_return) AS qty_required, 
                            0 AS qty_used 
                        FROM 
                            `pr_purchase_data` 
                        WHERE 
                            purchase_id = ".escape($PurchaseOrderId, 'int')."
                        GROUP BY 
                            stock_id, attribute_id
                        
                        UNION
                        
                        SELECT 
                            stock_id, 
                            attribute_id,
                            0 AS qty_required, 
                            SUM(qty-qty_return) AS qty_used 
                        FROM 
                            `pr_purchase_data` prd, 
                            pr_purchase pr 
                        where 
                            pr.id = prd.purchase_id AND 
                            pr.purchase_order_id = ".escape($PurchaseOrderId, 'int')." 
                        GROUP BY 
                            stock_id, attribute_id
                    ) AS tmp,
                    pr_purchase_data prd
                WHERE
                    tmp.stock_id = prd.stock_id AND
                    tmp.attribute_id <=> prd.attribute_id AND
                    prd.purchase_id = ".escape($PurchaseOrderId, 'int')." 
                GROUP BY
                    stock_id, attribute_id
                HAVING
                    qty > 0
        ");
    }

    function getRemainingStockFromRequisition($PurchaseRequisitionId)
    {
        return DBQM::query("
                SELECT 
                    tmp.stock_id,
                    tmp.attribute_id,
                    SUM(qty_required - qty_used) AS qty
                FROM 
                    (
                        SELECT 
                            stock_id, 
                            attribute_id,
                            SUM(qty) AS qty_required, 
                            0 AS qty_used 
                        FROM 
                            pr_purchase_requisition_data 
                        WHERE 
                            purchase_requisition_id = ".escape($PurchaseRequisitionId, 'int')."
                        GROUP BY 
                            stock_id, attribute_id
                        
                        UNION
                        
                        SELECT 
                            stock_id, 
                            attribute_id,
                            0 AS qty_required, 
                            SUM(qty-qty_return) AS qty_used 
                        FROM 
                            `pr_purchase_data` prd, 
                            pr_purchase pr 
                        where 
                            pr.id = prd.purchase_id AND 
                            pr.purchase_requisition_id = ".escape($PurchaseRequisitionId, 'int')." 
                        GROUP BY 
                            stock_id, attribute_id
                    ) AS tmp,
                    pr_purchase_requisition_data prd
                WHERE
                    tmp.stock_id = prd.stock_id AND
                    tmp.attribute_id <=> prd.attribute_id AND
                    prd.purchase_requisition_id = ".escape($PurchaseRequisitionId, 'int')." 
                GROUP BY
                    stock_id, attribute_id
                HAVING
                    qty > 0
        ");
    }
}