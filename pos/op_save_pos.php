<?php
/**
 * Order Invoice (OIV), Dine-in Invoice (DIV, with Table No) and WhatsApp Invoice (WHIV) save - POS screen.
 * Same behaviour as SIV: saved through Sales class into sl_sales / sl_sales_data.
 * POS screen posts with ajax=Y and gets JSON back (receipt popup), delete posts normally and gets redirected.
 */
include_once("../connection/localhost.php");

$isAjax = ($_POST['ajax'] ?? '') == 'Y';
$VoucherTypeId = SalesPOS::getVoucherTypeId($_POST['voucher_type_id'] ?? '');
$_POST['voucher_type_id'] = $VoucherTypeId;
$isDineIn = $VoucherTypeId == VoucherType::DIV;

function posResponse(array $Response)
{
    header('Content-Type: application/json');
    echo json_encode($Response);
    exit;
}

$oSales = new Sales();

if (!$oSales->isValidFormSubmission())
    $isAjax ? posResponse(['ok' => false, 'error' => 'Invalid request']) : ErrorHandler::UnauthorizedAccess();

if (!SecurityShell::isVoucherAccess($VoucherTypeId) or (!empty($_POST['voucher_id']) and !SecurityShell::isVoucherEditAccess($VoucherTypeId)))
    $isAjax ? posResponse(['ok' => false, 'error' => 'You do not have access to save this voucher']) : ErrorHandler::UnauthorizedAccess();

/* ************* Validate cart before touching anything ************* */
$CartRows = [];
$CountDataRows = count((array) ($_POST['stock_id'] ?? []));
for ($i=0; $i<$CountDataRows; $i++)
{
    $Qty = (float) $_POST['qty'][$i];
    if (!empty($_POST['stock_id'][$i]) and $Qty > 0)
        $CartRows[] = [$_POST['stock_id'][$i], $Qty, (float) $_POST['selling_price'][$i]];
}

if (!isset($_POST['btn_delete']))
{
    $Errors = [];
    if (empty($_POST['customer_id']))
        $Errors[] = 'Please select customer';
    if (empty($_POST['branch_id']))
        $Errors[] = 'Please select warehouse';
    if ($isDineIn and trim($_POST['table_no'] ?? '') == '')
        $Errors[] = 'Please enter table no';
    if (empty($CartRows))
        $Errors[] = 'Cart is empty';

    if (!empty($Errors))
        $isAjax ? posResponse(['ok' => false, 'error' => implode("<br>", $Errors)]) : Utils::redirectToReferer();
}
/* ************* Validate cart before touching anything ************* */

$_POST['voucher_date'] = $_POST['voucher_date'] ?: date('m/d/Y');
$DateEncoded = DateEncode($_POST['voucher_date']);

$OldErrors = $_SESSION['__error'] ?? [];
$_SESSION['__error'] = [];

$Connection = DatabaseHelper::getConnection();
$Connection->begin_transaction();

$oSales->initForSave();

$oSales->saveMainTableData([
    '',                         // sales_order_id
    $_POST['customer_id'],
    $DateEncoded,               // due_date
    $DateEncoded,               // delivery_date
    '',                         // description
    $_POST['branch_id'],
    Sales::TYPE_INVOICE,
    0, 0, 0,                    // invoice discount amount / % / calc
    0, 0,                       // gst % / calc
    '',                         // sales_man_id
    '',                         // name
    $_POST['mobile_number'],
    0                           // transport_amount
]);

if ($oSales->isVoucherCreatedSuccessfully())
{
    foreach ($CartRows as list($CombinedStockId, $Qty, $SellingPrice))
    {
        list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($CombinedStockId);

        $oSales->saveDataTableData([
            $StockId,
            $AttributeId,
            '',
            $Qty,
            0,
            $SellingPrice,
            0,
            0
        ], Sales::IND_DATA);
    }

    $oSales->saveDataTableData([
        $_POST['customer_id'],
        '',
        '',
        $_POST['mobile_number'],
        '',
    ], Sales::IND_DELIVERY);

    // POS sale is paid in full on the spot: same amount received in Petty Cash (posted as payment with the invoice)
    $CartTotal = 0;
    foreach ($CartRows as list($CombinedStockId, $Qty, $SellingPrice))
        $CartTotal += $Qty * $SellingPrice;

    if ($CartTotal > 0)
        $oSales->saveDataTableData([
            '',                                         // payment_type
            getConstantAccount()->getPettyCashAccount(),
            '',                                         // cheque_no
            $DateEncoded,                               // cheque_date
            round($CartTotal, 2),
            'Received in Petty Cash - '.SalesPOS::getTitle($VoucherTypeId)
        ], Sales::IND_PAYMENT);

    if ($isDineIn)
        SalesPOS::saveTableNo($oSales->getVoucherId(), $_POST['table_no']);

    $oSales->performTransactions();
}

$SaveErrors = $_SESSION['__error'] ?? [];
$isSaved = ($oSales->isVoucherCreatedSuccessfully() or $oSales->isDuplicateSubmission()) && empty($SaveErrors);

if ($isSaved)
    $Connection->commit();
else
    $Connection->rollback();

$_SESSION['__error'] = array_merge($OldErrors, $isAjax ? [] : $SaveErrors);

if (!$isAjax)
{
    $oSales->notifyStatusMessage();
    Utils::redirect(VoucherType::getLink($VoucherTypeId));
}

if (!$isSaved)
    posResponse(['ok' => false, 'error' => $SaveErrors ? strip_tags(implode("<br>", $SaveErrors)) : 'Unable to save '.SalesPOS::getTitle($VoucherTypeId)]);

$VoucherId = $oSales->getVoucherId();

posResponse([
    'ok' => true,
    'id' => (int) $VoucherId,
    'voucher_number' => $oSales->getVoucherNumber(),
    'voucher_name' => VoucherType::getCode($VoucherTypeId)."-".$oSales->getVoucherNumber(),
    'total' => (float) DBQM::result(DBQM::query("SELECT voucher_total FROM distinct_id WHERE id = ".escape($oSales->getDistinctId(), 'int')), 0),
    'print_url' => ProjectHelper::SiteURL()."pos/print_voucher.php?id=".$VoucherId."&voucher_type_id=".$VoucherTypeId,
    'is_edit' => !empty($_POST['voucher_id']),
    'next_token' => bin2hex(random_bytes(16)),
]);
