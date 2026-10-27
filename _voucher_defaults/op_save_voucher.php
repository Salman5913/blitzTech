<?php
include_once("../connection/localhost.php");

/** @var BasicVoucher $oBasicVoucher */
$oBasicVoucher = new $_POST['voucher_class']();

if (!($oBasicVoucher instanceof DefaultVoucherForm))
    ErrorHandler::showOnErrorPage("Class doesn't implement DefaultVoucherForm");

if ($oBasicVoucher->isValidFormSubmission())
{
    $oBasicVoucher->initForSave();
    $oBasicVoucherForm = new BasicVoucherForm($oBasicVoucher);

    $UploadPath = (new UploadPath(get_class($oBasicVoucher)))->getPath();

    /* *********** Main Table Data Entry ************ */
    $Columns = $oBasicVoucherForm->getMainTableColumnsWithDataTypes();
    $MainTableData = [];
    $StockColumnNames = array();

    foreach ($Columns as $ColumnName => $v)
    {
        if (str_contains($ColumnName, "stock_id")) { $StockColumnNames[] = $ColumnName; }
        elseif (str_contains($ColumnName, "attribute_id")) { $StockColumnNames[] = $ColumnName; }
    }

    list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_POST[$StockColumnNames[0]]);
    foreach ($Columns as $Column => $DataType)
    {
        if (isset($_POST['_old_'.$Column]))
            $MainTableData[] = $_POST['_old_'.$Column]; // to get value of existing file field (via file upload)
        else
        {
            $InputValue = $_POST[$Column];
            if (str_contains($DataType, "date"))
                $InputValue = DateEncode($InputValue);
            elseif ($DataType == 'time')
                $InputValue = DateEncode($InputValue, "H:i:s");

            $oFormFieldType = $oBasicVoucher->getVoucherFormUtils()->getColumnDisplayType($Column);
            if ($oFormFieldType instanceof FormFieldTypeMultiSelectFromAutocomplete and is_array($InputValue))
            {
                $InputValue = implode(";", $InputValue).";";
            }

            if ($Column == $StockColumnNames[0])
                $InputValue = $StockId;

            if ($Column == $StockColumnNames[1])
                $InputValue = $AttributeId;

            $MainTableData[] = $InputValue;
        }
    }

    $oBasicVoucher->saveMainTableData($MainTableData);

    foreach ($_FILES['main_file']['name'] as $ColumnName => $Name)
    {
        $oUpload = new Upload();
        $FileName = $oUpload->uploadArrayFileSingle($Name, $_FILES['main_file']['tmp_name'][$ColumnName], $UploadPath, true);

        if (!empty($FileName))
            $oBasicVoucher->updateFileNameMainTable($ColumnName, $FileName);
    }
    /* *********** Main Table Data Entry ************ */


    if ($oBasicVoucher->isVoucherCreatedSuccessfully())
    {
        $DataTables = $oBasicVoucher->getDataTableName();

        foreach ($DataTables as $Key => $TableName)
        {
            $Columns = $oBasicVoucherForm->getDataTableColumns($TableName);
            $FirstColumnName = $oBasicVoucherForm->getDataTableFirstColumn($TableName);
            $CountRows = count((array)$_POST['data'][$Key][$FirstColumnName]);

            for($i=0; $i<$CountRows; $i++)
            {
                foreach ($oBasicVoucherForm->getVoucherFormUtils()->getStockColumns($Key) as $StockColumn => $AttributeColumn)
                {
                    list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($_POST['data'][$Key][$StockColumn][$i]);

                    $_POST['data'][$Key][$StockColumn][$i] = $StockId;
                    $_POST['data'][$Key][$AttributeColumn][$i] = $AttributeId;
                }

                $Data = [];

                /* Check if row is empty by checking the first column value */
                $FirstColumnValue = array_values($_POST['data'][$Key][$FirstColumnName])[$i];
                if (empty($FirstColumnValue))
                    continue;

                foreach ($Columns as $Column => $DataType)
                {
                    $InputValue = $_POST['data'][$Key][$Column][$i];
                    if (str_contains($DataType, "date"))
                        $InputValue = DateEncode($InputValue);
                    elseif ($DataType == 'time')
                        $InputValue = DateEncode($InputValue, "H:i:s");

                    $oFormFieldType = $oBasicVoucher->getVoucherFormUtils()->getColumnDisplayType($Column);
                    if ($oFormFieldType instanceof FormFieldTypeMultiSelectFromAutocomplete and is_array($InputValue))
                    {
                        $InputValue = implode(";", $InputValue).";";
                    }
                    elseif ($oFormFieldType instanceof FormFieldTypeFile)
                    {
                        $InputValue = $_POST['_old'][$Key][$Column][$i];

                        if (!empty($_FILES['data_file']['name'][$Key][$Column][$i]))
                        {
                            $oUpload = new Upload();
                            $FileName = $oUpload->uploadArrayFileSingle(
                                $_FILES['data_file']['name'][$Key][$Column][$i],
                                $_FILES['data_file']['tmp_name'][$Key][$Column][$i],
                                $UploadPath,
                                true
                            );

                            if (!empty($FileName))
                            {
                                $InputValue = $FileName;
                            }
                        }
                    }

                    $Data[] = $InputValue;
                }

                $oBasicVoucher->saveDataTableData($Data, $Key);
            }
        }

        if ($oBasicVoucher instanceof BasicVoucherAdditionalUtils)
            $oBasicVoucher->performOtherActionBeforeTransaction();

        $oBasicVoucher->performTransactions();
    }

    if (!isset($_POST['_ajax_voucher']))
    {
        $oBasicVoucher->notifyStatusMessage();
        $oBasicVoucher->redirectToStatus('', $oBasicVoucher->getVoucherType());
    }
    else
    {
        echo json_encode(["id" => $oBasicVoucher->getVoucherId(), "name" => Autocomplete::getAjaxCreatedNewlyItem($_POST['_ac_type'], $oBasicVoucher->getVoucherId())]);
    }
}
else
    ErrorHandler::UnauthorizedAccess();