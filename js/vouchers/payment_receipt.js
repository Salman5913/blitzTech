$(function() {

    $("table")
        .on("accountSelected", "input[data-type='search_account']", function() {
            const closestRow = $(this).closest("tr");
            const accountId = $(this).attr("data-ac-id");

            ChartOfAccount.getAccountBalance(closestRow.find(".balance"), accountId, false);
            ChartOfAccount.getCurrencyRate(closestRow.find("[data-js='currency_rate']"), accountId, true);
        });

    $('[data-submit-alert="Y"]').click(function(event){
        var branch_id = $("[data-js='branch_id']").attr("data-ac-id");
        var first_row_account = $('table.dynamic.table-0 tbody tr:eq(0) input[data-type="search_account"]').val();

        if (first_row_account  === '' || first_row_account === undefined)
        {
            event.preventDefault();
            Swal.fire({
                icon: "error",
                title: "Invalid Data!",
                text: "Can not save empty voucher!",
                customClass: {
                    confirmButton: 'color'
                }
            });
            return false;
        }
        else {
            $('table.dynamic.table-0 tbody tr').each(function() {
                var _this = $(this);
                var amount = NumberHelper.parseInput($("[data-js='amount']", _this));
                var account_id = $("[data-type='search_account']", _this).attr("data-ac-id");
                var account_name = $("[data-type='search_account']", _this).val();

                if ((amount === 0 || amount === 0.00 || amount == '') && (account_id  !== '' && account_id !== undefined))
                {
                    event.preventDefault();
                    Swal.fire({
                        icon: "error",
                        title: "Invalid Data!",
                        text: "Amount of "+account_name+" is 0. You can't create voucher!",
                        customClass: {
                            confirmButton: 'color'
                        }
                    });
                    return false;
                }
            });
        }
    });

    setTimeout(function (){
        $("input[data-type='search_account']").trigger("accountSelected");
    }, 500);
});