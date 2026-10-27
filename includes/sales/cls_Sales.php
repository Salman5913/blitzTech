<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 24/12/2014
 * Time: 9:19 PM
 */

class Sales extends BasicVoucher
{
    const IND_DELIVERY = 'DELIVERY';
    const IND_DATA = 'DATA';
    const IND_NON_INVENTORY = 'NON INVENTORY';
    const IND_PAYMENT = 'PAYMENT';

    const TYPE_DELIVERY = 'D';
    const TYPE_INVOICE = 'I';

    public function __construct()
    {
        parent::__construct(
            "sl_sales",
            [
                self::IND_DATA => "sl_sales_data",
                self::IND_DELIVERY => "sl_sales_delivery",
                self::IND_PAYMENT => "sl_sales_payment",
                self::IND_NON_INVENTORY => "sl_sales_non_inventory"
            ],
            "sales_id",
            array_merge(
                VoucherType::getSalesVoucherTypes(false),
                [
                    VoucherType::SOV,
                    VoucherType::SRV
                ]
            )
        );
    }

    static function isAvailableSalesOrders()
    {
        $Select = DBQM::query("SELECT COUNT(s.id) FROM sl_sales s, distinct_id d WHERE d.id = s.distinct_id AND d.is_deleted <> 'Y' AND d.voucher_type_id = ".escape(VoucherType::SOV));

        return (int) DBQM::result($Select, 0);
    }

