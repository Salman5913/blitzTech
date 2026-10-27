<?php

/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 012, 1/12/2015
 * Time: 1:38 PM
 */

class SMSManager
{
    private $CompanyReceiver = [];
    private $AllowedCountryCodes = [];


    const DEFAULT_COUNTRY_CODE = '+92';
    private $COMPANY_NAME = 'AB Electronics';


    function __construct() {
        $this->CompanyReceiver[] = '+923219225919';

        $this->AllowedCountryCodes[] = self::DEFAULT_COUNTRY_CODE;
    }

    function sendToCompany($Message) {
        $this->send($this->CompanyReceiver, $Message);
    }

    /**
     * @param int|array $AccountId
     * @param $Message
     */
    function sendToUser($AccountId, $Message)
    {
        if (!is_array($AccountId))
        {
            $Number = ChartOfAccount::getMobileNumber($AccountId);

            if (!empty($Number) and strlen($Number) > 5)
                $this->send(array($Number), Settings::getCompanyName() . ": " . $Message);
        }
        else
        {
            $Receivers = [];
            foreach($AccountId as $Id)
            {
                $Number = ChartOfAccount::getMobileNumber($Id);

                if (!empty($Number) and strlen($Number) > 5)
                    $Receivers[] = $Number;
            }

            if (count($Receivers) > 0)
                $this->send($Receivers, Settings::getCompanyName() . ": " . $Message);
        }
    }

	/**
	 * @param int $Number
	 * @param $Message
	 */
	function sendToNumberDirectly($Number, $Message)
	{
		if (!empty($Number) and strlen($Number) > 5)
			$this->send(array($Number), Settings::getCompanyName() . ": " . $Message);
	}


    function cleanUpContactNumber($Number)
    {
        if ($Number[0] == 0 and $Number[1] == 0)
            $Number = "+".substr($Number, 2);

        $Number = ltrim($Number, 0);

        if ($Number[0] != '+')
            $Number = self::DEFAULT_COUNTRY_CODE.$Number;

        return $Number;
    }

    function send(array $Receiver, $Message)
    {
        $Fields = array();
        $Count=0;
        foreach ($Receiver as $Number) {
            $Number = $this->cleanUpContactNumber($Number);

            if ($this->isPhoneNumberAllowed($Number))
                $Fields['receiver['.$Count++.']'] = $Number;
        }
        $Fields['message'] = $Message;
        $Fields['send'] = 'true';
        $Fields['company_name'] = $this->COMPANY_NAME;

        $URL = 'http://smsapi.3dm-sols.com/send_notification.php';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $Fields);

        // Execute post
        /*$result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }*/

        // Close connection
        //curl_close($ch);
        return $result;
    }

    /**
     * @param $Number
     * @return bool
     */
    private function isPhoneNumberAllowed($Number)
    {
        $isDefaultCountryCode = false;

        foreach($this->AllowedCountryCodes as $Code)
            if (str_starts_with($Number, $Code))
                $isDefaultCountryCode = true;

        return true;
    }
}