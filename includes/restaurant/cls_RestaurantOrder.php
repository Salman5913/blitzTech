<?php
/**
 * Restaurant website orders (rs_orders / rs_order_items), managed from the ERP.
 *
 * A completed order becomes a Sales Invoice (SIV) through postToSales(), called from
 * restaurant/manage_web_orders.php (as the logged-in user).
 */
class RestaurantOrder
{
    const STATUS_RECEIVED = 'received';
    const STATUS_PREPARING = 'preparing';
    const STATUS_ON_THE_WAY = 'on_the_way';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const FILTER_ACTIVE = 'active';

    static function getStatusLabel($Status, $OrderType = '')
    {
        return [
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_PREPARING => 'Preparing',
            self::STATUS_ON_THE_WAY => $OrderType == 'pickup' ? 'Ready for pickup' : 'On the way',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ][$Status] ?? $Status;
    }

    static function getStatusBadge($Status)
    {
        return [
            self::STATUS_RECEIVED => 'badge-soft-success',
            self::STATUS_PREPARING => 'badge-soft-warning',
            self::STATUS_ON_THE_WAY => 'badge-soft-info',
            self::STATUS_COMPLETED => 'badge-soft-success',
            self::STATUS_CANCELLED => 'badge-soft-secondary',
        ][$Status] ?? 'badge-soft-secondary';
    }

    /** The step after $Status in the kitchen flow, or null when finished */
    static function getNextStatus($Status)
    {
        return [
            self::STATUS_RECEIVED => self::STATUS_PREPARING,
            self::STATUS_PREPARING => self::STATUS_ON_THE_WAY,
            self::STATUS_ON_THE_WAY => self::STATUS_COMPLETED,
        ][$Status] ?? null;
    }

    static function getSettings()
    {
        return require __DIR__ . '/../../restaurant_bridge/config.php';
    }

    /* ************************* Reading ************************* */
    /** Open in the kitchen, or completed but not invoiced yet */
    const WHERE_ACTIVE = "status IN ('received', 'preparing', 'on_the_way') OR (status = 'completed' AND erp_sales_id IS NULL)";

    /** Orders of the branch the restaurant pages are on (each branch gets its own website orders) */
    private static function branchWhere()
    {
        return "branch_id = " . escape(RestaurantBranch::getCurrentId(), 'int');
    }

    /** $Type: '' (all), 'delivery' or 'pickup' */
    static function getOrders($Filter, $Type = '')
    {
        $Where = $Filter == self::FILTER_ACTIVE ? self::WHERE_ACTIVE : (empty($Filter) ? "1" : "status = " . escape($Filter));
        $TypeWhere = in_array($Type, ['delivery', 'pickup'], true) ? " AND order_type = " . escape($Type) : "";

        return DBQM::query("SELECT * FROM rs_orders WHERE ($Where)$TypeWhere AND " . self::branchWhere() . " ORDER BY id DESC LIMIT 200");
    }

    /**
     * Fingerprint of this branch's rs_orders for live updates on Web Orders: changes when an order is added or any order changes.
     * latestId = the newest order, so the page can tell a new order from a status change.
     */
    static function getChangeState()
    {
        $Row = DBQM::fetch_assoc(DBQM::query("SELECT COUNT(*) AS n, COALESCE(MAX(id), 0) AS latest_id, COALESCE(MAX(updated_at), '') AS updated FROM rs_orders WHERE " . self::branchWhere()));

        return ['signature' => $Row['n'] . '-' . $Row['latest_id'] . '-' . $Row['updated'], 'latestId' => (int)$Row['latest_id']];
    }

    static function countActive()
    {
        return (int)DBQM::result(DBQM::query("SELECT COUNT(*) FROM rs_orders WHERE (" . self::WHERE_ACTIVE . ") AND " . self::branchWhere()), 0);
    }

    /** Order counts for the filter chips: [filter key => count] */
    static function countByFilter()
    {
        $Counts = [self::FILTER_ACTIVE => self::countActive(), '' => 0];
        foreach ([self::STATUS_RECEIVED, self::STATUS_PREPARING, self::STATUS_ON_THE_WAY, self::STATUS_COMPLETED, self::STATUS_CANCELLED] as $Status)
            $Counts[$Status] = 0;

        $Select = DBQM::query("SELECT status, COUNT(*) AS n FROM rs_orders WHERE " . self::branchWhere() . " GROUP BY status");
        while ($Row = DBQM::fetch_assoc($Select)) {
            $Counts[$Row['status']] = (int)$Row['n'];
            $Counts[''] += (int)$Row['n'];
        }

        return $Counts;
    }

