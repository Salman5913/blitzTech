<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 018, 11/18/2014
 * Time: 11:39 AM
 */

class Utils
{
    static function getThemeClass()
    {
        $PageURL = $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'];
        $Class = 'blue';

        if (Year::isYearClosed())
            $Class = 'gradient-black';

        // Redesign: one brand colour for every module (no per-section purple/golden/cyan/etc).
        // Only status pages keep their own class.
        elseif (str_contains($PageURL, "success.php"))
            $Class = 'green';
        elseif (str_contains($PageURL, "error.php"))
            $Class = 'red';

        return $Class;
    }

    static function redirectToReferer()
    {
        self::redirect($_SERVER['HTTP_REFERER']);
        exit;
    }

    static function redirect($PageName)
    {
        if (!headers_sent())
            header("Location: ".$PageName);
        else
            echo '<meta http-equiv="refresh" content="0; url='.$PageName.'" />';

        exit;
    }

    static function canEditIfAccessAvailable($isEdit)
    {
        if ($isEdit and !SecurityShell::isAccess("Settings", "Vouchers Edit")) // if edit is open and user have no rights to edit
        {
            ?>
            <script type="text/javascript">
                $(function () {
                    $("form button[type='submit']").remove();
                    $('form').contents().unwrap();
                })
            </script>
            <?
        }
    }

    static function intOnlyAttr() {
        echo " data-type='int' ";
    }

    static function requireAutoComplete() {
        echo " data-chk-id='yes' ";
    }

    static function getRequireAutoComplete() {
        return " data-chk-id='yes' ";
    }

	static function formValidate()
	{
		echo " data-form-validate='true' ";
	}

    /**
     * Requires label associated with this input
     */
    static function isRequired($Return=false) {
        if (!$Return)
            echo ' data-error="Y" ';
        else
            return ' data-error="Y" ';
    }

    static function isRequiredLinkedWithDataColumn($LinkedDataColumnName, $Return=false)
    {
        if (!$Return)
            echo ' data-error-linked-to="'.$LinkedDataColumnName.'" ';
        else
            return ' data-error-linked-to="'.$LinkedDataColumnName.'" ';
    }

    static function format($Number, $Decimal=2, $ShowZeroAsDash=false) {

        if (round($Number, $Decimal) == 0 and $ShowZeroAsDash)
            return "-";

        if ($Number < 0)
            return "(".number_format(round(abs($Number), $Decimal), $Decimal).")";
        else
            return number_format(round(abs($Number), $Decimal), $Decimal);
    }

    static function formatAsThousand($Number)
    {
        if ($Number > 999)
            return self::format(floor($Number / 1000),0) . 'K';
        else
            return self::format($Number);
    }

    static function AjaxLoadedPopup($Title, $URL)
    {
        echo " href='javascript:void(0)' data-type-popup-ajax-load='y' data-type-popup-ajax-load-url='".$URL."' data-type-popup-ajax-load-title='".$Title."'";
    }

    static function AjaxLoadedVoucher($VoucherTypeId, $Id='')
    {
        return " href='".VoucherType::getLink($VoucherTypeId, $Id, true)."' data-ajax-open-voucher='true' ";
    }

    static function GETtoHiddenFields()
    {
        foreach($_GET as $Name => $Value) {
            if (substr($Name, 0, 1) !== '_')
                echo '<input type="hidden" name="'. $Name .'" value="'. $Value .'">';
        }
    }

    static function GETtoURLParams($Ignore='')
    {
        $GET = $_GET;
        unset($GET[$Ignore]);

        return http_build_query($GET);
    }

    static function ArrayToInputFields($Array, $InputName)
    {
        foreach($Array as $Name => $Value) {
            echo '<input type="hidden" name="'. $InputName .'['.$Name.']" value="'. $Value .'">';
        }
    }

	static function getDropDownOptionsFromArray($Array, string|null $SelectedValue='', $isArrayWithKeys=true)
	{
		foreach ($Array as $Key => $Value)
		{
			$Key = $isArrayWithKeys ? $Key : $Value;
			?>
			<option value="<?=$Key?>" <?=$Key == $SelectedValue ? ' selected' : '' ?>><?=$Value?></option>
			<?
		}
	}

