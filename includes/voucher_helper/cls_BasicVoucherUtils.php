<?php


abstract class BasicVoucherUtils
{
    /* ************** Report & Links *************** */
    function getEditLink($VoucherTypeId, $Id)
    {
        return VoucherType::getLink($VoucherTypeId, $Id);
    }

    function getPrintLink($ClassName)
    {
        $FileName = "print_voucher.php";

        if (is_file($FileName))
            return $FileName."?t=a";
        else
            return VoucherHelper::getDefaultPrintLink($ClassName)."?t=a";

    }

    function getViewVoucherLink($VoucherTypeId, $Id)
    {
        return VoucherType::getLink($VoucherTypeId, $Id)."&view=true";
    }

    function getCopyVoucherLink($VoucherTypeId, $Id)
    {
        return VoucherType::getLink($VoucherTypeId, $Id)."&copy=true";
    }
    /* ************** Report & Links *************** */



    /* ************** Disable Editing ****************** */
    function disableEditIfNoPermission($isEdit)
    {
        if ($isEdit and !SecurityShell::isAccess("Settings", "Vouchers Edit"))
        {
            $this->disableEdits();
        }
    }

    function displayForViewPurpose()
    {
        if (isset($_GET['view']))
            $this->disableEdits();
    }

    protected function disableEdits()
    {
        $Filter = isset($_GET['is_ajax_voucher_call']) ? '.slide-popup ' : '';
        ?>
        <script>
            $(function () {
                $("<?=$Filter?>form[data-form-validate='true'] input:visible:not([type=checkbox]):not([name='voucher_date']), <?=$Filter?>select, <?=$Filter?>textarea").each(function () {
                    /*var value = "<div class='form-control'>" + $(this).val() + "</div>";
                    $(value).insertAfter($(this));
                    $(this).hide();*/

                    $(this).removeAttr("name");
                    $(this).attr("disabled", true);
                });

                $("<?=$Filter?>table.dynamic .action").remove();

                $("<?=$Filter?>form:not(.app-search) .voucher-buttons").remove();
                $('<?=$Filter?>form:not(.app-search)').contents().unwrap();
            })
        </script>
        <?php
    }
    /* ************** Disable Editing ****************** */



    /* *************** Check for Date Field ***************** */
    function checkForVoucherDateField()
    {
        if (!isset($_GET['view']))
        {
            ?>
            <script>
                $(function () {
                    if ($("input[name='voucher_date']").length === 0)
                    {
                        initError();
                        setError("Voucher Date Field Not Found");
                        showError();
                    }
                })
            </script>
            <?php
        }
    }
}