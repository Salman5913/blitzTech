<?php
/**
 * Created by PhpStorm.
 * User: musta
 * Date: 0019, 9/19/2019
 * Time: 9:48 AM
 */

class Aging
{
    private $AccountId;
    private $InvoiceArray;
    private $PaymentReceiptArray;

    private $UnclearedBillDistinctIds;
    private $ZeroAmount;

    private $InvoiceVoucherType;
    private $PaymentReceiptVoucherType;
    private $Multiplier = 1;
    private static $TotalBalance = 0;

    public function __construct($AccountId, $Type)
    {
        $this->AccountId = $AccountId;

        if ($Type == 'customer')
        {
            $this->InvoiceVoucherType = array_merge(VoucherType::getSalesVoucherTypes(false), [VoucherType::PRV, VoucherType::OPA, VoucherType::CPV, VoucherType::BPV]);
            $this->PaymentReceiptVoucherType = [VoucherType::BRV, VoucherType::CRV, VoucherType::CQF, VoucherType::CQR, VoucherType::CQV, VoucherType::SRV, VoucherType::PIV];
            $this->Multiplier = -1;
        }
        elseif ($Type == 'supplier')
        {
            $this->InvoiceVoucherType = [VoucherType::PIV, VoucherType::SRV, VoucherType::OPA, VoucherType::CRV, VoucherType::BRV];
            $this->PaymentReceiptVoucherType = array_merge(VoucherType::getSalesVoucherTypes(false), [VoucherType::BPV, VoucherType::CPV, VoucherType::CQF, VoucherType::CQR, VoucherType::CQV, VoucherType::PRV]);
            $this->Multiplier = 1;
        }

        $this->calculateData();
    }

    private function calculateData()
    {
        $SelectInvoices = $this->getBalancesByDistinctId($this->AccountId, $this->InvoiceVoucherType, 'B');
        $SelectPaymentReceipts = $this->getBalancesByDistinctId($this->AccountId, $this->PaymentReceiptVoucherType, 'P');

        $this->PaymentReceiptArray = [];

        /* ******* Record All Sales & Payment In an Array ********* */
        while ($RowInvoices = DBQM::fetch_assoc($SelectInvoices))
        {
            if (!empty($RowInvoices['distinct_id']))
                $this->InvoiceArray[$RowInvoices['distinct_id']] = $RowInvoices['sum_amount'] * $this->Multiplier * -1;
        }
        while ($RowPaymentReceipts = DBQM::fetch_assoc($SelectPaymentReceipts))
        {
            if (!empty($RowPaymentReceipts['distinct_id']))
                $this->PaymentReceiptArray[$RowPaymentReceipts['distinct_id']] = $RowPaymentReceipts['sum_amount'] * $this->Multiplier;
        }
        /* ******* Record All Sales & Payment In an Array ********* */


        /**
         * Traverse all sales until all payments are adjusted in sales
         * Sales that remains with balances are the ones who's payments have not been received.
         */
        foreach ($this->InvoiceArray as $InvoiceDistinctId => $InvoiceAmount)
        {
            $InvoiceAmount = $this->deductPayment($InvoiceDistinctId);

            if (isset($this->InvoiceArray[$InvoiceDistinctId]))
                $this->InvoiceArray[$InvoiceDistinctId] = $InvoiceAmount;
        }

        /* ******** Get all Distinct Ids of remaining sales in an array ********* */
        foreach ($this->InvoiceArray as $DistinctId => $Amount)
        {
            $this->UnclearedBillDistinctIds[] = $DistinctId;
            $this->ZeroAmount[] = $Amount;
        }
        /* ******** Get all Distinct Ids of remaining sales in an array ********* */
    }

    function getRemainingAmountByDistinctId($DistinctId)
    {
        return $this->InvoiceArray[$DistinctId];
    }

    function hasAgingData()
    {
        $Count = 0;
        foreach ($this->ZeroAmount as $Amount)
        {
            if ($Amount > 0)
                $Count++;
        }

        return $Count > 0;
        //return count($this->UnclearedBillDistinctIds) > 0;
    }