    /**
     * @param $MultiDimensionalArray array [Group Name => [group elements]]
     * @param string|null $SelectedValue
     * @param bool $isArrayWithKeys
     * @return void
     */
    static function getDropDownOptionsFromArrayGrouped(array $MultiDimensionalArray, string|null $SelectedValue='', bool $isArrayWithKeys=true)
    {
        foreach ($MultiDimensionalArray as $GroupName => $Array)
        {
            ?>
            <optgroup label="<?=$GroupName?>">
            <?
                foreach ($Array as $Key => $Value)
                {
                    $Key = $isArrayWithKeys ? $Key : $Value;
                    ?>
                    <option value="<?=$Key?>" <?=$Key == $SelectedValue ? ' selected' : '' ?>><?=$Value?></option>
                    <?
                }
            ?>
            </optgroup>
            <?
        }
    }

	/**
	 * @param mysqli_result $Select
	 * @param $KeyColumn
	 * @param $ValueColumn
	 * @param string|array $SelectedValue
	 */
	static function getDropDownOptionsFromSelect($Select, $KeyColumn, $ValueColumn, $SelectedValue=[])
	{
		while($Row = DBQM::fetch_assoc($Select)) {
			$isSelected = false;
			if ( ! is_array( $SelectedValue ) )
			{
				if ( $Row[ $KeyColumn ] == $SelectedValue )
				{
					$isSelected = true;
				}
			}
			else
			{
				if (in_array($Row[$KeyColumn], $SelectedValue))
					$isSelected = true;
			}
			?>
            <option value="<?=$Row[$KeyColumn]?>" <?=$isSelected ? ' selected' : '' ?>><?=$Row[$ValueColumn]?></option>
			<?
		}
	}

	static function roundTo100($Number)
    {
        return round($Number / 100) * 100;
    }

    static function sumInputs($InputSelector, $isInputElement=true, $InputFieldName='')
    {
        echo " data-input-sum='".$InputSelector."' data-input-sum-element-type='".($isInputElement ? 'input' : 'html')."' data-input-element-name='".$InputFieldName."' ";
    }

    static function sumReportColumn($DataSelector)
    {
        echo " data-report-column-sum='".$DataSelector."' ";
    }

    static function calcPercentValue($Total, $DiscountPercentage, $DiscountAmount)
    {
        $Value = 0;

        if ($DiscountPercentage > 0)
            $Value = $Total * ($DiscountPercentage / 100);
        else if ($DiscountAmount > 0)
            $Value = $DiscountAmount;

        return $Value;
    }

    static function getVisitorIPAddress()
    {
        $IPAddress = $_SERVER["REMOTE_ADDR"];
        if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
            $IPAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
            $IPAddress = $_SERVER['HTTP_CLIENT_IP'];
        if (filter_var(@$_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP))
            $IPAddress = $_SERVER['HTTP_CF_CONNECTING_IP'];


        return $IPAddress;
    }

    static function getPhoneNumber($MobileNumber)
    {
        return preg_replace('/[^0-9+]/', '', $MobileNumber);
    }

    static function getWhatsAppLink($MobileNumber)
    {
        return "https://wa.me/".self::getPhoneNumber($MobileNumber);
    }

    static function getDataFromURL($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result)
            return $result;
        else
            return false;
    }

    static function makeReadableColumnName($ColumnName)
    {
        if (str_starts_with($ColumnName, "is_")) {
            $ColumnName = str_replace("is_", "", $ColumnName);
            $ColumnName .= "?";
        }

        $ColumnName = str_replace("_id", "", $ColumnName);
        $ColumnName = str_replace("_", " ", $ColumnName);

        return ucwords($ColumnName);
    }

    static function clearStringForURL($String)
    {
        $String = str_replace(" ", '-', $String);
        return preg_replace("/[^A-Za-z0-9-]/", '', $String);
    }

    static function highlightText($Value)
    {
        if ($Value > 0)
            echo 'text-success';
        elseif ($Value < 0)
            echo 'text-danger';
        else
            echo 'text-warning';
    }
}