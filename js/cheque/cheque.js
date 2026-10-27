$(function() {

    $('#account_id, #reissue_account_id').on('blur', function() {
        $("[data-js='from_account']").html($("#account_id").val());
        $("[data-js='to_account']").html($("#reissue_account_id").val());
    }).trigger("blur");

    $("#is_return").click(function () {
        if ($(this).is(":checked"))
            $('[data-return-linked]').removeClass("hidden");
        else
            $('[data-return-linked]').addClass("hidden");
    });

    /*$("form#voucher").submit(function() {
        if (formErrorCheck(this)) {
            nameToIdConvert();
            return true;
        }
        return false;
    });*/
});