    private function deductPayment($SalesDistinctId)
    {
        foreach ($this->PaymentReceiptArray as $PaymentDistinctId => $PaymentAmount)
        {
            if (!isset($this->PaymentReceiptArray[$PaymentDistinctId]))
                continue;

            $PaymentAmount = $this->PaymentReceiptArray[$PaymentDistinctId];

            $Deduction =  ($this->InvoiceArray[$SalesDistinctId] >= $PaymentAmount) ? $PaymentAmount : $this->InvoiceArray[$SalesDistinctId];

            $this->InvoiceArray[$SalesDistinctId] -= $Deduction;
            $this->PaymentReceiptArray[$PaymentDistinctId] -= $Deduction;

            if ($this->PaymentReceiptArray[$PaymentDistinctId] == 0) {
                unset($this->PaymentReceiptArray[$PaymentDistinctId]);
            }

            if ($this->InvoiceArray[$SalesDistinctId] == 0) {
                unset($this->InvoiceArray[$SalesDistinctId]);
                return 0;
            }

            if (count($this->PaymentReceiptArray) > 0 and $this->InvoiceArray[$SalesDistinctId] > 0)
                $this->deductPayment($SalesDistinctId);
        }

        return $this->InvoiceArray[$SalesDistinctId];
    }

    private function getDates($Column, $DayFrom, $DayTo, $DateTill='')
    {
        $DateTill = empty($DateTill) ? DatabaseHelper::DATE() : DateEncode($DateTill);

        $TillDate = date('Y-m-d', strtotime($DateTill." -".$DayFrom." days"));
        $FromDate = !empty($DayTo) ? date('Y-m-d', strtotime($DateTill." -".$DayTo." days")) : '';

        return UtilsQueryMaker::date($Column, $FromDate, $TillDate);
    }

    function getDaysPassed($InvoiceDate)
    {
        $Now = time();
        $InvoiceTime = strtotime($InvoiceDate);
        $TimeDifference = $Now - $InvoiceTime;

        return round($TimeDifference / (60 * 60 * 24));
    }

