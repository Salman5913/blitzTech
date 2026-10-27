<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 27/12/2014
 * Time: 9:22 AM
 */

/**
 * Old name was record_stock
 * Class StockTransaction
 */
class StockLedger
{

    /**
     * @var Ledger
     */
    private $oLedger;
    private $DistinctId;
    /**
     * @var VoucherType
     */
    private $oVoucherType;
    private $VoucherNumber;
    private $DateTime;

    function __construct(Ledger $oLedger, $DistinctId, VoucherType $oVoucherType, $VoucherNumber, $DateTime)
    {
        $this->oLedger = $oLedger;
        $this->DistinctId = $DistinctId;
        $this->oVoucherType = $oVoucherType;
        $this->VoucherNumber = $VoucherNumber;
        $this->DateTime = $DateTime;

        /*$this->deleteByDistinctId($DistinctId);*/
    }

    private function recordLedger($BatchId, $BranchId, $Qty, $SellingPrice)
    {
        DBQM::query("INSERT INTO st_stock_ledger VALUES (
                                                            0,
                                                            ".escape(Year::getYearId(), 'int').",
                                                            ".escape($this->DistinctId, 'int').",
                                                            ".escape($BranchId, 'int').",
                                                            ".escape($BatchId, 'int').",
                                                            ".escape($this->oVoucherType->getVoucherType(), 'int').",
                                                            ".escape($this->VoucherNumber, 'int').",
                                                            ".escape($this->DateTime).",
                                                            ".escape($Qty, 'double').",
                                                            ".escape($SellingPrice, 'double')."
        )");
    }

    /**
     * Ledger entry will be done by system
     *
     * @param $StockId
     * @param $AttributeId
     * @param $BranchId
     * @param $Qty
     * @param int $CostPrice
     * @param string $SupplierId
     * @param string $PurchaseDataId
     * @param string $Description
     */
    function recordPurchase($StockId, $AttributeId, $BranchId, $Qty, $CostPrice, $SupplierId='', $PurchaseDataId='', $Description= '', $InvoiceType)
    {
        $oStockBatch = new StockBatch();
        $oStockBatchTransition = new StockBatchTransition();

        /**
         * Find if any batch is available in transition for this stock, use that batch number and then delete that batch from transition table
         * else, create new batch
         */
        $RowBatchTransition = $oStockBatchTransition->getBatchTransitionByDistinctId($this->DistinctId, $StockId, $AttributeId);

        if (!empty($RowBatchTransition['id']))
        {
            $BatchId = $oStockBatch->createNewBatch($StockId, $AttributeId, $RowBatchTransition['batch_number'], $Qty, $CostPrice, $RowBatchTransition['batch_id']);

            if ($BatchId)
                $oStockBatchTransition->delete($RowBatchTransition['id']);
        }
        else
        {
            $BatchNo = $oStockBatch->getNextBatchNumber($StockId);
            $BatchId = $oStockBatch->createNewBatch($StockId, $AttributeId, $BatchNo, $Qty, $CostPrice);
        }

        if ($BatchId)
            Purchase::updateBatchNumber($this->oVoucherType->getVoucherType(), $this->VoucherNumber, $BatchId, $StockId, $AttributeId, $PurchaseDataId);

        $this->recordLedger($BatchId, $BranchId, $Qty, 0);


        /* ****************** Stock In *************** */
        if (!empty($SupplierId) AND $InvoiceType != Purchase::TYPE_DELIVERY)
        {
            $this->oLedger->saveData($SupplierId, "-".($CostPrice * $Qty),1, $Description."Stock Addition", getConstantAccount()->getPurchaseAccount(), '', 'N', $StockId, $AttributeId, $Qty, $CostPrice);
            $this->oLedger->saveData(getConstantAccount()->getPurchaseAccount(), $CostPrice * $Qty,1, $Description."Stock Addition", $SupplierId, '', 'N', $StockId, $AttributeId, $Qty, $CostPrice);
        }
        /* ****************** Stock In *************** */
    }

