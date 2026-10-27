<?php


class Autocomplete
{
    const TYPE_ACCOUNT = 'account';
    const TYPE_COA = 'coa';
    const TYPE_STOCK = 'stock';

    /**
     * Categories required as array
     */
    const TYPE_ACCOUNT_BY_COA = 'account_by_coa';
    const TYPE_BRANCH = 'branch';
    const TYPE_STOCK_CATEGORY = 'stock_category';
    const TYPE_STOCK_BRAND = 'stock_brand';
    const TYPE_STOCK_UNIT = 'stock_unit';
    const TYPE_STOCK_ATTR_UNIT = 'stock_attr_unit';
    const TYPE_STOCK_ATTRIBUTE_ALL = 'stock_attribute_all';
    const TYPE_VOUCHER_TYPE = 'voucher_type';
    const TYPE_CURRENCY = 'currency';
    const TYPE_COST_CENTER = 'cost_center';

    const TYPE_PROJECT = 'project';
    const TYPE_QUOTATION_CUSTOMER = 'quotation_customer';
    const TYPE_PROJECT_PAYMENT_TYPE = 'projectPaymentType';
    const TYPE_APPROVED_PROJECT = 'approvedProject';

    const TYPE_CRM_SOURCE = 'crm_source';
    const TYPE_CRM_STAGE = 'crm_stage';

    const TYPE_CRM_SALES_PITCH = 'crm_sales_pitch';
    const TYPE_CRM_TAGS = 'crm_tags';

    const TYPE_CONTACT_CARD = 'contact_card';

    const TYPE_CRM_INQUIRY_TYPE = 'crm_inquiry_type';


    private $Type;
    private $Table;
    private $Column;
    private bool $isAutocompleteRequired = true;
    private array $Where = [];
    private $LinkedVoucherType;


    function __construct($Type)
    {
        $this->Type = $Type;
        $this->setData();
    }

    function setData()
    {
        $TableName = '';
        $ColumnName = '';
        $Where = [];
        $LinkedVoucherType = '';

        switch ($this->Type)
        {
            case self::TYPE_CURRENCY:
                $TableName = 'acc_currency';
                $ColumnName = 'name';
                break;

            case self::TYPE_BRANCH:
                $TableName = 'branch';
                $ColumnName = 'name';
                break;

            case self::TYPE_STOCK_CATEGORY:
                $TableName = 'st_stock_category';
                $ColumnName = 'name';
                break;

            case self::TYPE_STOCK_BRAND:
                $TableName = 'st_stock';
                $ColumnName = 'brand';
                $this->isAutocompleteRequired = false;
                break;

            case self::TYPE_STOCK_UNIT:
                $TableName = 'st_stock';
                $ColumnName = 'unit';
                $this->isAutocompleteRequired = false;
                break;

            case self::TYPE_STOCK_ATTR_UNIT:
                $TableName = 'st_stock_attribute';
                $ColumnName = 'unit';
                $this->isAutocompleteRequired = false;
                break;

            case self::TYPE_STOCK_ATTRIBUTE_ALL:
                $TableName = 'cp_stock_attribute';
                $ColumnName = 'name';
                break;

            case self::TYPE_VOUCHER_TYPE:
                $TableName = 'vt_voucher_type';
                $ColumnName = 'abbr';
                break;

            case self::TYPE_COST_CENTER:
                $TableName = 'cc_cost_center';
                $ColumnName = 'cost_center_name';
                break;

            case self::TYPE_PROJECT:
                $TableName = 'vw_ac_project';
                $ColumnName = 'ac_project';
                break;

            case self::TYPE_APPROVED_PROJECT:
                $TableName = 'vw_ac_approved_project';
                $ColumnName = 'ac_project';
                $Where = ["is_approved = 'Y'"];
                break;

            case self::TYPE_QUOTATION_CUSTOMER:
                $TableName = 'qt_quote';
                $ColumnName = 'customer_name';
                $this->isAutocompleteRequired = false;
                break;

            case self::TYPE_PROJECT_PAYMENT_TYPE:
                $TableName = 'prj_project_wise_payment';
                $ColumnName = 'payment_type';
                $this->isAutocompleteRequired = false;
                break;

            case self::TYPE_CRM_SOURCE:
                $TableName = 'crm_source';
                $ColumnName = 'source_name';
                $Where[] = 'is_active = '.escape("Y");
                break;

            case self::TYPE_CRM_STAGE:
                $TableName = 'crm_stage';
                $ColumnName = 'stage_name';
                break;

            case self::TYPE_CRM_SALES_PITCH:
                $TableName = 'crm_sales_pitch';
                $ColumnName = 'industry_name';
                $LinkedVoucherType = VoucherType::CRSP;
                break;

            case self::TYPE_CRM_TAGS:
                $TableName = 'crm_tags';
                $ColumnName = 'tag_name';
                $this->isAutocompleteRequired = false;
                break;

            case self::TYPE_CONTACT_CARD:
                $TableName = 'vw_dir_contact_card';
                $ColumnName = 'ac_name';
                $LinkedVoucherType = VoucherType::CCRD;
                break;

            case self::TYPE_CRM_INQUIRY_TYPE:
                $TableName = 'crm_inquiry_type';
                $ColumnName = 'type_name';
                $LinkedVoucherType = VoucherType::CRIT;
                break;
        }

        $this->Table = $TableName;
        $this->Column = $ColumnName;
        $this->Where = $Where;
        $this->LinkedVoucherType = $LinkedVoucherType;
    }

