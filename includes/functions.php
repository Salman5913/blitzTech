<?
if (!function_exists("escape"))
{
    /**
     * Accepts date in <br />
     * Date Only format: m/d/Y <br />
     * DateTime format: m/d/Y h:i:s
     *
     * @param $Date
     * @param string $format
     * @return string
     */
    function DateEncode($Date, string $format = 'Y-m-d', $ReturnEmptyAsItIs=false)
    {
        $StrToTime = strtotime($Date);
        return date('Y', $StrToTime) > 1972 ? date($format, $StrToTime) : ($ReturnEmptyAsItIs ? "" : date($format));
    }

    function DateDecode($Date, $format = 'M j, Y')
    {
        return substr($Date, 0, 4) > 1972 ? date($format, strtotime($Date)) : "";
    }

    /**
     * @param $Value
     * @param string $Type
     * @param bool $ReturnEmptyAsNull
     * @return float|int|string
     */
    function escape($Value, string $Type="text", bool $ReturnEmptyAsNull=false)
    {
        if (empty($Value) and $ReturnEmptyAsNull) return "null";

        if ($Value != null)
        {
            $Value = mysqli_real_escape_string(DatabaseHelper::getConnection(), $Value);
            $Value = trim($Value);
        }

        switch ($Type) {
            case "date":
            case "text":
                $Value = $Value != "" ? "'" . mb_convert_encoding($Value, "UTF-8") . "'" : "''";
                break;

            case "long":
            case "int":
                $Value = $Value != "" ? intval(ltrim($Value, 0)) : "0";
                break;

            case "double":
                $Value = $Value != "" ? doubleval(ltrim($Value, 0)) : "0";
                break;
        }
        return $Value;
    }


    function autoload($ClassName)
    {
        $FileName = "cls_".$ClassName.".php";

        if (is_dir("includes"))
            $RelatingBack = "";
        elseif (is_dir("../includes"))
            $RelatingBack = "../";
        else
            $RelatingBack = "../../";

        if (is_file($RelatingBack."includes/".$FileName))
            include_once($RelatingBack."includes/".$FileName);
        else
        {
            $SubFolders = glob($RelatingBack."includes/*", GLOB_ONLYDIR);
            foreach($SubFolders as $Folders)
                if (is_file($Folders."/".$FileName))
                    include_once($Folders."/".$FileName);
        }
    }

    spl_autoload_register("autoload");

    if (!function_exists("hex2bin"))
    {
        function hex2bin($HexData) {
            $BinData = '';
            for ($i=0;$i<strlen($HexData);$i+=2) {
                $BinData .= chr(hexdec(substr($HexData,$i,2)));
            }
            return $BinData;
        }
    }

    /**
     * @return ConstantAccount
     */
    function getConstantAccount()
    {
        return new ConstantAccount();
    }

    /**
     * @return ConstantCategory
     */
    function getConstantCategory()
    {
        return new ConstantCategory();
    }
}