    static function getOrder($OrderId)
    {
        return DBQM::fetch_assoc(DBQM::query("SELECT * FROM rs_orders WHERE id = " . escape($OrderId, 'int')));
    }

    static function getItems($OrderId)
    {
        $Items = [];
        $Select = DBQM::query("SELECT * FROM rs_order_items WHERE order_id = " . escape($OrderId, 'int') . " ORDER BY id");
        while ($Row = DBQM::fetch_assoc($Select))
            $Items[] = $Row;

        return $Items;
    }

    /** Modifiers chosen on one order line (name and price as ordered). Their price is already in the line's unit_price. */
    static function getModifiers($OrderItemId)
    {
        $Modifiers = [];
        $Select = DBQM::query("SELECT name, price FROM rs_order_item_modifiers WHERE order_item_id = " . escape($OrderItemId, 'int') . " ORDER BY id");
        while ($Row = DBQM::fetch_assoc($Select))
            $Modifiers[] = $Row;

        return $Modifiers;
    }

    /* ************************* Status ************************* */
    /**
     * Invoiced orders can't change; "completed" posts the Sales Invoice (again, if an earlier post failed).
     *
     * @return array [bool success, string message]
     */
    static function setStatus($OrderId, $Status)
    {
        $Order = self::getOrder($OrderId);
        if (!$Order)
            return [false, "Order not found."];

        if (!in_array($Status, [self::STATUS_RECEIVED, self::STATUS_PREPARING, self::STATUS_ON_THE_WAY, self::STATUS_COMPLETED, self::STATUS_CANCELLED]))
            return [false, "Invalid status."];

        if ($Order['erp_sales_id'] && $Status != self::STATUS_COMPLETED)
            return [false, $Order['order_code'] . " is already invoiced (Sales Invoice # " . $Order['erp_voucher_number'] . "). Make a sales return instead."];

        DBQM::query("UPDATE rs_orders SET status = " . escape($Status) . " WHERE id = " . escape($OrderId, 'int'));

        if ($Status != self::STATUS_COMPLETED || $Order['erp_sales_id'])
            return [true, $Order['order_code'] . " marked " . self::getStatusLabel($Status, $Order['order_type']) . "."];

        $Result = self::postToSales(self::toSalesData($Order));

        if (isset($Result['salesId']))
        {
            DBQM::query("UPDATE rs_orders SET erp_sales_id = " . escape($Result['salesId'], 'int') . ", erp_voucher_number = " . escape($Result['voucherNumber'], 'int') . ", erp_error = NULL WHERE id = " . escape($OrderId, 'int'));
            return [true, $Order['order_code'] . " completed. Sales Invoice # " . $Result['voucherNumber'] . " created."];
        }

        DBQM::query("UPDATE rs_orders SET erp_error = " . escape(mb_substr($Result['error'], 0, 255)) . " WHERE id = " . escape($OrderId, 'int'));
        return [false, $Order['order_code'] . " marked completed but the invoice was not created: " . $Result['error']];
    }

    /* ************************* Sales Invoice ************************* */
    static function toSalesData(array $Order)
    {
        $Items = [];
        foreach (self::getItems($Order['id']) as $Item)
            $Items[] = ['stock_id' => $Item['erp_stock_id'], 'attribute_id' => $Item['erp_attribute_id'], 'name' => $Item['item_name'], 'qty' => $Item['qty'], 'price' => $Item['unit_price']];

        return [
            'order_code' => $Order['order_code'],
            // Invoiced and sold from stock of the branch the customer ordered from
            'branch_id' => $Order['branch_id'],
            'name' => $Order['customer_name'],
            'phone' => $Order['customer_phone'],
            'address' => $Order['customer_address'],
            'notes' => $Order['notes'],
            'delivery_fee' => $Order['delivery_fee'],
            'items' => $Items,
        ];
    }

