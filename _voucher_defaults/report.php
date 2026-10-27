<?
include_once("../connection/localhost.php");

/** @var BasicVoucher $oBasicVoucher */

if (!class_exists($_GET['voucher_class']))
    ErrorHandler::showOnErrorPage("Invalid controller class");

$oBasicVoucher = new $_GET['voucher_class']();

if (!($oBasicVoucher instanceof DefaultVoucherReport))
    ErrorHandler::showOnErrorPage("Voucher has not implemented DefaultVoucherReport");

$oBasicVoucherReport = new BasicVoucherReport($oBasicVoucher);

$Select = false;

if (($oBasicVoucherReport->hasFilters() or isset($_GET['search'])) or !$oBasicVoucherReport->hasFilters())
{
    $Select = $oBasicVoucher->getSearchQuery();
}


$_GET['_page_title'] = VoucherType::getName($oBasicVoucher->getAllowedVoucherTypes()[0]).' Report';
$_GET['_page_title_text'] = 'View / Edit Vouchers';

include_once("../includes/inc_header.php");
?>
    <form action="" method="get" <?Utils::formValidate()?>>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <? $oBasicVoucher->showVoucherButtonsWithOptions(($oBasicVoucher instanceof DefaultVoucherReportAdditionalUtils AND !empty((array)$oBasicVoucher->additionalReportVoucherVoucherTypesForOpenNew())) ? $oBasicVoucher->additionalReportVoucherVoucherTypesForOpenNew() : [$_GET['voucher_type_id']],
                        ($oBasicVoucher instanceof DefaultVoucherReportAdditionalUtils) ? $oBasicVoucher->additionalReportBulkOptionLinks() : [],
                        ($oBasicVoucher instanceof DefaultVoucherReportAdditionalUtils) ? $oBasicVoucher->additionalReportAddVoucherTypesQueryParam() : [],
                        ($oBasicVoucher instanceof DefaultVoucherReportAdditionalUtils) ? $oBasicVoucher->additionalReportAddOtherPagesLink() : []); ?>

                    <?php if ($oBasicVoucherReport->hasFilters()) { ?>
                    <div class="row  filter hidden">
                        <? $oBasicVoucherReport->designFilters(); ?>
                        <div class="col-sm-12 text-right">
                            <button type="submit" class="btn color" name="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <input type="hidden" name="voucher_class" value="<?=$_GET['voucher_class']?>">
        <input type="hidden" name="action_voucher_type_id" value="<?=$_GET['voucher_type_id']?>">
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="row">
                <div class="col-md-9"><h3 class="card-title">Search Results</div>
                <div class="col-auto ml-auto"><? UtilsPrint::button() ?></div>
            </div>
            <div class="table-responsive">
                <table id="data-table" class="table data-table-report color table-striped table-bordered <? UtilsPrint::makePrintable() ?>" style="min-height: 500px;">
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="check_all" value="Y"> </th>
                        <? foreach ($oBasicVoucherReport->getReportTableColumns() as $ColumnName => $ColumnData) { ?>
                            <th class="<?=$oBasicVoucherReport->getCSSClass($ColumnData['type'], $ColumnName)?>"><?=$ColumnData['label']?></th>
                        <? } ?>
                        <th class="text-right">Voucher Status</th>
                        <th class="text-right print-hidden"> Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                        <tr class="<? $oBasicVoucher->highlightDeletedRow($Row) ?>">
                            <td><label><input type="checkbox" name="distinct_id[<?=$Row['distinct_id']?>]" data-js="distinct_id" id="distinct_id" value="<?=$Row['distinct_id']?>"></label></td>
                            <? foreach ($oBasicVoucherReport->getReportTableColumns() as $ColumnName => $ColumnData) { ?>
                                <td width="<?=$ColumnData['width']?>" class="<?=$oBasicVoucherReport->getCSSClass($ColumnData['type'], $ColumnName)?>">
                                    <?=$oBasicVoucherReport->getReadableValue($ColumnData['type'], $Row[$ColumnName])?>
                                </td>
                            <? } ?>
                            <td class="text-right"><? $oBasicVoucher->getVoucherStatus($Row); ?></td>
                            <td class="text-right report-actions print-hidden">
                                <? $oBasicVoucher->getReportButtons($Row['voucher_type_id'], $Row['id'], ($oBasicVoucher instanceof DefaultVoucherReportAdditionalUtils) ? $oBasicVoucher->additionalReportLinks($Row) : []); ?>
                            </td>
                        </tr>
                    <? } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</form>
<? UtilsPrint::init('') ?>
<? include_once("../includes/inc_footer.php") ?>