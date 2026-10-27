<?php
include_once("../connection/localhost.php");

$oSales = new Sales();

if ($oSales->isValidFormSubmission())
{
    $oSales->initForSave();

    $oSales->saveMainTableData([
        $_POST['sales_order_id'],
        $_POST['customer_id'],
        DateEncode($_POST['due_date']),
        DateEncode($_POST['delivery_date']),
        $_POST['description'],
        $_POST['branch_id'],
        $_POST['invoice_type'],
        $_POST['invoice_discount_amount'],
        $_POST['invoice_discount_percentage'],
        $_POST['calc_discount'],
        $_POST['gst_percentage'],
        $_POST['calc_gst'],
        $_POST['sales_man_id'],
        $_POST['name'],
        $_POST['mobile_number'],
        $_POST['transport_amount']
    ]);


    if ($oSales->isVoucherCreatedSuccessfully())
    {
        /* ************************* Data Rows Entries ***************************** */
        $CountDataRows = count($_POST['stock_id']);
        for($i=0; $i<$CountDataRows; $i++)
        {
            if ($_POST['stock_id'][$i] > 0 OR !empty($_POST['item_detail'][$i]))
            {
                list ($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_POST['stock_id'][$i]);
                $oSales->saveDataTableData([
                    $StockId,
                    $AttributeId,
                    $_POST['item_detail'][$i],
                    $_POST['qty'][$i],
                    $_POST['qty_return'][$i],
                    $_POST['selling_price'][$i],
                    $_POST['discount_percentage'][$i],
                    0
                ], Sales::IND_DATA);
            }
        }
        /* ************************* Data rows Entries ***************************** */


        /* ************************* Data Rows Entries ***************************** */
        $CountDataRows = count($_POST['item_description']);
        for($i=0; $i<$CountDataRows; $i++)
        {
            if (!empty($_POST['item_description'][$i]))
            {
                $oSales->saveDataTableData([
                    $_POST['item_description'][$i],
                    $_POST['ni_qty'][$i],
                    $_POST['ni_qty_return'][$i],
                    $_POST['ni_selling_price'][$i]
                ], Sales::IND_NON_INVENTORY);
            }
        }
        /* ************************* Data rows Entries ***************************** */


        /* ************************* Delivery Entry ***************************** */
        $oSales->saveDataTableData([
            $_POST['customer_id'],
            $_POST['delivery_address'],
            $_POST['delivery_name'],
            $_POST['delivery_phone'],
            $_POST['delivery_desc'],
        ], Sales::IND_DELIVERY);
        /* ************************* Expenses Entries ***************************** */


        /* ************************* Payment Entries ***************************** */

            $CountPaymentDataRows = count($_POST['cash_bank_id']);
            for($i=0; $i<$CountPaymentDataRows; $i++)
            {
                if ($_POST['cash_bank_id'][$i] > 0)
                {
                    $oSales->saveDataTableData([
                        $_POST['payment_type'][$i],
                        $_POST['cash_bank_id'][$i],
                        $_POST['cheque_number'][$i],
                        DateEncode($_POST['cheque_date'][$i]),
                        abs($_POST['payment_amount'][$i]),
                        $_POST['payment_desc'][$i]
                    ], Sales::IND_PAYMENT);
                }
            }
        /* ************************* Payment Entries ***************************** */


        /* ************************* Ledger Entries ***************************** */
        $oSales->performTransactions();
        /* ************************* Ledger Entries ***************************** */
    }

    $oSales->notifyStatusMessage();

    if (!$oSales->isDelete() AND !$oSales->isDraft() AND !$oSales->isClose())
    {
        PopupWindowManager::addURL('print_voucher.php?id='.$oSales->getVoucherId().'&voucher_type_id='.$oSales->getVoucherType());

        if ($_POST['print_dc'] == 'Y')
            PopupWindowManager::addURL('print_dc.php?id='.$oSales->getVoucherId().'&voucher_type_id='.$oSales->getVoucherType());
    }

    $oSales->redirectToStatus(get_class($oSales), $oSales->getVoucherType());
}
else
    ErrorHandler::UnauthorizedAccess();