    /**
     * Saves the order as a Sales Invoice with the ERP's own Sales class, all or nothing.
     * Linked dishes (stock_id) are sold from stock; the rest go as non-inventory lines.
     *
     * @param array $Order order_code, name, phone, address, notes, delivery_fee, items[stock_id, attribute_id, name, qty, price],
     *                     optional customer_account_id / branch_id (default: restaurant_bridge/config.php)
     * @return array ['salesId', 'distinctId', 'voucherNumber', 'existing'?] or ['error', 'details']
     */
    static function postToSales(array $Order)
    {
        $Settings = self::getSettings();
        $CustomerAccountId = $Order['customer_account_id'] ?? $Settings['customer_account_id'];
        $BranchId = $Order['branch_id'] ?? $Settings['branch_id'];
        $Description = 'Website order ' . $Order['order_code'];

        /* ************* Already posted? (retry or double click) ************* */
        $Select = DBQM::query("SELECT s.id, d.id AS distinct_id, d.voucher_number FROM sl_sales s, distinct_id d
                               WHERE s.distinct_id = d.id AND d.is_deleted <> 'Y' AND d.voucher_type_id = " . escape(VoucherType::SIV, 'int') . "
                               AND s.description = " . escape($Description));
        if ($Row = DBQM::fetch_assoc($Select))
            return ['salesId' => (int)$Row['id'], 'distinctId' => (int)$Row['distinct_id'], 'voucherNumber' => (int)$Row['voucher_number'], 'existing' => true];

        /* ************* The Sales class reads the voucher form from the request ************* */
        $OriginalPost = $_POST;
        $OriginalGet = $_GET;
        $OriginalErrors = $_SESSION['__error'] ?? [];
        $_SESSION['__error'] = [];

        $_GET['voucher_type_id'] = VoucherType::SIV;
        $_POST = [
            'voucher_id' => '',
            'voucher_type_id' => VoucherType::SIV,
            'voucher_date' => date('m/d/Y'),
            'cost_center_id' => '',
            '_project_id' => '',
            'submit' => 'Y',
            // Makes the payload unique per order: BasicVoucher drops identical payloads within 10 seconds as duplicates
            '__restaurant_order_code' => $Order['order_code'],
        ];

        $Connection = DatabaseHelper::getConnection();
        $Connection->begin_transaction();

        $oSales = new Sales();
        $oSales->initForSave();

        $oSales->saveMainTableData([
            '',                                  // sales_order_id
            $CustomerAccountId,
            date('Y-m-d'),                       // due_date
            date('Y-m-d'),                       // delivery_date
            $Description,
            $BranchId,
            Sales::TYPE_INVOICE,
            0, 0, 0,                             // invoice discount amount / % / calc
            0, 0,                                // gst % / calc
            '',                                  // sales_man_id
            $Order['name'] ?? '',
            $Order['phone'] ?? '',
            (float)($Order['delivery_fee'] ?? 0) // transport_amount
        ]);

        if ($oSales->isVoucherCreatedSuccessfully())
        {
            foreach ($Order['items'] as $Item)
            {
                $Qty = (int)$Item['qty'];
                $Price = (float)$Item['price'];

                if (!empty($Item['stock_id']))
                    $oSales->saveDataTableData([(int)$Item['stock_id'], empty($Item['attribute_id']) ? null : (int)$Item['attribute_id'], '', $Qty, 0, $Price, 0, 0], Sales::IND_DATA);
                else
                    $oSales->saveDataTableData([$Item['name'], $Qty, 0, $Price], Sales::IND_NON_INVENTORY);
            }

            $oSales->saveDataTableData([
                $CustomerAccountId,
                $Order['address'] ?? '',
                $Order['name'] ?? '',
                $Order['phone'] ?? '',
                $Order['notes'] ?? '',
            ], Sales::IND_DELIVERY);

            $oSales->performTransactions();
        }

        $Errors = $_SESSION['__error'] ?? [];
        $isSaved = $oSales->isVoucherCreatedSuccessfully() && empty($Errors);

        if ($isSaved)
            $Connection->commit();
        else
            $Connection->rollback();

        $_POST = $OriginalPost;
        $_GET = $OriginalGet;
        $_SESSION['__error'] = $OriginalErrors;

        if (!$isSaved)
            return ['error' => self::describeError($Errors), 'details' => $Errors];

        return ['salesId' => (int)$oSales->getVoucherId(), 'distinctId' => (int)$oSales->getDistinctId(), 'voucherNumber' => (int)$oSales->getVoucherNumber()];
    }

    private static function describeError(array $Errors)
    {
        foreach ($Errors as $Error)
        {
            // "Qty not adjusted properly, ... Stock Name: Zinger Burger - 1 Qty unadjusted!"
            if (preg_match('/Stock Name: (.+) - ([\d.]+) Qty unadjusted/', $Error, $Match))
                return "Not enough stock of {$Match[1]} in the order's branch ({$Match[2]} short).";
        }

        return $Errors ? strip_tags(reset($Errors)) : 'Could not save sales invoice.';
    }
}
