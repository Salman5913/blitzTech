var timeout_id = null;

$(function() {

    if ($("table.data-table-report").length > 0)
        $("table.data-table-report").dataTable().fnLengthChange( -1 );

    $("#account_id").on("accountSelected", function () {
        $.getJSON("../chart_of_account/ajax_get_emp_details.php", { account_id: $("#account_id").attr("data-ac-id") }, function (data) {
            $("#selected_emp_id").html(data.emp_id);
            $("#selected_emp_branch").html(data.branch_name);
        });
    });

    /*$("#bulk_advances").find("input").change(function () {
        if (timeout_id !== null)
            clearTimeout(timeout_id);

        timeout_id = setTimeout(function () {
            var total_advance_full = 0;
            $("[data-js='advance']").filter(function () {
                return !!this.value;
            }).each(function () {
                total_advance_full += parseFloat($(this).val());
            });
            $("[data-js='total_advances_amount']").html(thousandSep(Math.round(total_advance_full)));
        }, 500);
    }).trigger("change");*/
});
