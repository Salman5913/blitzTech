<?php

class FormFieldTypeStock extends FormFieldType
{
    /**
     * Displays stock row, it includes
     *  - Hiding Attribute ID,
     *  - Branch id can be on same row, or it can be attached from main table
     *      - If branch id is attached on main table, you need to separately attach branch autocomplete on branch.
     *  - Price column can be left empty (in case you don't want selling price)
     *
     * @param BasicVoucherFormUtils $oFormUtils
     * @param $TableIndex
     * @param $StockIdColumn
     * @param $AttributeIdColumnName
     * @param $BranchIdColumn
     * @param $QuantityColumn
     * @param $PriceColumn
     * @param string $AvailableQtyColumn displays as text column
     * @param bool $AllowOnlyAvailableQty if false, any qty can be accepted in qty field
     * @param string $ItemDiscountColumn to calculate discount on price column and make row total
     * @param bool $CostPriceColumnRequired If true, a text column will be displayed (before price or qty column) which ever is present.
     * @param bool $WholeSalePriceColumnRequired if true, a text column will be displayed after price column. If price column is not there, wholesale will not work too.
     * @param bool $TotalColumnRequired If true, automatic row calculation (via JS) will be implemented.
     */
    public function __construct(BasicVoucherFormUtils &$oFormUtils, $TableIndex, $StockIdColumn, $AttributeIdColumnName, $BranchIdColumn, $QuantityColumn, $PriceColumn, string $AvailableQtyColumn='', bool $AllowOnlyAvailableQty=true, string $ItemDiscountColumn='', bool $CostPriceColumnRequired=false, bool $WholeSalePriceColumnRequired=false, bool $TotalColumnRequired=true)
    {
        $AvailableQtyColumn = empty($AvailableQtyColumn) ? 'available_qty' : $AvailableQtyColumn;

        $this->AdditionalDetails['attribute_id_column'] = $AttributeIdColumnName;
        $this->AdditionalDetails['branch_id_column'] = $BranchIdColumn;
        $this->AdditionalDetails['available_qty_column'] = $AvailableQtyColumn;
        $this->AdditionalDetails['qty_column'] = $QuantityColumn;
        $this->AdditionalDetails['allow_only_available_qty'] = $AllowOnlyAvailableQty;
        $this->AdditionalDetails['rate_column'] = $PriceColumn;
        $this->AdditionalDetails['item_discount_column'] = $ItemDiscountColumn;


        /* *************** Setting Additional Attributes of Stock Fields, based on requirements *************** */
        $this->AdditionalDetails['cost_price_column'] = $CostPriceColumnRequired ? 'avg_cost_price' : '';
        $this->AdditionalDetails['wholesale_price_column'] = $WholeSalePriceColumnRequired ? 'wholesale_price' : '';
        $this->AdditionalDetails['total_price_column'] = $TotalColumnRequired ? 'total_amount' : '';
        /* *************** Setting Additional Attributes of Stock Fields, based on requirements *************** */


        /* ------ Hide Attribute Id ------ */
        $oFormUtils->setHiddenFields([$AttributeIdColumnName]);
        /* ------ Hide Attribute Id ------ */


        /* ------ Add Available Qty Column (As Text) ------ */
        $oFormUtils->addAdditionalColumns($TableIndex, $AvailableQtyColumn, 'int', $QuantityColumn, 'text-center');
        /* ------ Add Available Qty Column (As Text) ------ */


        /* ------ If Cost price is required, display it before : Price (if available) OR Qty (if price is not available) ------ */
        if ($CostPriceColumnRequired)
            $oFormUtils->addAdditionalColumns($TableIndex, $this->AdditionalDetails['cost_price_column'], 'int', empty($PriceColumn) ? $QuantityColumn : $PriceColumn, 'text-center');
        /* ------ If Cost price is required, display it next to : Price (if available) OR Stock (if price is not available) ------ */


        /* ------ If Wholesale price is required, display it next to : Price (if available) OR don't display it (if price is not available) ------ */
        if ($WholeSalePriceColumnRequired)
            $oFormUtils->addAdditionalColumns($TableIndex, $this->AdditionalDetails['wholesale_price_column'], 'int', $PriceColumn, 'text-center');
        /* ------ If Wholesale price is required, display it next to : Price (if available) OR don't display it (if price is not available) ------ */


        /* ------ If Total is required, add a text column at the very end of the table ------ */
        if ($TotalColumnRequired)
        {
            $oFormUtils->addAdditionalColumns($TableIndex, $this->AdditionalDetails['total_price_column'], 'int', 'last_column', 'text-right');
            $oFormUtils->showColumnTotal([$this->AdditionalDetails['total_price_column']]);
        }
        /* ------ If Total is required, add a text column at the very end of the table ------ */


        /* ------ Marking stock columns for save file ------ */
        $oFormUtils->setStockColumns($TableIndex, $StockIdColumn, $AttributeIdColumnName);
        /* ------ Marking stock columns for save file ------ */
    }

    function getFieldName(): null|string
    {
        return null;
    }

    function getFieldDesign($Value, $CommonAttributes): void
    {
        $AdditionalDetails = $this->getAdditionalDetails();

        $AttributeIdColumn = $AdditionalDetails['attribute_id_column'];
        $BranchIdColumn = $AdditionalDetails['branch_id_column'];

        $this->oFormFieldDesigner->setForceColumnType($BranchIdColumn, 'string');
        $this->updateRowColumnAttributes($BranchIdColumn, ' data-ac-id="'.$this->getRowData()[$BranchIdColumn].'" ');
        $this->updateRowColumnValue($BranchIdColumn, Branch::getName($this->getRowData()[$BranchIdColumn]));
        ?>
        <input
            type="text"
            <?=$CommonAttributes?>
            <? Autocomplete::ACSearchStockRow(
                $Value,
                $this->oFormFieldDesigner->getRowData($AttributeIdColumn),
                '[data-table-column="'.$BranchIdColumn.'"]',
                '[data-table-column="'.$AdditionalDetails['available_qty_column'].'"]',
                '[data-table-column="'.$AdditionalDetails['qty_column'].'"]',
                '[data-table-column="'.$AdditionalDetails['rate_column'].'"]',
                $AdditionalDetails['allow_only_available_qty'],
                '[data-table-column="'.$AdditionalDetails['item_discount_column'].'"]',
                '[data-table-column="'.$AdditionalDetails['cost_price_column'].'"]',
                '[data-table-column="'.$AdditionalDetails['wholesale_price_column'].'"]',
                '[data-table-column="'.$AdditionalDetails['total_price_column'].'"]'
            ); ?>
        >
        <?php
    }
}