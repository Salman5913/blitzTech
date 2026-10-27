<?php


class FBR
{
    const POS_ID = 100;
    const USIN_NO = 0;
    private $InvoiceVariables = [];
    private $TotalQty = 0;
    private $InvoiceType;
    private $InvoiceTypeItem;
    private $TotalSales = 0;
    private $TotalTax = 0;
    private $TotalDiscount = 0;

    public function __construct($IsEdit=false, $IsReturn=false, $Is3rdSchedule=false)
    {
        if (!$IsEdit)
            $this->InvoiceType = $IsReturn ? 3 : 1;
        else
            $this->InvoiceType = $IsReturn ? 3 : 1;

        if ($Is3rdSchedule)
            $this->InvoiceTypeItem = $IsReturn ? 12 : 11;
        else
            $this->InvoiceTypeItem = $IsReturn ? 3 : 1;
    }

    function saveInfo($InvoiceNumber, $InvoiceDateTime, $BuyerName='', $BuyerPhoneNumber='', $NTN='', $CNIC='')
    {
        $this->InvoiceVariables = array (
            'InvoiceNumber' => $InvoiceNumber,
            'POSID' => self::POS_ID,
            'USIN' => self::USIN_NO,
            'DateTime' => $InvoiceDateTime,
            'BuyerNTN' => $NTN,
            'BuyerCNIC' => $CNIC,
            'BuyerName' => $BuyerName,
            'BuyerPhoneNumber' => $BuyerPhoneNumber,
            'TotalBillAmount' => $this->TotalSales + $this->TotalTax,
            'TotalSaleValue' => $this->TotalSales,
            'TotalTaxCharged' => $this->TotalTax,
            'Discount' => $this->TotalDiscount,
            'FurtherTax' => 0,
            'PaymentMode' => 1,
            'RefUSIN' => NULL,
            'InvoiceType' => 1
        );
    }

    function addItem($ItemCode, $ItemName, $HSCode, $Qty, $TaxRate, $SaleValueBeforeTax, $Tax, $Discount)
    {
        $this->InvoiceVariables['Items'][] = [
            'ItemCode' => $ItemCode,
            'ItemName' => $ItemName,
            'Quantity' => $Qty,
            'PCTCode' => $HSCode,
            'TaxRate' => $TaxRate,
            'SaleValue' => $SaleValueBeforeTax,
            'TotalAmount' => $SaleValueBeforeTax + $Tax,
            'TaxCharged' => $Tax,
            'Discount' => $Discount,
            'FurtherTax' => 0.0,
            'InvoiceType' => $this->InvoiceTypeItem,
            'RefUSIN' => NULL
        ];

        $this->TotalQty += $Qty;
        $this->TotalSales += $SaleValueBeforeTax;
        $this->TotalTax += $Tax;
        $this->TotalDiscount += $Discount;
    }

    function sendToFBR()
    {
        $this->InvoiceVariables['TotalQuantity'] = $this->TotalQty;

        if ($this->TotalQty == 0)
        {
            ErrorHandler::setError("Invalid Items passed to API via FINAC");
            return false;
        }

        $SandBoxURL = 'https://esp.fbr.gov.pk:8244/FBR/v1/api/Live/PostData';
        $ProductionURL = 'https://gw.fbr.gov.pk/imsp/v1/api/Live/PostData';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $SandBoxURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->InvoiceVariables);

        $Result = curl_exec($ch);
        if ($Result === FALSE) {
            ErrorHandler::setError('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);


        $Result = json_decode($Result, true);
        if (isset($Result['FBRInvoiceNumber']) and isset($Result['Code']) and $Result['Code'] == 100)
            return $Result['FBRInvoiceNumber'];
        else
        {
            if (is_array($Result['Errors']))
            {
                foreach ($Result['Errors'] as $Key => $Error)
                    ErrorHandler::setError($Key." => ".$Error);
            }
            else
                ErrorHandler::setError($Result['Errors']);

            return false;
        }
    }

    static function printQRCode($InvoiceNumber)
    {
        ?>
        <table class="table">
            <tr>
                <td colspan="2" class="text-center">FBR Invoice # <br> <?=$InvoiceNumber?></td>
            </tr>
            <tr>
                <td width="50%" class="text-center"><img src="<?=ProjectHelper::SiteURL()?>assets/images/fbr-logo.jpg" class="img-fluid"></td>
                <td width="50%" class="text-center"><img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=<?=$InvoiceNumber?>&choe=UTF-8" title="Scan" class="img-fluid" /></td>
            </tr>
            <tr>
                <td colspan="2" class="text-center">Verify this invoice through FBR TaxAsaan MobileApp or SMS at 9966 and win exciting prizes in draw.</td>
            </tr>
        </table>
        <?php
    }

    /**
     * {\”FBRInvoiceNumber\”:11000120181112000369,\”Response\”:\”Invoice received successfully\”,\”Code\”:\”100\”}
     */
}