    function getAllPendingOrders($CustomerAccountId)
    {
        return DBQM::query("
            SELECT
                DISTINCT sales_order_id
            FROM
             (
                 SELECT 
                    voucher_number,
                    tmp.sales_order_id,
                    SUM(qty_required - qty_used) AS qty
                FROM 
                    (
                        SELECT 
                            sl.id,
                            sl.id AS sales_order_id,
                            stock_id, 
                            SUM(qty-qty_return) AS qty_required, 
                            0 AS qty_used 
                        FROM
                            sl_sales sl,
                            sl_sales_data sld,
                            distinct_id did
                        WHERE 
                            sl.id = sld.sales_id AND
                            sl.distinct_id = did.id AND
                            did.is_deleted <> 'Y' AND
                            voucher_type_id = ".escape(VoucherType::SOV, 'int')." AND
                            customer_account_id = ".escape($CustomerAccountId, 'int')."
                        GROUP BY 
                            sl.id, stock_id
                        
                        UNION
                        
                        SELECT 
                            sl.id,
                            sales_order_id,
                            stock_id, 
                            0 AS qty_required, 
                            SUM(qty-qty_return) AS qty_used 
                        FROM
                            sl_sales sl,
                            sl_sales_data sld,
                            distinct_id did
                        WHERE 
                            sl.id = sld.sales_id AND
                            sl.distinct_id = did.id AND
                            sales_order_id IS NOT NULL AND
                            did.is_deleted <> 'Y' AND
                            voucher_type_id IN (".VoucherType::getSalesVoucherTypes().", ".escape(VoucherType::SRV).") AND
                            customer_account_id = ".escape($CustomerAccountId, 'int')."
                        GROUP BY 
                            sales_order_id, stock_id
                    ) AS tmp,
                    sl_sales sll,
                    distinct_id did1
                WHERE
                    sll.id = tmp.id AND
                    sll.distinct_id = did1.id
                GROUP BY 
                    tmp.sales_order_id, tmp.stock_id
                HAVING 
                    qty > 0
             ) AS tmp1
        ");
    }

    function getRemainingStockFromOrder($SalesOrderId)
    {
        return DBQM::query("
                SELECT 
                    tmp.stock_id,
                    tmp.attribute_id,
                    SUM(qty_required - qty_used) AS qty,
                    sld.selling_price,
                    sld.item_disc
                FROM 
                    (
                        SELECT 
                            stock_id,
                            attribute_id,
                            SUM(qty-qty_return) AS qty_required, 
                            0 AS qty_used 
                        FROM 
                            `sl_sales_data` 
                        WHERE 
                            sales_id = ".escape($SalesOrderId, 'int')."
                        GROUP BY 
                            stock_id, attribute_id
                        
                        UNION
                        
                        SELECT 
                            stock_id,
                            attribute_id, 
                            0 AS qty_required, 
                            SUM(qty-qty_return) AS qty_used 
                        FROM 
                            `sl_sales_data` sd, 
                            sl_sales sl 
                        where 
                            sl.id = sd.sales_id AND 
                            sl.sales_order_id = ".escape($SalesOrderId, 'int')." 
                        GROUP BY 
                            stock_id, attribute_id
                    ) AS tmp,
                    sl_sales_data sld
                WHERE
                    tmp.stock_id = sld.stock_id AND
                    tmp.attribute_id <=> sld.attribute_id AND
                    sld.sales_id = ".escape($SalesOrderId, 'int')." 
                GROUP BY
                    stock_id, attribute_id
                HAVING
                    qty > 0
        ");
    }

    protected function saveTransactionEntries()
    {
        $VoucherTypeId = $this->getVoucherType();

        /* ************* Do not perform any transactional entries if Order voucher or Requisition voucher ************* */
        if ($VoucherTypeId == VoucherType::SOV)
            return;
        /* ************* Do not perform any transactional entries if Order voucher or Requisition voucher ************* */


        $oLedger = $this->getLedgerObject();
        $oStockLedger = $this->getStockLedgerObject();

        $oSMSTemplates = new SMSTemplates();

        $RowDetails = $this->getDetails();

        $TotalSales = 0;
        $TotalNonInventory = 0;
        $InvoiceDiscountPercentage = 0;
        $ItemCount = 0;

        $SelectInvoiceData = $this->getData(self::IND_DATA);
        $SelectData = $this->getData(self::IND_NON_INVENTORY);

        $SalesAccount = !empty($RowDetails['project_id']) ? getConstantAccount()->getProjectSales() : getConstantAccount()->getSalesAccount();

        /* ************ Calc Invoice Discount Percentage *************** */
        if ($RowDetails['invoice_discount_calc_value'] > 0)
        {
            $TotalAmount = 0;
            while($RowData = DBQM::fetch_assoc($SelectInvoiceData))
            {
                $SellingPrice = $RowData['selling_price'];
                $Qty = $RowData['qty'] - $RowData['qty_return'];

                $SellingPrice -= Utils::calcPercentValue($SellingPrice, $RowData['item_disc'], 0);

                $TotalAmount += $Qty * $SellingPrice;
            }

            while($Row = DBQM::fetch_assoc($SelectData))
            {
                $SellingPrice = $Row['selling_price'];
                $Qty = $Row['qty'] - $Row['qty_return'];

                $TotalAmount += $Qty * $SellingPrice;
            }

            $InvoiceDiscountPercentage = ($RowDetails['invoice_discount_calc_value'] / $TotalAmount) * 100;
        }
        /* ************ Calc Invoice Discount Percentage *************** */

        DBQM::reset($SelectInvoiceData);
        while($RowData = DBQM::fetch_assoc($SelectInvoiceData))
        {
            $SellingPrice = $RowData['selling_price'];
            $Qty = $RowData['qty'] - $RowData['qty_return'];

            $SellingPrice -= Utils::calcPercentValue($SellingPrice, $RowData['item_disc'], 0);
            $SellingPrice -= Utils::calcPercentValue($SellingPrice, $InvoiceDiscountPercentage, 0);

            $ItemCount += abs($Qty);

            $RowTotal = $Qty * $SellingPrice;
            $TotalSales += $RowTotal; // total amount with discount for inventory

            if (!empty($RowData['stock_id']))
            {
//                $oStockLedger->recordSales($RowData['stock_id'], $RowData['attribute_id'], $Qty, $SellingPrice, $RowDetails['branch_id']);

                $oLedger->saveData($RowDetails['customer_account_id'], $RowTotal,1,'Stock', getConstantAccount()->getSalesAccount(), 'N', 'N', $RowData['stock_id'], $RowData['attribute_id'], $Qty, $SellingPrice, $RowDetails['invoice_type']);
            }
            elseif (!empty($RowData['description']))
            {
                $TotalNonInventory += $Qty * $SellingPrice;
                // Non inventory
                $oLedger->saveData($RowDetails['customer_account_id'], $RowTotal,1, 'Sales - ' . $RowData['description'] . ' x ' . $RowData['qty_return'] > 0 ? $RowData['qty_return'] : $RowData['qty'] . ' @ ' . $SellingPrice, getConstantAccount()->getNonInventoryPurchasesAccount(), '', 'N', '', '', 0, 0, $RowDetails['invoice_type']);
            }

            $this->updateDiscountedRate($RowData['id'], $SellingPrice);
        }

        DBQM::reset($SelectData);
        while($Row = DBQM::fetch_assoc($SelectData))
        {
            $SellingPrice = $Row['selling_price'];
            $Qty = $Row['qty'] - $Row['qty_return'];

            $SellingPrice -= Utils::calcPercentValue($SellingPrice, $InvoiceDiscountPercentage, 0);

            $ItemCount += abs($Qty);

            $RowTotal = $Qty * $SellingPrice;
            $TotalSales += $RowTotal; // total amount with discount for inventory

            $oLedger->saveData($RowDetails['customer_account_id'], $RowTotal,1, 'Sales - ' . $RowData['description'] . ' x ' . $RowData['qty_return'] > 0 ? $RowData['qty_return'] : $RowData['qty'] . ' @ ' . $SellingPrice, null, '', 'N', '', '', 0, 0, $RowDetails['invoice_type']);
        }

        $Multiplier =  $this->getVoucherType() == VoucherType::SRV ? -1 : 1;
        $TotalSales = $TotalSales + ($RowDetails['transport_amount'] * $Multiplier);

        /* **************************** Ledger ****************************** */
        // Invoice Discount & Sales

        if ($RowDetails['invoice_type'] != Sales::TYPE_DELIVERY)
        {
            $oLedger->saveData($SalesAccount, '-'.($TotalSales - $TotalNonInventory),1,"Total Sales", $RowDetails['customer_account_id']);
            $oLedger->saveData($RowDetails['customer_account_id'], ($RowDetails['transport_amount']),1,"Total Transport", $SalesAccount);
            $oLedger->saveData($SalesAccount, '-'.$TotalNonInventory,1,"Total Sales", $RowDetails['customer_account_id']);

            // GST
            $oLedger->saveData($RowDetails['customer_account_id'], $RowDetails['gst_calc_value'] * $Multiplier,1, ProjectHelper::TAX_LABEL, getConstantAccount()->getGSTAccount());
            $oLedger->saveData(getConstantAccount()->getGSTAccount(), '-'.($RowDetails['gst_calc_value'] * $Multiplier),1,ProjectHelper::TAX_LABEL, $RowDetails['customer_account_id']);
        }
        /* **************************** Ledger ****************************** */

        /* **************************** Payments ****************************** */
        if ($RowDetails['invoice_type'] != Sales::TYPE_DELIVERY)
        {
            $SelectPayments = $this->getData(self::IND_PAYMENT);
            while($RowPayment = DBQM::fetch_assoc($SelectPayments))
            {
                $oPaymentLedger = new Ledger($this->getDistinctId(), $RowPayment['payment_date'], VoucherType::SIPV, Ledger::getNewVoucherNumber(VoucherType::SIPV), $this->getVoucherId());

                $oPaymentLedger->saveData($RowDetails['customer_account_id'], "-".$RowPayment['amount'] * $Multiplier,1, $RowPayment['description'], $RowPayment['cash_bank_id']);
                $oPaymentLedger->saveData($RowPayment['cash_bank_id'], ($RowPayment['amount'] * $Multiplier),1, $RowPayment['description'], $RowDetails['customer_account_id']);

                $PaymentAmount += $RowPayment['amount'];
            }
        }
        /* **************************** Payments ****************************** */

        $this->updateVoucherTotal($TotalSales + ($RowDetails['gst_calc_value'] * $Multiplier) + ($RowDetails['advance_tax_percentage'] * $Multiplier));
    }

    function updateDiscountedRate($SalesDataId, $DiscountedRate)
    {
        DBQM::query("UPDATE sl_sales_data SET discounted_rate = ".escape($DiscountedRate, 'double')." WHERE id = ".escape($SalesDataId));
    }
}