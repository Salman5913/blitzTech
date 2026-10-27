<?php

class Currency
{
    static function getRateByAccountId($AccountId)
    {
        $Select = DBQM::query("
                                    SELECT 
                                         c.conversion_rate 
                                    FROM 
                                         acc_accounts a
                                         LEFT JOIN acc_currency c
                                            ON a.currency_code = c.code
                                    WHERE 
                                         a.id = " . escape($AccountId) . " ");
        return DBQM::result($Select, 0);
    }

    static function getAll()
    {
        return DBQM::query("SELECT * FROM acc_currency");
    }

    static function getRate($FromCurrencyCode, $ToCurrencyCode, $ForcedAPICheck=false)
    {
        if ($FromCurrencyCode == ProjectHelper::DEFAULT_CURRENCY && $ToCurrencyCode == ProjectHelper::DEFAULT_CURRENCY)
            return 1;

        if ($ToCurrencyCode == ProjectHelper::DEFAULT_CURRENCY && !$ForcedAPICheck)
        {
            $Select = DBQM::query("SELECT conversion_rate FROM acc_currency WHERE code = ".escape($FromCurrencyCode));
            return DBQM::result($Select, 0);
        }
        else
        {
            $Rate = self::getConversionRateFromAPI($FromCurrencyCode, $ToCurrencyCode);

            if ($Rate)
                return $Rate;
        }

        return 1;
    }

    function updateRates()
    {
        $Rates = $this->getPKRBaseRatesFromAPI();
        foreach ($Rates as $CurrencyCode => $Rate)
        {
            /* Bank rate is 10% lower */
            $BankRate = $CurrencyCode != ProjectHelper::DEFAULT_CURRENCY ? $Rate * 0.9 : 1;

            DBQM::query("UPDATE acc_currency SET conversion_rate = ".escape($Rate, 'double').", bank_conversion_rate = ".escape($BankRate)." WHERE code = ".escape($CurrencyCode));
        }
    }

    function getPKRBaseRatesFromAPI()
    {
        $Select = DBQM::query("SELECT GROUP_CONCAT(code) AS codes FROM acc_currency WHERE code <> ".escape(ProjectHelper::DEFAULT_CURRENCY));
        $Codes = DBQM::result($Select, 0);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.apilayer.com/exchangerates_data/latest?symbols=".urlencode($Codes)."&base=".ProjectHelper::DEFAULT_CURRENCY,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: text/plain",
                "apikey: iSYzKpSgbxtIe4fNrdNCpaeQeCoLZTNI"
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET"
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $Response = json_decode($response, true);

        $Rates = [];
        foreach ($Response['rates'] as $CurrencyCode => $Rate)
        {
            $Rates[$CurrencyCode] = 1/$Rate;
        }

        return $Rates;
    }

    static function getConversionRateFromAPI($FromCode, $ToCode)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.apilayer.com/exchangerates_data/convert?to=".$ToCode."&from=".$FromCode."&amount=1",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: text/plain",
                "apikey: iSYzKpSgbxtIe4fNrdNCpaeQeCoLZTNI"
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET"
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $Response = json_decode($response, true);
        return $Response['success'] ?? $Response['result'];
    }
}