    function getLinkedVoucherType()
    {
        if (SecurityShell::isVoucherAccess($this->LinkedVoucherType))
            return $this->LinkedVoucherType;
        else
            return null;
    }

    function getLinkedVoucherTypeNewLink()
    {
        return $this->getLinkedVoucherType() ? VoucherType::getLink($this->getLinkedVoucherType(), '', true) : '';
    }

    function getType()
    {
        return $this->Type;
    }

    function isAutocompleteRequired()
    {
        return $this->isAutocompleteRequired;
    }

    public static function ACSearchAccountByCOAId(array $COAId, $AccountId, $Return=false)
    {
        $Output = '';
        $Output .= Utils::getRequireAutoComplete();

        $Output .= " data-type='search_account_coa' data-coa-id='" . json_encode($COAId) . "' data-can-access='".(SecurityShell::isAccess("Chart of Account", "Transaction Account") ? 'y' : 'n')."' ";

        if (!empty($AccountId)) {
            $Name = ChartOfAccount::getName($AccountId);
            $Output .= " value='" . $Name . "' data-ac-id='" . $AccountId . "' ";
        }

        if ($Return)
            return $Output;
        else
            echo $Output;
    }

    static function ACSearchCOA($COAId, $Return=false)
    {
        $Output = '';
        $Output .= Utils::getRequireAutoComplete();

        $Output .= " data-type='search_coa' ";

        if (!empty($COAId)) {
            $Name = ChartOfAccountCategory::getNameById($COAId);
            $Output .= " value='" . $Name . "' data-ac-id='" . $COAId . "' ";
        }

        if ($Return)
            return $Output;
        else
            echo $Output;
    }

    static function ACSearchAccount($AccountId, $Return=false)
    {
        $Output = '';
        $Output .= Utils::getRequireAutoComplete();
        $Output .= " data-type='search_account' data-can-access='".(SecurityShell::isAccess("Chart of Account", "Transaction Account") ? 'y' : 'n')."' ";

        if (!empty($AccountId)) {
            $Name = ChartOfAccount::getName($AccountId);
            $Output .= " value='" . $Name . "' data-ac-id='" . $AccountId . "' data-can-access='".(SecurityShell::isAccess("Chart of Account", "Transaction Account") ? 'y' : 'n')."' ";
        }

        if ($Return)
            return $Output;
        else
            echo $Output;
    }

    static function ACSearchStock($StockIdOrCombineCode, $AttributeId)
    {
        if (str_contains($StockIdOrCombineCode, "::"))
            list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($StockIdOrCombineCode);
        else
            $StockId = $StockIdOrCombineCode;

        Utils::requireAutoComplete();
        echo " data-type='search_stock' ";

        if (!empty($StockId)) {
            $Name = Stock::getName($StockId, $AttributeId);
            echo " value='" . $Name . "' data-ac-id='" . Stock::joinStockIdAttributeId($StockId, $AttributeId) . "' ";
        }
    }


