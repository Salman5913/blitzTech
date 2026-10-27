<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 014, 1/14/2015
 * Time: 4:56 PM
 */
if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("connection/localhost.php");

include_once('../vendor/php-excel-reader/excel_reader2.php');
include_once('../vendor/SpreadsheetReader.php');

class Import
{
    function Stock()
    {
        $Reader = new SpreadsheetReader("../import/stock.xlsx");
        $sheetCount = count($Reader->sheets());
        $StockReader = $Reader->ChangeSheet(0);

        $oStock = new Stock();
        $oStock->initForSave();

        $i=1;
        foreach ($Reader as $Row)
        {
            if($i > 1){
                $ColCount = 0;
                $StockName = $Row[$ColCount++];
                $Quantity = $Row[$ColCount++];
                $CostPrice = $Row[$ColCount++];
                $SellingPrice = $Row[$ColCount++];

                $CategoryId = 1; //StockCategory::getStockIdByName($Category);

                echo $i.' - '.$StockName.'<br>';


                DBQM::query("INSERT INTO st_stock VALUES (
                            0,
                            ".escape(Company::getLoggedInCompanyId()).",
                            ".escape($CategoryId).",
                            ".escape($StockName).",
                            ".escape($StockName).",
                            '',
                            '',
                            '',
                            ".escape($SellingPrice).",
                            'Y',
                            ''
                )");
            }
            $i++;
        }
    }

    function openingStock($FileName)
    {
        $Reader = new SpreadsheetReader("../import/".$FileName);
        $sheetCount = count($Reader->sheets());
        $StockReader = $Reader->ChangeSheet(0);

        $oStock = new Stock();
        $oBranch = new Branch();
        $oPurchase = new Purchase();

        $_POST['voucher_type_id'] = VoucherType::OPS;

        $oPurchase->initForSave();

        $oPurchase->saveMainTableData([
                null,
                null,
                null,
                2,
                "0000-00-00",
                Settings::getOpeningDateTime(),
                'I',
                'Stock Opening',
                0,
                0,
                0,
                0,
                0
        ]);

        $i=0;
        foreach ($Reader as $Row)
        {
            if($i > 1)
            {
                $ColCount = 0;
                $StockName = $Row[$ColCount++];
                $Quantity = $Row[$ColCount++];
                $CostPrice = $Row[$ColCount++];

                $StockId = $oStock->getIdByName($StockName);
                if($StockId > 0)
                {
                    if ($oPurchase->isVoucherCreatedSuccessfully())
                    {
                        if($Quantity > 0)
                        {
                            $oPurchase->saveDataTableData([
                                $StockId,
                                null,
                                '',
                                null,
                                $Quantity,
                                0,
                                $CostPrice,
                                0,
                                0
                            ], Purchase::IND_DATA);
                        }
                    }
                }
            }
            $i++;
        }
    }
}
$oImport = new Import();
$oImport->openingStock('stock_opening_7.xlsx');