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

class ImportAccounts
{
    function AccountOpening()
    {
        $Reader = new SpreadsheetReader("../import/accounts-ab.xlsx");
        $sheetCount = count($Reader->sheets());
        $StockReader = $Reader->ChangeSheet(0);

        $_POST['voucher_type_id'] = VoucherType::OPA;

        $oChartOfAccount = new ChartOfAccount();
        $oVoucher = new Vouchers();
        $oVoucher->initForSave();

        $i=0;
        foreach ($Reader as $Row)
        {
            $ColCount = 0;
            $Category = $Row[$ColCount++];
            $AccountName = $Row[$ColCount++];
            $isEditable = $Row[$ColCount++];
            $SIA = $Row[$ColCount++];
            $Balance = $Row[$ColCount++];

            $CategoryId = ChartOfAccountCategory::getCOAIdByName($Category);

            $Select = DBQM::query("SELECT id FROM acc_accounts WHERE name = ".escape($AccountName));
            $AccountId = DBQM::result($Select, 0);

            /*if(empty($oChartOfAccount->getAccountIdByName($AccountName)))
            {
                DBQM::query("INSERT INTO acc_accounts VALUES (
                    0,
                    ".escape($CategoryId).",
                    ".escape($AccountName).",
                    ".escape($SIA).",
                    ".escape($isEditable).",
                    ".escape(Company::getLoggedInCompanyId())."
                )");
            }*/

            if ($i > 800 && $i <= 1000)
            {
                if($Balance > 0 || $Balance < 0)
                {
                    echo $AccountName."<br>";
                    $oVoucher->saveMainTableData([
                        getConstantAccount()->getEquityInvestmentAccount(),
                        'Opening Accounts'
                    ]);

                    if($oVoucher->isVoucherCreatedSuccessfully())
                    {
                        $oVoucher->saveDataTableData([
                            $AccountId,
                            $Balance,
                            0,
                            'Balance Carry Forward From 2021',
                            '',
                            '0000-00-00'
                        ]);
                    }
                }
            }

            $i++;
        }
    }

    function Stock()
    {
        $Reader = new SpreadsheetReader("../import/Stock.xlsx");
        $sheetCount = count($Reader->sheets());
        $StockReader = $Reader->ChangeSheet(0);


        $oStock = new Stock();
        $oStock->initForSave();

        $i=0;
        foreach ($Reader as $Row)
        {
            $ColCount = 0;
            $StockName = $Row[$ColCount++];
            $Category = $Row[$ColCount++];
            $Brand = $Row[$ColCount++];
            $i++;

            $CategoryId = StockCategory::getStockIdByName($Category);

            DBQM::query("INSERT INTO st_stock VALUES(
                        0,
                        ".escape(Company::getLoggedInCompanyId()).",
                        ".escape($CategoryId).",
                        ".escape($StockName).",
                        ".escape($StockName).",
                        ".escape($Brand).",
                        10,
                        0,
                        '',
                        'Y'
            )");
        }
    }

    function openingStock()
    {
        $Reader = new SpreadsheetReader("../import/opening_stock.xlsx");
        $sheetCount = count($Reader->sheets());
        $StockReader = $Reader->ChangeSheet(0);

        $oStock = new Stock();
        $oBranch = new Branch();
        $oPurchase = new Purchase();

        $_POST['voucher_type_id'] = VoucherType::OPS;


        $BranchId = $oBranch->getBranchIdByName('SITE');

        $oPurchase->initForSave();
        $oPurchase->saveMainTableData([
            null,
            null,
            null,
            $BranchId,
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
            $ColCount = 0;
            $StockName = $Row[$ColCount++];
            $BranchName= $Row[$ColCount++];
            $Quantity = $Row[$ColCount++];
            $CostPrice = $Row[$ColCount++];

            $StockId = $oStock->getIdByName($StockName);

            if ($oPurchase->isVoucherCreatedSuccessfully())
            {
                if($Quantity > 0 && $BranchName == 'SITE')
                {
                    echo $StockName."<br>";

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
                    ]);
                }
            }

            $i++;
        }
    }

    function updateLedgerEntries()
    {
        $Select = DBQM::query("select sl.*, did.voucher_id from st_stock_ledger sl, distinct_id did where sl.distinct_id = did.id and sl.batch_id not in (select id from st_stock_batch) and sl.voucher_type_id in (2, 27, 28, 29, 15) group by sl.voucher_type_id, sl.voucher_number");

        while ($Row = DBQM::fetch_assoc($Select))
        {
            $oSales = new Sales();

            $_POST['voucher_id'] = $Row['voucher_id'];
            $_POST['voucher_type_id'] = $Row['voucher_type_id'];
            $_POST['voucher_date'] = DateDecode($Row['date_time']);

            $oSales->setDeleteDataUponSave(false);
            $oSales->initForSave();

            $oSales->performTransactions();

            echo $_POST['voucher_id'];
        }
    }

    function updateBatchCost()
    {
        $SelectBatch = DBQM::query("SELECT * FROM st_stock_batch");
        while ($Row = DBQM::fetch_assoc($SelectBatch))
        {
           // DBQM::query("UPDATE st_stock_ledger SET ")
        }
    }
}

$oImportAccounts = new ImportAccounts();
$oImportAccounts->updateLedgerEntries();