    function getAging($DayFrom, $DayTill, $DateTill='')
    {
        $Where = UtilsQueryMaker::equal("ld.account_id", $this->AccountId);
        $Column = $this->Multiplier == -1 ? "SUM(ld.debit - ld.credit)" : "SUM(ld.credit - ld.debit)";

        if (!empty($this->UnclearedBillDistinctIds))
        {
            return DBQM::query("
                            SELECT 
                                    did.voucher_date,
                                    did.voucher_number,
                                    did.id AS distinct_id,
                                    vt.name AS voucher_type,
                                    ({$Column}) AS sum_amount
                            FROM
                                    vt_voucher_type vt,
                                    distinct_id did, 
                                    ledger l,
                                    ledger_data ld
                            WHERE
                                    vt.id = did.voucher_type_id AND
                                    l.distinct_id = did.id AND
                                    l.id = ld.ledger_id AND 
                                    did.id IN (".implode(", ",array_map("escape", $this->UnclearedBillDistinctIds)).")
                                    {$this->getDates('did.voucher_date', $DayFrom, $DayTill, $DateTill)}
                                    {$Where}
                            GROUP BY
                                    did.id
                            ORDER BY
                                    DATE(did.voucher_date) DESC
            ");
        }
    }

    function getCompleteAging($DateTill='')
    {
        $Where = UtilsQueryMaker::equal("ld.account_id", $this->AccountId);
        $Column = $this->Multiplier == -1 ? "SUM(ld.debit - ld.credit)" : "SUM(ld.credit - ld.debit)";

        if (!empty($this->UnclearedBillDistinctIds))
        {
            return DBQM::query("
                            SELECT 
                                tmp.*
                            FROM
                            (
                                SELECT 
                                    did.voucher_date,
                                    did.voucher_number,
                                    did.id AS distinct_id,
                                    vt.name AS voucher_type,
                                    ({$Column}) AS sum_amount_0_7
                                FROM
                                    vt_voucher_type vt,
                                    distinct_id did, 
                                    ledger l,
                                    ledger_data ld
                                WHERE
                                    vt.id = did.voucher_type_id AND
                                    l.distinct_id = did.id AND
                                    l.id = ld.ledger_id AND 
                                    did.id IN (".implode(", ",array_map("escape", $this->UnclearedBillDistinctIds)).")
                                    {$this->getDates('did.voucher_date', "0", "7", $DateTill)}
                                    {$Where}
                                GROUP BY
                                    did.id
                                    
                                UNION
                                
                                SELECT 
                                    did.voucher_date,
                                    did.voucher_number,
                                    did.id AS distinct_id,
                                    vt.name AS voucher_type,
                                    ({$Column}) AS sum_amount_8_14
                                FROM
                                    vt_voucher_type vt,
                                    distinct_id did, 
                                    ledger l,
                                    ledger_data ld
                                WHERE
                                    vt.id = did.voucher_type_id AND
                                    l.distinct_id = did.id AND
                                    l.id = ld.ledger_id AND 
                                    did.id IN (".implode(", ",array_map("escape", $this->UnclearedBillDistinctIds)).")
                                    {$this->getDates('did.voucher_date', "8", "14", $DateTill)}
                                    {$Where}
                                GROUP BY
                                    did.id
                                    
                                UNION
                                
                                
                                SELECT 
                                    did.voucher_date,
                                    did.voucher_number,
                                    did.id AS distinct_id,
                                    vt.name AS voucher_type,
                                    ({$Column}) AS sum_amount_15_21
                                FROM
                                    vt_voucher_type vt,
                                    distinct_id did, 
                                    ledger l,
                                    ledger_data ld
                                WHERE
                                    vt.id = did.voucher_type_id AND
                                    l.distinct_id = did.id AND
                                    l.id = ld.ledger_id AND 
                                    did.id IN (".implode(", ",array_map("escape", $this->UnclearedBillDistinctIds)).")
                                    {$this->getDates('did.voucher_date', "15", "21", $DateTill)}
                                    {$Where}
                                GROUP BY
                                    did.id
                                    
                                UNION
                                
                                
                                SELECT 
                                    did.voucher_date,
                                    did.voucher_number,
                                    did.id AS distinct_id,
                                    vt.name AS voucher_type,
                                    ({$Column}) AS sum_amount_22_28
                                FROM
                                    vt_voucher_type vt,
                                    distinct_id did, 
                                    ledger l,
                                    ledger_data ld
                                WHERE
                                    vt.id = did.voucher_type_id AND
                                    l.distinct_id = did.id AND
                                    l.id = ld.ledger_id AND 
                                    did.id IN (".implode(", ",array_map("escape", $this->UnclearedBillDistinctIds)).")
                                    {$this->getDates('did.voucher_date', "22", "28", $DateTill)}
                                    {$Where}
                                GROUP BY
                                    did.id
                                    
                                UNION
                                
                                
                                SELECT 
                                    did.voucher_date,
                                    did.voucher_number,
                                    did.id AS distinct_id,
                                    vt.name AS voucher_type,
                                    ({$Column}) AS sum_amount_29_45
                                FROM
                                    vt_voucher_type vt,
                                    distinct_id did, 
                                    ledger l,
                                    ledger_data ld
                                WHERE
                                    vt.id = did.voucher_type_id AND
                                    l.distinct_id = did.id AND
                                    l.id = ld.ledger_id AND 
                                    did.id IN (".implode(", ",array_map("escape", $this->UnclearedBillDistinctIds)).")
                                    {$this->getDates('did.voucher_date', "29", "45", $DateTill)}
                                    {$Where}
                                GROUP BY
                                    did.id
                                    
                                UNION
                                
                                
                                SELECT 
                                    did.voucher_date,
                                    did.voucher_number,
                                    did.id AS distinct_id,
                                    vt.name AS voucher_type,
                                    ({$Column}) AS sum_amount_46_60
                                FROM
                                    vt_voucher_type vt,
                                    distinct_id did, 
                                    ledger l,
                                    ledger_data ld
                                WHERE
                                    vt.id = did.voucher_type_id AND
                                    l.distinct_id = did.id AND
                                    l.id = ld.ledger_id AND 
                                    did.id IN (".implode(", ",array_map("escape", $this->UnclearedBillDistinctIds)).")
                                    {$this->getDates('did.voucher_date', "46", "60", $DateTill)}
                                    {$Where}
                                GROUP BY
                                    did.id
                                    
                                UNION
                                
                                
                                SELECT 
                                    did.voucher_date,
                                    did.voucher_number,
                                    did.id AS distinct_id,
                                    vt.name AS voucher_type,
                                    ({$Column}) AS sum_amount_61_90
                                FROM
                                    vt_voucher_type vt,
                                    distinct_id did, 
                                    ledger l,
                                    ledger_data ld
                                WHERE
                                    vt.id = did.voucher_type_id AND
                                    l.distinct_id = did.id AND
                                    l.id = ld.ledger_id AND 
                                    did.id IN (".implode(", ",array_map("escape", $this->UnclearedBillDistinctIds)).")
                                    {$this->getDates('did.voucher_date', "61", "90", $DateTill)}
                                    {$Where}
                                GROUP BY
                                    did.id
                                    
                                UNION
                                
                                
                                SELECT 
                                    did.voucher_date,
                                    did.voucher_number,
                                    did.id AS distinct_id,
                                    vt.name AS voucher_type,
                                    ({$Column}) AS sum_amount_91_8000
                                FROM
                                    vt_voucher_type vt,
                                    distinct_id did, 
                                    ledger l,
                                    ledger_data ld
                                WHERE
                                    vt.id = did.voucher_type_id AND
                                    l.distinct_id = did.id AND
                                    l.id = ld.ledger_id AND 
                                    did.id IN (".implode(", ",array_map("escape", $this->UnclearedBillDistinctIds)).")
                                    {$this->getDates('did.voucher_date', "91", "8000", $DateTill)}
                                    {$Where}
                                GROUP BY
                                    did.id
                            
                            ) AS tmp 
                            ORDER BY
                                DATE(voucher_date) ASC
            ");
        }
    }

    function getBalancesByDistinctId($AccountId, array $VoucherTypeIds, $Type='')
    {
        $AccountType = $this->Multiplier < 0 ? 'C' : 'S';

        $Where = UtilsQueryMaker::inOrEqual("l.voucher_type_id", $VoucherTypeIds);
        $SumAmount = $Type == 'P' ? 'SUM( credit * -1 )' : 'SUM( debit )';

        return DBQM::query("
                                SELECT 
                                    SUM(sum_amount) as sum_amount,
                                    distinct_id,
                                    date_time
                                FROM 
                                (
                                    SELECT
                                        SUM( debit - credit ) AS sum_amount,
                                        distinct_id,
                                        date_time
                                    FROM
                                        ledger l,
                                        ledger_data ld 
                                    WHERE
                                        l.id = ld.ledger_id AND 
                                        ld.account_id = ".escape($AccountId)." 
                                        {$Where}
                                    GROUP BY
                                        distinct_id 
                                
                                    union
                                    
                                    SELECT
                                        ({$SumAmount}) AS sum_amount,
                                        distinct_id ,
                                        date_time
                                    FROM
                                        ledger l,
                                        ledger_data ld 
                                    WHERE
                                        l.id = ld.ledger_id AND 
                                        ld.account_id = ".escape($AccountId)." AND 
                                        l.voucher_type_id = ".escape(VoucherType::JV)."
                                    GROUP BY
                                        distinct_id 
                                ) as tmp
                                GROUP BY 
                                    distinct_id
                                ORDER BY 
                                    date_time
        ");
    }

    function getAgingValue($DayFrom, $DayTill)
    {
        $TotalRemaining = 0;
        $Select= $this->getAging($DayFrom, $DayTill);

        while ($Row = DBQM::fetch_assoc($Select))
        {
            $Remaining = $this->getRemainingAmountByDistinctId($Row['distinct_id']);
            $TotalRemaining += $Remaining;

        }

        self::$TotalBalance += $TotalRemaining;

        if ($TotalRemaining <> 0)
            return Utils::format($TotalRemaining, 0);
        else
            return '';
    }

    static function getTotalValue()
    {

        return self::$TotalBalance;
    }

    static function setVariableZero()
    {

        return self::$TotalBalance = 0;
    }
}