    static function ACSearchStockRow($StockId, $AttributeId, $BranchElement, $AvailableQtyElement, $QtyElement, $RateElement, $AllowOnlyAvailableQuantity=true, $ItemDiscountElement='', $CostPriceElement='', $WholeSalePriceElement='', $TotalPriceElement=''): void
    {
        Utils::requireAutoComplete();
        echo " 
            data-type='search_stock_row' 
            data-track='Y' 
            data-stock-available-qty='".$AvailableQtyElement."' 
            data-qty-element='".$QtyElement."' 
            data-stock-rate-element='".$RateElement."' 
            data-stock-branch-element='".$BranchElement."' 
            data-stock-allow-available-qty='".($AllowOnlyAvailableQuantity ? 'Y' : 'N')."'
        ";

        if (!empty($ItemDiscountElement))
            echo " data-stock-discount-element='".$ItemDiscountElement."' ";
        if (!empty($CostPriceElement))
            echo " data-stock-cost-price-element='".$CostPriceElement."' ";
        if (!empty($WholeSalePriceElement))
            echo " data-stock-wholesale-price-element='".$WholeSalePriceElement."' ";
        if (!empty($TotalPriceElement))
            echo " data-stock-total-price-element='".$TotalPriceElement."' ";

        if (!empty($StockId)) {
            $Name = Stock::getName($StockId, $AttributeId);
            echo " value='" . $Name . "' data-ac-id='" . Stock::joinStockIdAttributeId($StockId, $AttributeId) . "' ";
        }
    }

    static function ACSearchStockByTrack($Track, $StockId)
    {
        Utils::requireAutoComplete();
        echo " data-type='search_stock_track' data-track='" . $Track . "' ";

        if (!empty($StockId)) {
            $Name = Stock::getName($StockId);
            echo " value='" . $Name . "' data-ac-id='" . $StockId . "' ";
        }
    }

    static function ACSearchStockAttr($StockAttributeId)
    {
        Utils::requireAutoComplete();
        echo " data-type='search_stock_attribute' ";

        if (!empty($StockAttributeId)) {
            $Name = StockAttribute::getName($StockAttributeId);
            echo " value='" . $Name . "' data-ac-id='" . $StockAttributeId . "' ";
        }
    }

    static function ACEditSearchOther($Id, $Value)
    {
        if (!empty($Id)) {
            echo " value='" . $Value . "' data-ac-id='" . $Id . "' ";
        }
    }