    /**
     * @param $StockId
     * @param $AttributeId
     * @param $Qty
     * @param string $SellingPrice if COST is passed, then COST prices will be used as selling price, else if empty, batch selling price will be used
     * @param $BranchId
     * @return float|int COGS
     */
    function recordSales($StockId, $AttributeId, $Qty, $SellingPrice, $BranchId)
    {
        $oStockBatch = new StockBatch();

        $RemainingQty = $Qty;
        $LastQty = 100;

        $TotalCOGS = 0;
        $WhereAttribute = UtilsQueryMaker::equalOrNull("attribute_id", $AttributeId);
        $WhereBranch = UtilsQueryMaker::equal("branch_id", $BranchId);

        /**
         * For recording branch transaction
         */
        $QtyTransferred = 0;

        if ($Qty > 0)
        {
            while ($RemainingQty > 0 and $LastQty > 0)
            {
                $SelectBatch = DBQM::query("
                        SELECT
                            batch_id,
                            b.cost,
                            SUM(l.qty) AS sum_qty
                        FROM
                            st_stock_ledger l,
                            st_stock_batch b
                        WHERE
                            l.batch_id = b.id AND
                            stock_id = ".escape($StockId, 'int')."
                            {$WhereAttribute}
                            {$WhereBranch}
                        GROUP BY
                            batch_id 
                        HAVING
                            sum_qty > 0 
                        ORDER BY
                            b.batch_number
                ");
                $RowBatch = DBQM::fetch_assoc($SelectBatch);

                $QtyUsed = $RemainingQty > $RowBatch['sum_qty'] ? $RowBatch['sum_qty'] : $RemainingQty;

                if ($SellingPrice == 'COST')
                    $SellingPrice = $RowBatch['cost'];


                $RemainingQty -= $QtyUsed;
                $LastQty = $QtyUsed;
                $QtyTransferred += $QtyUsed;

                $COGS = $QtyUsed * $RowBatch['cost'];
                $TotalCOGS += $COGS;

                $this->recordLedger($RowBatch['batch_id'], $BranchId, "-".$QtyUsed, $SellingPrice);
            }
        }
        else
        {
            $RemainingQty = abs($RemainingQty);
            while ($RemainingQty > 0 and $LastQty > 0)
            {
                /**
                 * First search for current open batch, if there is space for new stock (purchase qty - sold qty)
                 * Example: 50 purchased, 40 current stock => 50 - 40 = 10
                 *
                 * there is space of 10 items in this stock
                 */
                $SelectCurrentBatchStock = DBQM::query("
                    SELECT
                        batch_id,
                        cost,
                        SUM(l.qty) AS sum_qty
                    FROM
                        st_stock_batch b,
                        st_stock_ledger l
                    WHERE
                        l.batch_id = b.id AND
                        stock_id = ".escape($StockId)."
                        {$WhereAttribute}
                        {$WhereBranch}
                    GROUP BY
                        batch_id 
                    HAVING
                        sum_qty > 0 
                    ORDER BY
                        batch_number 
                    LIMIT 1
                ");
                $RowBatch = DBQM::fetch_assoc($SelectCurrentBatchStock);

                $RemainingBatchQty = $RowBatch['sum_qty'];

                /**
                 * Check how much qty has been purchased of current batch
                 */
                $PurchaseQty = $oStockBatch->getPurchaseQtyOfBatch($RowBatch['batch_id']);
                $SoldQty = $PurchaseQty - $RemainingBatchQty;


                /**
                 * if nothing is sold from current batch,
                 * add return stock to previous batch
                 */
                if ($SoldQty == 0)
                {
                    $SelectBatch = DBQM::query("
                        SELECT
                            batch_id,
                            cost,
                            SUM( l.qty ) AS sum_qty 
                        FROM
                            st_stock_batch b,
                            st_stock_ledger l 
                        WHERE
                            l.batch_id = b.id AND
                            stock_id = ".escape($StockId)."
                            {$WhereAttribute}
                            {$WhereBranch}
                        GROUP BY
                            batch_id 
                        HAVING
                            sum_qty <= 0 
                        ORDER BY
                            batch_number DESC
                    ");
                    $RowBatch = DBQM::fetch_assoc($SelectBatch);

                    $RemainingBatchQty = $RowBatch['sum_qty'];

                    $PurchaseQty = $oStockBatch->getPurchaseQtyOfBatch($RowBatch['batch_id']);
                    $SoldQty = $PurchaseQty - $RemainingBatchQty;
                }

                $QtyBack = $SoldQty > $RemainingQty ? $RemainingQty : $SoldQty;
                $QtyTransferred -= $QtyBack;

                if ($SellingPrice == 'COST')
                    $SellingPrice = $RowBatch['cost'];

                $RemainingQty -= $QtyBack;
                $COGS = $QtyBack * $RowBatch['cost'];
                $TotalCOGS += $COGS;


                $LastQty = $QtyBack;

                $this->recordLedger($RowBatch['batch_id'], $BranchId, $QtyBack, $SellingPrice);
            }
        }

        if ($RemainingQty != 0)
        {
            ErrorHandler::setError( "Qty not adjusted properly, either stock ran out or some other problem occurred. Stock Name: " . Stock::getName( $StockId, $AttributeId ) . " - " . round( $RemainingQty, 4 ) . " Qty unadjusted!" );
        }

        return $TotalCOGS;
    }

    /**
     * If delete requested from purchase voucher, move batch to transition table first
     * @param $DistinctId
     */
    static function deleteByDistinctId($DistinctId)
    {
        $oStockBatch = new StockBatch();
        $oStockBatchTransition = new StockBatchTransition();

        $VoucherTypeId = DistinctId::getVoucherTypeIdByDistinctId($DistinctId);

        if (VoucherType::isPurchaseVoucher($VoucherTypeId) OR ($VoucherTypeId == VoucherType::BMV || $VoucherTypeId == VoucherType::SMV))
        {
            if ($VoucherTypeId == VoucherType::BMV || $VoucherTypeId == VoucherType::SMV)
                $SelectLedger = self::getStockLedgerByDistinctIdForManufacturing($DistinctId);
            else
                $SelectLedger = self::getStockLedgerByDistinctId($DistinctId);


            while ($RowLedger = DBQM::fetch_assoc($SelectLedger))
            {
                $StockBatchDetails = StockBatch::getStockBatchDetails($RowLedger['batch_id']);

                $QtyIn = $RowLedger['qty'] > 0 ? $RowLedger['qty'] : 0;
                $QtyOut = $RowLedger['qty'] < 0 ? $RowLedger['qty'] : 0;

                $BatchTransitionId = $oStockBatchTransition->saveRecord($StockBatchDetails['id'], $RowLedger['branch_id'], $StockBatchDetails['stock_id'], $StockBatchDetails['attribute_id'], $DistinctId, $StockBatchDetails['batch_number'], $QtyIn, $QtyOut);

                DBQM::query("DELETE FROM st_stock_ledger WHERE distinct_id = ".escape($DistinctId, 'int')." AND batch_id = ".escape($StockBatchDetails['id'])." AND year_id = ".escape(Year::getYearId()));

                $oStockBatch->deleteBatch(StockBatchTransition::getBatchIId($BatchTransitionId));
            }
        }
        else
            DBQM::query("DELETE FROM st_stock_ledger WHERE distinct_id = ".escape($DistinctId, 'int')." AND year_id = ".escape(Year::getYearId()));

        if ($VoucherTypeId == VoucherType::BMV || $VoucherTypeId == VoucherType::SMV)
            DBQM::query("DELETE FROM st_stock_ledger WHERE distinct_id = ".escape($DistinctId, 'int')." AND year_id = ".escape(Year::getYearId()));
    }

    static function getQty($StockId)
    {
        $Select = DBQM::query("SELECT SUM(stl.qty) FROM st_stock_ledger stl, st_stock_batch stb WHERE stl.batch_id = stb.id AND stb.stock_id = ".escape($StockId, 'int')." AND stl.year_id = ".escape(Year::getYearId()));
        return DBQM::result($Select, 0);
    }

    static function getBranchQty($StockId, $BranchId)
    {
        $Select = DBQM::query("SELECT SUM(stl.qty) FROM st_stock_ledger stl, st_stock_batch stb WHERE stl.batch_id = stb.id AND stb.stock_id = ".escape($StockId, 'int')." AND stl.branch_id = ".escape($BranchId)." AND stl.year_id = ".escape(Year::getYearId()));
        return DBQM::result($Select, 0);
    }

    static function getStockLedgerByDistinctId($DistinctId)
    {
        return DBQM::query("SELECT * FROM st_stock_ledger WHERE distinct_id = ".escape($DistinctId, 'int')." AND year_id = ".escape(Year::getYearId()));
    }

    static function getStockLedgerByDistinctIdForManufacturing($DistinctId)
    {
        return DBQM::query("SELECT * FROM st_stock_ledger WHERE distinct_id = ".escape($DistinctId, 'int')." and qty > 0");
    }
}