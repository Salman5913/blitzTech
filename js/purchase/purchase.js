$(function() {

    $('#submit').removeClass('hidden');

    $("body").on("accountSelected", "[data-js='search_supplier_id']", function () {

        $.get("ajax_get_search_order_dropdown.php", { supplier_id: $(this).attr("data-ac-id")},
            function(data) {
                $('#order_id').html(data);
            }
        );
    });

    $("form").on("change", "input[data-type='int'], input[data-type='float']", function() {
        $("[data-type='int'], [data-type='float']").each(function() {
            if ($(this).val() == '')
                $(this).val(0);
        });

        var _table_rows = $("table.dynamic.stock tbody tr");
        var total_rs = 0;
        var total_rs_with_gst = 0;
        var gross_total = 0;

        /* ******* Data Stock Rows Calculation ******** */
        _table_rows.each(function() {
            var qty = NumberHelper.parseInput($("[data-js='stock_qty']", this));
            var qty_return = NumberHelper.parseInput($("[data-js='stock_qty_return']", this));
            var actual_rate = NumberHelper.parseInput($("[data-js='stock_rate']", this));
            var discount_percentage = NumberHelper.parseInput($("[data-js='stock_disc_percent']", this));
            var rate = NumberHelper.parseInput($("[data-js='stock_rate']", this));

            if (discount_percentage > 0)
                rate = rate - (actual_rate * (discount_percentage / 100));

            var row_total = (qty - qty_return) * rate;

            var row_total_with_gst = (qty - qty_return) * rate;

            total_rs += row_total;
            total_rs_with_gst += row_total_with_gst;

            $(".total_row", this).html(NumberHelper.thousandSep(NumberHelper.round(row_total)));
            $(".total_row_with_gst", this).html(NumberHelper.thousandSep(NumberHelper.round(row_total_with_gst)));
        });
        /* ******* Data Stock Rows Calculation ******** */

        $(".total_rs").html(NumberHelper.thousandSep(NumberHelper.round(total_rs)));
        $(".total_rs_with_gst").html(NumberHelper.thousandSep(NumberHelper.round(total_rs_with_gst)));

        var total_purchase = total_rs;
        $(".total_gross_sales").html(NumberHelper.thousandSep(NumberHelper.round(total_purchase)));

        var discount_amount = NumberHelper.calcPercentValue(total_rs, NumberHelper.parseInput($("#invoice_discount_percentage")), NumberHelper.parseInput($('#invoice_discount_amount')));

        gross_total = total_purchase - discount_amount;

        var total_gst = NumberHelper.calcPercentValue(gross_total, NumberHelper.parseInput($("#invoice_gst_percentage")), 0);

        $(".total_discount").html(NumberHelper.thousandSep(NumberHelper.round(discount_amount)));
        $("#calc_discount").val(discount_amount);

        $(".gross_total").html(NumberHelper.thousandSep(NumberHelper.round(gross_total)));

        $(".total_gst").html(NumberHelper.thousandSep(NumberHelper.round(total_gst)));
        $("#calc_gst").val(total_gst);

        var final_amount = gross_total + total_gst;

        $(".final_amount").html(NumberHelper.thousandSep(NumberHelper.round(final_amount)));

        $('#total_invoice_amount').val(final_amount);
    });

    $("table.stock").on("stockSelected", "input[data-type='search_stock']", function() {
        var _this = $(this);
        if (FormHelper.isAttr($(this).attr("data-ac-id")))
        {
            $.get("../stock/ajax_get_stock_full_name_by_id.php",
                {
                    stock_id: $(this).attr("data-ac-id")
                },
                function(data) {
                    _this.closest("tr").find("[data-js='item_detail']").val(data);
                }
            );
        }
    });

    $('[data-submit-alert="Y"]').click(function(event){
        var branch_id = $("[data-js='branch_id']").attr("data-ac-id");
        var first_row_stock = $('table.dynamic.stock tbody tr:eq(0) input[data-type="search_stock_row"]').val();

        if (first_row_stock  === '' || first_row_stock === undefined)
        {
            event.preventDefault();
            Swal.fire({
                icon: "error",
                title: "Invalid Data!",
                text: "Please select Item From Dropdown!",
                customClass: {
                    confirmButton: 'color'
                }
            });
            return false;
        }
        else {
            $('table.dynamic.stock tbody tr').each(function() {
                var _this = $(this);
                var qty = NumberHelper.parseInput($("[data-js='stock_qty']", _this));
                var qty_return = NumberHelper.parseInput($("[data-js='stock_qty_return']", _this));
                var stock_id = $("[data-type='search_stock_row']", _this).attr("data-ac-id");
                var item_name = $("[data-type='search_stock_row']", _this).val();

                if (isNaN(qty_return) || qty_return === undefined)
                    qty_return = 0;

                console.log("ok1", stock_id, qty, qty_return);

                if
                ((((qty === 0 || qty === 0.00 || qty == '') && (qty_return === 0 || qty_return === 0.00 || qty_return == '')) && (stock_id  !== '' && stock_id !== undefined)))
                {
                    event.preventDefault();
                    Swal.fire({
                        icon: "error",
                        title: "Invalid Data!",
                        text: "Qty of "+item_name+" is 0. You can't create invoice!",
                        customClass: {
                            confirmButton: 'color'
                        }
                    });
                    return false;
                }
                else if ((((stock_id  !== '' && stock_id !== undefined) && ((branch_id  === '' || branch_id === undefined)))) && voucher_type_id != 18)
                {
                    event.preventDefault();
                    Swal.fire({
                        icon: "error",
                        title: "Invalid Data!",
                        text: "Warehouse of "+item_name+" is not selected. You can't create invoice!",
                        customClass: {
                            confirmButton: 'color'
                        }
                    });
                    return false;
                }
            });
        }
    });

    if ($("#voucher_id").length)
        $("input[data-type='float']:first-child").trigger("change");
    else if ($("#purchase_order_id").length)
        $("input[data-type='float']:first-child").trigger("change");
    else if ($("#purchase_requisition_id").length)
        $("input[data-type='float']:first-child").trigger("change");
});