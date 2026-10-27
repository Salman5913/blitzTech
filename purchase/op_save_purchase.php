<?php
include_once("../connection/localhost.php");

$oPurchase = new Purchase();
$oBranch = new Branch();

if ($oPurchase->isValidFormSubmission())
{
    $oPurchase->initForSave();

    $DateTime = DateEncode($_POST['voucher_date']);

    $oPurchase->saveMainTableData([
        $_POST['purchase_order_id'],
        $_POST['purchase_requisition_id'],
        $_POST['supplier_id'],
        $_POST['branch_id'],
        DateEncode($_POST['due_date']),
        $_POST['invoice_type'],
        $_POST['description'],
        $_POST['invoice_discount_percentage'],
        $_POST['invoice_discount_amount'],
        $_POST['calc_discount'],
        $_POST['invoice_gst_percentage'],
        $_POST['calc_gst']
    ]);


    if ($oPurchase->isVoucherCreatedSuccessfully())
    {
        /* ************************* Data Rows Entries ***************************** */
        $CountDataRows = count($_POST['stock_id']);
        for($i=0; $i<$CountDataRows; $i++)
        {
            if (!empty($_POST['stock_id'][$i]) OR !empty($_POST['item_detail'][$i]))
            {
                list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_POST['stock_id'][$i]);


                $oPurchase->saveDataTableData([
                    $StockId,
                    $AttributeId,
                    $_POST['item_detail'][$i],
                    NULL,
                    $_POST['qty'][$i],
                    $_POST['qty_return'][$i],
                    $_POST['rate'][$i],
                    $_POST['discount_percentage'][$i],
                    0
                ], Purchase::IND_DATA);
            }
        }
        /* ************************* Data rows Entries ***************************** */



        /* ************************* Payment Entries ***************************** */

        if ($_POST['supplier_id'] == getConstantAccount()->getCashSupplier() && $oPurchase->getVoucherType() != VoucherType::POV)
        {
            $CashBankId = getConstantAccount()->getPettyCashAccount();
            $PaymentAmt = (float) $_POST['total_invoice_amount'];

            $oPurchase->saveDataTableData([
                '',
                $CashBankId,
                '',
                '0000-00-00',
                abs($PaymentAmt),
                ''
            ], Purchase::IND_PAYMENT);
        }

        $oPurchase->performTransactions();
    }

    $oPurchase->notifyStatusMessage();

    if (!$oPurchase->isDelete() && !$oPurchase->isDraft() && !$oPurchase->isClose())
        PopupWindowManager::addURL('print_voucher.php?id='.$oPurchase->getVoucherId().'&voucher_type_id='.$oPurchase->getVoucherType());

    $oPurchase->redirectToStatus(get_class($oPurchase), $oPurchase->getVoucherType());
}
else
    ErrorHandler::UnauthorizedAccess();