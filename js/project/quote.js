
$(function() {
    var _end_row = $("h3:contains(INVENTORY)").closest("div.row");
    $("div.col-md-8").appendTo(_end_row);

    $("[data-report-column-sum='[data-table-column=total]']").addClass("text-right").removeClass("text-center");
    $("[data-report-column-sum='[data-table-column=inventory_total]']").addClass("text-right").removeClass("text-center");

    $("body").on("change", "input[data-type='int'], input[data-type='float']", function() {
        $("[data-type='int'], [data-type='float']").each(function() {
            if ($(this).val() == '')
                $(this).val(0);
        });

        var _table_rows = $("table.dynamic.table-DATA tbody tr");
        var _table_rows_inventory = $("table.dynamic.table-INVENTORY tbody tr");
        var total_rs = 0;
        var total_stock  = 0;

        /* ******* Data Stock Rows Calculation ******** */
        _table_rows.each(function() {
            var qty = NumberHelper.parseInput($("[data-js='qty']", this));
            var rate = NumberHelper.parseInput($("[data-js='rate']", this));

            var row_total = qty * rate;
            total_rs += row_total;

            $("[data-table-column='total']", this).html(NumberHelper.thousandSep(NumberHelper.round(row_total)));
        });
        /* ******* Data Stock Rows Calculation ******** */

        _table_rows_inventory.each(function() {
            var inventory_qty = NumberHelper.parseInput($("[data-js='inventory_qty']", this));
            var inventory_rate = NumberHelper.parseInput($("[data-js='inventory_rate']", this));

            var row_total_inventory = inventory_qty * inventory_rate;
            total_stock += row_total_inventory;

            $("[data-table-column='inventory_total']", this).html(NumberHelper.thousandSep(NumberHelper.round(row_total_inventory)));
        });

        $("[data-report-column-sum='[data-table-column=total]']").html(NumberHelper.thousandSep(total_rs));
        $("[data-report-column-sum='[data-table-column=inventory_total]']").html(NumberHelper.thousandSep(total_stock));
    });

    setTimeout(function (){
        $("input[data-type='float']:first-child").trigger("change");
    }, 500);
});