    function searchData($Term='')
    {
        $Where = $this->Where;

        if ($this->Type == self::TYPE_COST_CENTER)
        {
            $SelectCostCenter = DBQM::query("SELECT GROUP_CONCAT(cost_center_id) FROM sh_user_cost_center WHERE user_id = ".escape(SecurityShell::getLoggedInUserId()));

            if (DBQM::result($SelectCostCenter, 0) != '')
                $Where[] = " id IN (".DBQM::result($SelectCostCenter, 0).") ";
        }

        if (!empty($Where))
        {
            $Where = implode(" AND ", $Where);
            $Where = " AND ".$Where;
        }
        else
            $Where = '';

        return DBQM::query("
                SELECT 
                    `{$this->Column}` AS col_data, 
                    id AS col_id 
                FROM 
                    `{$this->Table}` 
                WHERE 
                    `{$this->Column}` <> '' AND
                    `{$this->Column}` LIKE ".escape("%".$Term."%")."
                    {$Where}
                GROUP BY 
                    `{$this->Column}` 
                ORDER BY 
                    `{$this->Column}`
                LIMIT 
                    50
        ");
    }

    function getIdByData($Data)
    {
        $Select = DBQM::query("SELECT id FROM `{$this->Table}` WHERE `{$this->Column}` = ".escape($Data));
        return DBQM::result($Select, 0);
    }

    function getDataById($Id)
    {
        $Select = DBQM::query("SELECT `{$this->Column}` FROM `{$this->Table}` WHERE id = ".escape($Id));
        return DBQM::result($Select, 0);
    }

    static function Search($Type, $EditValueId)
    {
        echo self::getSearch($Type);
        echo self::getEditValue($Type, $EditValueId);
    }

    static function getSearch($Type)
    {
        $oAutocomplete = new Autocomplete($Type);
        $Output = '';

        if ($oAutocomplete->isAutocompleteRequired())
            $Output .= Utils::getRequireAutoComplete();

        $Output .= ($oAutocomplete->getLinkedVoucherType()) ? " data-voucher-type='".$oAutocomplete->getLinkedVoucherTypeNewLink()."&_ac_type=".$Type."' " : '';
        return $Output." data-type=".$Type." ";
    }

    static function getEditValue($Type, $EditValueId)
    {
        $oAutocomplete = new Autocomplete($Type);
        $Output = '';

        if ($oAutocomplete->isAutocompleteRequired())
        {
            if (!empty($EditValueId)) {
                $Data = $oAutocomplete->getDataById($EditValueId);
                $Output = " value='".$Data."' data-ac-id='".$EditValueId."' ";
            }
        }
        else
            $Output = " value='".$EditValueId."'";

        return $Output;
    }

    function getTableName()
    {
        return $this->Table;
    }

    static function getJS()
    {
        $oClass = new ReflectionClass(__CLASS__);
        $Constants = $oClass->getConstants();
        ?>
        <script>
        function attacheBasicAutocomplete()
        {
            const $oAutocomplete = new AutoCompleteHandler();

            <?
            $AutoCompleteWithRequired = [];
            $AutoCompleteWithoutRequired = [];

            foreach ($Constants as $Value) {
                $oAutocomplete = new Autocomplete($Value);
                $Type = $oAutocomplete->getType();
                $isAutocompleteRequired = $oAutocomplete->isAutocompleteRequired();

                if ($isAutocompleteRequired)
                    $AutoCompleteWithRequired[] = $Type;
                else
                    $AutoCompleteWithoutRequired[] = $Type;
            }
            ?>

            const autocompleteWithRequired = <?php echo json_encode($AutoCompleteWithRequired); ?>;
            const autocompleteWithoutRequired = <?php echo json_encode($AutoCompleteWithoutRequired); ?>;

            autocompleteWithRequired.forEach((type) => {
                $(`[data-type='${type}']`)
                    .autocomplete({
                        source: `${SiteURL}_autocomplete/ac_search.php?autocomplete_type=${type}`,
                        minLength: 0,
                        select: (e, ui) => $oAutocomplete.handleSelectWithTrigger(e, ui, `${type}Selected`),
                    })
                    .change($oAutocomplete.handleInputChange)
                    .blur((e) => {
                        $oAutocomplete.handleBlur($(e.currentTarget), type, `${type}Selected`);
                    });
            });

            autocompleteWithoutRequired.forEach((type) => {
                $(`[data-type='${type}']`).autocomplete({
                    source: `${SiteURL}_autocomplete/ac_search.php?autocomplete_type=${type}`,
                    minLength: 0,
                    select: function () {
                        $(this).focus();
                    },
                });
            });

            $("[data-type=select2]").select2();
        }
        </script>
        <?php
    }

    static function designAutocomplete($Type, $AdditionalArguments, $Value)
    {
        if ($Type == Autocomplete::TYPE_ACCOUNT)
            Autocomplete::ACSearchAccount($Value);
        elseif ($Type == Autocomplete::TYPE_STOCK)
        {
            list($StockId, $AttributeId) = Stock::splitStockIdAttributeId($Value);
            Autocomplete::ACSearchStock($StockId, $AttributeId);
        }
        elseif ($Type == Autocomplete::TYPE_ACCOUNT_BY_COA)
            Autocomplete::ACSearchAccountByCOAId($AdditionalArguments, $Value);
        elseif ($Type == Autocomplete::TYPE_COA)
            Autocomplete::ACSearchCOA($Value);
        elseif (!empty($Type))
            Autocomplete::Search($Type, $Value);
    }

    /**
     * @return mixed
     */
    public function getColumn()
    {
        return $this->Column;
    }

    static function getAjaxCreatedNewlyItem($Type, $Id)
    {
        if (empty($Type))
            return false;

        $oAutocomplete = new Autocomplete($Type);
        $Column = $oAutocomplete->getColumn();
        $Table = $oAutocomplete->getTableName();

        $Select = DBQM::query("SELECT `{$Column}` FROM `{$Table}` WHERE `id` = ".escape($Id));
        return DBQM::result($Select, 0);
    }
}