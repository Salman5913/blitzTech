<?php
/**
 * Order Invoice Voucher (POS) save.
 * Same behaviour as SIV: saved through Sales class into sl_sales / sl_sales_data.
 * POS screen posts with ajax=Y and gets JSON back (receipt popup), delete posts normally and gets redirected.
 */
include_once("../connection/localhost.php");

$isAjax = ($_POST['ajax'] ?? '') == 'Y';
$_POST['voucher_type_id'] = VoucherType::OIV;

function posResponse(array $Response)
{
    header('Content-Type: application/json');
    echo json_encode($Response);
    exit;
}

$oSales = new Sales();

if (!$oSales->isValidFormSubmission())
    $isAjax ? posResponse(['ok' => false, 'error' => 'Invalid request']) : ErrorHandler::UnauthorizedAccess();

if (!SecurityShell::isVoucherAccess(VoucherType::OIV) or (!empty($_POST['voucher_id']) and !SecurityShell::isVoucherEditAccess(VoucherType::OIV)))
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
    Utils::redirect(VoucherType::getLink(VoucherType::OIV));
}

if (!$isSaved)
    posResponse(['ok' => false, 'error' => $SaveErrors ? strip_tags(implode("<br>", $SaveErrors)) : 'Unable to save Order Invoice']);

$VoucherId = $oSales->getVoucherId();

posResponse([
    'ok' => true,
    'id' => (int) $VoucherId,
    'voucher_number' => $oSales->getVoucherNumber(),
    'voucher_name' => VoucherType::getCode(VoucherType::OIV)."-".$oSales->getVoucherNumber(),
    'total' => (float) DBQM::result(DBQM::query("SELECT voucher_total FROM distinct_id WHERE id = ".escape($oSales->getDistinctId(), 'int')), 0),
    'print_url' => ProjectHelper::SiteURL()."pos/print_voucher.php?id=".$VoucherId."&voucher_type_id=".VoucherType::OIV,
    'is_edit' => !empty($_POST['voucher_id']),
    'next_token' => bin2hex(random_bytes(16)),
]);
