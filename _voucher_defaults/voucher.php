<?php
include_once("../connection/localhost.php");

$oVoucherDesigner = new VoucherDesigner();
$RowHighlightConditions = $oVoucherDesigner->oBasicVoucherForm->getAllRowHighlightConditions();

include_once("../includes/inc_header.php");

$oVoucherDesigner->oBasicVoucher->additionalStyle();
?>
<script src="../assets/libs/tinymce/tinymce.min.js"></script>
<? $oVoucherDesigner->formDesigner(); ?>
<? foreach ($oVoucherDesigner->oBasicVoucherForm->getAttachedJS() as $JSFile) { ?>
    <script src="<?=$JSFile?>"></script>
<?php } ?>
<script>
var _is_edit_voucher = <?=$oVoucherDesigner->oBasicVoucher->isEdit() ? 'true' : 'false' ?>;
$(function() {
    <? foreach ($RowHighlightConditions as $Data) { ?>
        $("table.dynamic").on("change", "[data-table-column=<?=$Data['column']?>]", function() {

            var condition = false;

            <? if (in_array($Data['operator'], ['==', '!='])) { ?>
                condition = $(this).val() <?=$Data['operator']?> '<?=$Data['value']?>';
            <? } elseif (in_array($Data['operator'], ['>=', '<=', '>', '<'])) { ?>
                condition = NumberHelper.parseInput($(this)) <?=$Data['operator'] ." ". $Data['value']?>;
            <? } elseif ($Data['operator'] == 'empty') {  ?>
                condition = $(this).val() === '' || $(this).val() === '0' || NumberHelper.round(NumberHelper.parseInput($(this))) === 0;
            <? } ?>

            if (condition)
                $(this).closest("tr").addClass("<?=$Data['class_name']?>");
            else
                $(this).closest("tr").removeClass("<?=$Data['class_name']?>");
        }).trigger("change");

        $("table.dynamic [data-table-column=<?=$Data['column']?>]").each(function () {
            $(this).trigger("change");
        })
    <? } ?>
});
</script>
<?php
$oVoucherDesigner->oBasicVoucher->additionalScript();
?>
<? include_once("../includes/inc_footer.php");