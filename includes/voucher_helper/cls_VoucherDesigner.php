<?php

class VoucherDesigner
{
    public BasicVoucher $oBasicVoucher;
    public BasicVoucherForm $oBasicVoucherForm;
    
    public function __construct()
    {
        $this->oBasicVoucher = new $_GET['voucher_class']();
        $this->oBasicVoucher->initForEdit();

        $this->oBasicVoucherForm = new BasicVoucherForm($this->oBasicVoucher);

        if ($this->oBasicVoucher->isCopy())
            $this->oBasicVoucher->unsetEdit();

        if ($this->oBasicVoucher->isEdit())
        {
            VoucherHelper::redirectIfVoucherEditNotPermitted($this->oBasicVoucher->getDistinctId());
        }
    }
    
    function formDesigner($AjaxFormSupport=false, $isAdditionalButton=true)
    {
        $ElementGroups = $this->oBasicVoucherForm->getElementGroups();
        $RowDetails = $this->oBasicVoucher->getDetails();
        $isEdit = $this->oBasicVoucher->isEdit();
        $OverviewTotals = $this->oBasicVoucherForm->getOverviewTotals();

        ?>
        <form method="post" action="../_voucher_defaults/op_save_voucher.php" <? if ($AjaxFormSupport) { ?>data-ajax-submit-support="true"<? } ?> <? Utils::formValidate(); ?> enctype="multipart/form-data" data-form-type="<?=$this->oBasicVoucher::class?>">
            <? $this->oBasicVoucher->showVoucherDeleteNotification(); ?>
            <div class="row">
                <div class="col-12">
                    <? $this->oBasicVoucher->showTopTextSegment() ?>
                </div>
            </div>
            <div class="row">
                <? foreach ($ElementGroups as $GroupName => $GroupData) { ?>
                    <div class="<?=$GroupData['group_class']?>"><!--col-lg-12-->
                        <div class="card">
                            <h3 class="card-title"><?=$GroupName == BasicVoucherForm::DEFAULT_GROUP ? 'Basic Details' : $GroupName?></h3>
                            <div class="row">
                                <? if ($GroupName == BasicVoucherForm::DEFAULT_GROUP) { ?>
                                    <div class="<?=$GroupData['element_class']?>"><!--col-xl-3 col-lg-4 col-md-6-->
                                        <div class="form-group">
                                            <label for="voucher_date">Date</label>
                                            <input type="text" name="voucher_date" id="voucher_date" data-type="date" value="<?=DateDecode($RowDetails['voucher_date']) ?>" <? Utils::isRequired() ?>>
                                        </div>
                                    </div>
                                    <? if ($this->oBasicVoucher->isLedgerObjectRequired()) CostCenter::designVoucherField($RowDetails['cost_center_id'], $isEdit, $GroupData['element_class']); ?>
                                    <? if ($this->oBasicVoucher->isProject()) { $ACType = $this->oBasicVoucher->isAllProjectList() ? Autocomplete::TYPE_PROJECT : Autocomplete::TYPE_APPROVED_PROJECT; ?>
                                        <div class="<?=$GroupData['element_class']?>"><!--col-xl-3 col-lg-4 col-md-6-->
                                            <div class="form-group">
                                                <label for="_project_id">Project</label>
                                                <input type="text" name="_project_id" id="_project_id" <? Autocomplete::designAutocomplete($ACType, [], $RowDetails['project_id']); ?> >
                                            </div>
                                        </div>
                                    <? } ?>
                                <? } ?>
                                <? $this->oBasicVoucherForm->getMainTableDesignHTML($GroupName, $GroupData, $RowDetails, $_GET) ?>
                            </div>
                        </div>
                    </div>
                <? } ?>
            </div>
            <?
            foreach ($this->oBasicVoucherForm->getDataTables() as $Key => $TableName) {
                $SelectData = $this->oBasicVoucher->getData($Key);
                $CountRow = DatabaseHelper::getRecordCount($SelectData)+1;
                $i=0;
                ?>
                <div class="row <? if ($this->oBasicVoucherForm->isDataTableHidden($Key)) { ?>hidden<? } ?>">
                    <div class="col-lg-12">
                        <div class="card">
                            <h3 class="card-title"><?=empty($Key) ? 'Voucher Data' : $this->oBasicVoucherForm->makeReadableColumnName($Key)?></h3>
                            <table class="table dynamic table-bordered <?=$this->oBasicVoucherForm->getVoucherFormUtils()->isNonEditableDataTables($Key) ? 'non-editable' : '' ?> color table-striped table-<?=$Key?>">
                                <thead>
                                <tr>
                                    <? foreach ($this->oBasicVoucherForm->getDataTableColumns($TableName) as $ColumnName => $DataType) { ?>
                                        <? foreach ($this->oBasicVoucherForm->getAdditionalColumns($Key, $ColumnName) as $AdditionalColumnName => $AdditionalColumn) {  ?>
                                            <th class="<?=$AdditionalColumn['css_class']?>"><?=$this->oBasicVoucherForm->makeReadableColumnName($AdditionalColumnName)?></th>
                                        <? } ?>
                                        <th width="<?=$this->oBasicVoucherForm->getColumnWidth($ColumnName)?>" class="<?=in_array($ColumnName, $this->oBasicVoucherForm->getVoucherFormUtils()->getHiddenFields()) ? 'hidden' : '' ?>"><?=$this->oBasicVoucherForm->makeReadableColumnName($ColumnName)?>
                                            <? if (!empty($this->oBasicVoucherForm->getDataTableComment($TableName, $ColumnName))) { ?>
                                                <i class="mdi mdi-help-circle" title="<?=$this->oBasicVoucherForm->getDataTableComment($TableName, $ColumnName)?>"></i>
                                            <? } ?>
                                        </th>
                                    <? } ?>
                                    <? foreach ($this->oBasicVoucherForm->getAdditionalColumns($Key, 'last_column') as $AdditionalColumnName => $AdditionalColumn) {  ?>
                                        <th class="<?=$AdditionalColumn['css_class']?>"><?=$this->oBasicVoucherForm->makeReadableColumnName($AdditionalColumnName)?></th>
                                    <? } ?>
                                </tr>
                                </thead>
                                <tbody>
                                <? while ($RowData = DBQM::fetch_assoc($SelectData) OR $i<$CountRow) { $this->oBasicVoucherForm->setRowAttributes([]); ?>
                                    <tr>
                                        <? foreach ($this->oBasicVoucherForm->getDataTableColumns($TableName) as $ColumnName => $DataType) { ?>
                                            <? foreach ($this->oBasicVoucherForm->getAdditionalColumns($Key, $ColumnName) as $AdditionalColumnName => $AdditionalColumn) {  ?>
                                                <td data-table-column="<?=$AdditionalColumnName?>" class="text <?=$AdditionalColumn['css_class']?>"></td>
                                            <? } ?>
                                            <td class="<?=in_array($ColumnName, $this->oBasicVoucherForm->getVoucherFormUtils()->getHiddenFields()) ? 'hidden' : '' ?>"><? $this->oBasicVoucherForm->getFieldType($ColumnName, $DataType, $RowData[$ColumnName], $Key, $RowData) ?></td>
                                        <? } ?>
                                        <? foreach ($this->oBasicVoucherForm->getAdditionalColumns($Key, 'last_column') as $AdditionalColumnName => $AdditionalColumn) {  ?>
                                            <td data-table-column="<?=$AdditionalColumnName?>" class="text <?=$AdditionalColumn['css_class']?>"></td>
                                        <? } ?>
                                    </tr>
                                    <? $i++; } ?>
                                </tbody>
                                <tfoot>
                                <tr class="font-weight-bold">
                                    <? foreach ($this->oBasicVoucherForm->getDataTableColumns($TableName) as $ColumnName => $DataType) { ?>
                                        <? foreach ($this->oBasicVoucherForm->getAdditionalColumns($Key, $ColumnName) as $AdditionalColumnName => $AdditionalColumn) {  ?>
                                            <td class="text-center <?=$AdditionalColumn['css_class']?>" <? if ($this->oBasicVoucherForm->isColumnTotalRequired($AdditionalColumnName)) { Utils::sumInputs("[data-js=".$AdditionalColumnName."]", true, $AdditionalColumnName); } ?>></td>
                                        <? } ?>

                                        <td <? if ($this->oBasicVoucherForm->isColumnTotalRequired($ColumnName)) { Utils::sumInputs("[data-js=".$ColumnName."]", true, $ColumnName); } ?> class="text-center     <?=in_array($ColumnName, $this->oBasicVoucherForm->getVoucherFormUtils()->getHiddenFields()) ? 'hidden' : '' ?>"></td>
                                    <? } ?>
                                    <? foreach ($this->oBasicVoucherForm->getAdditionalColumns($Key, 'last_column') as $AdditionalColumnName => $AdditionalColumn) {  ?>
                                        <td class="text-center <?=$AdditionalColumn['css_class']?>" <? if ($this->oBasicVoucherForm->isColumnTotalRequired($AdditionalColumnName)) { Utils::sumReportColumn("[data-table-column=".$AdditionalColumnName."]"); } ?>></td>
                                    <? } ?>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <? if (count($OverviewTotals) > 0) { ?>
                    <div class="row">
                        <div class="col-lg-6"></div>
                        <div class="col-sm-6">
                            <div class="card">
                                <div class="row">
                                    <div class="col-auto mr-auto">
                                        <h3 class="card-title"> Voucher Totals</h3>
                                    </div>
                                    <div class="col-auto ml-auto"></div>
                                </div>
                                <? $i=0; foreach ($OverviewTotals as $TotalRows) { ?>
                                    <div class="row <?=($i++ > 0) ? 'border-top pt-1 ' : '' ?> pb-3 pb-md-1">
                                        <label class="col-auto col-form-label"><?=$TotalRows['name']?></label>
                                        <div class="col-auto col-form-label ml-auto total_overview_<?=$TotalRows['table_final_column_name']?>" data-overview-totals="true" data-overview-totals-unformatted=""></div>
                                    </div>
                                <? } ?>
                                <div class="row bg-soft-success font-weight-bold border-top pt-1 pb-3 pb-md-1">
                                    <label class="col-auto col-form-label font-weight-bold">Total</label>
                                    <div class="col-auto col-form-label ml-auto total_overview_voucher_total" data-overview-totals="true" data-overview-totals-unformatted=""></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <? } ?>
            <? } ?>
            <? $this->oBasicVoucher->showVoucherButtons([], new BasicVoucherAdditionalButton(), $isAdditionalButton); ?>
            <input type="hidden" name="voucher_class" value="<?=$_GET['voucher_class']?>">
            <? if (isset($_GET['is_ajax_voucher_call'])) { ?>
                <input type="hidden" name="_ajax_voucher" value="true">
            <? } ?>
            <? if (isset($_GET['_ac_type'])) { ?>
                <input type="hidden" name="_ac_type" value="<?=$_GET['_ac_type']?>">
            <? } ?>
        </form>
        <?php
    }
}
?>