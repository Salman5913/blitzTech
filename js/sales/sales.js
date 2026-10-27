$(function() {

    setTimeout(function () {
        $('input[data-js="customer_id"]').focus();
    }, 500);

    $('#submit').removeClass('hidden');
    var button_default_text = $('[name="submit"]').html();

    $("#invoice_type").on("change", function () {
        var _receipt_div = $("div.receipt_panel");

        if ($(this).val() === 'I') {
            _receipt_div.removeClass("hidden");
        } else {
            _receipt_div.addClass("hidden");
        }
    });

    $("body").on("accountSelected", "[data-js='search_customer_id']", function () {
        $.get("ajax_get_search_order_dropdown.php", { customer_id: $(this).attr("data-ac-id")},
            function(data) {
                $('#order_id').html(data);
            }
        );
    })
    .on("accountSelected", "#customer_id", function () {
        getBalance($(this).attr("data-ac-id"));
        getDiscountPercentage($(this).attr("data-ac-id"));
    })
    /* ************* Stock Rows *********** */
    .on("change", "table.dynamic.stock tbody input[data-type='int'], table.dynamic.stock tbody input[data-type='float']", function() {
        var _this = $(this).closest("tr");

        var qty = NumberHelper.parseInput($("[data-js='qty']", _this));
        var qty_return = NumberHelper.parseInput($("[data-js='qty_return']", _this));
        var rate = NumberHelper.parseInput($("[data-js='rate']", _this));
        var discount_percentage = NumberHelper.parseInput($("[data-js='discount_percentage']", _this));

        if (discount_percentage > 0) // if item has discount Add discount in rate
            rate = rate - (rate * (discount_percentage / 100));

        var row_total = (qty > 0) ? (qty * rate) : Math.abs(qty_return * rate * -1);

        $(".total_row", _this).html(NumberHelper.thousandSep(NumberHelper.round(row_total)));

        setTimeout(function (){
            addMiscCalc();
        }, 500);
    })
    /* ************* Stock Rows *********** */

    /* ************* Non Inventory Rows *********** */
    .on("change", "table.dynamic.non_inventory tbody input[data-type='int'], table.dynamic.non_inventory tbody input[data-type='float']", function() {
        var _this = $(this).closest("tr");

        var qty = NumberHelper.parseInput($("[data-js='ni_qty']", _this));
        var qty_return = NumberHelper.parseInput($("[data-js='ni_qty_return']", _this));
        var rate = NumberHelper.parseInput($("[data-js='ni_rate']", _this));

        var row_total = (qty > 0) ? (qty * rate) : Math.abs(qty_return * rate * -1);

        $(".ni_total_row", _this).html(NumberHelper.thousandSep(NumberHelper.round(row_total)));

        setTimeout(function (){
            addMiscCalc();
        }, 500);
    })
    /* ************* Non Inventory Rows *********** */

    /* ************* Payment Rows *********** */
    .on("change", "table.dynamic.payment tbody input[data-type='int'], table.dynamic.payment tbody input[data-type='float']", function() {
        var _this = $(this).closest("tr");

        var payment_amt = NumberHelper.parseInput($("[data-js='payment_amount']", _this));

        $(".total_payment_row", _this).html(NumberHelper.thousandSep(NumberHelper.round(payment_amt)))
        setTimeout(function (){
            addMiscCalc();
        }, 500);
    });

    $('.sales_calc').on('change', "input[data-type='float']", function (){
        setTimeout(function (){
            addMiscCalc();
        }, 500);
    });

    $('[data-submit-alert="Y"]').click(function(event){
        var $btn = $(this);
        var $form = $btn.closest("form");

        if ($btn.hasClass("disabled") || $btn.prop("disabled") || $form.data("is_submitting") === true) {
            event.preventDefault();
            return false;
        }

        var branch_id = $("[data-js='branch_id']").attr("data-ac-id");
        var first_row_stock = $('table.dynamic.stock tbody tr:eq(0) input[data-type="search_stock_row"]').val();
        var first_row_item_description = $('table.dynamic.non_inventory tbody tr:eq(0) textarea[data-js="item_description"]').val();

        if ((first_row_stock  === '' || first_row_stock === undefined) && (first_row_item_description  === '' || first_row_item_description === undefined))
        {
            event.preventDefault();
            Swal.fire({
                icon: "error",
                title: "Invalid Data!",
                text: "Can not save empty data!",
                customClass: {
                    confirmButton: 'color'
                }
            });
            return false;
        }
        else
        {
            $('table.dynamic.stock tbody tr').each(function() {
                var _this = $(this);
                var qty = NumberHelper.parseInput($("[data-js='qty']", _this));
                var qty_return = NumberHelper.parseInput($("[data-js='qty_return']", _this));
                var stock_id = $("[data-type='search_stock_row']", _this).attr("data-ac-id");
                var item_name = $("[data-type='search_stock_row']", _this).val();

                if (isNaN(qty_return) || qty_return === undefined)
                    qty_return = 0;

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
                else if ((((stock_id  !== '' && stock_id !== undefined) && (branch_id  === '' || branch_id === undefined))) && $("#sales_order_id").val() === '')
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

            $('table.dynamic.non_inventory tbody tr').each(function() {
                var _this = $(this);
                var qty = NumberHelper.parseInput($("[data-js='ni_qty']", _this));
                var qty_return = NumberHelper.parseInput($("[data-js='ni_qty_return']", _this));
                var item_description = $("[data-js='item_description']", _this).val();

                if (isNaN(qty_return) || qty_return === undefined)
                    qty_return = 0;

                if ((((qty === 0 || qty === 0.00 || qty == '') && (qty_return === 0 || qty_return === 0.00 || qty_return == '')) && (item_description  !== '' && item_description !== undefined)))
                {
                    event.preventDefault();
                    Swal.fire({
                        icon: "error",
                        title: "Invalid Data!",
                        text: "Qty of "+item_description+" is 0. You can't create invoice!",
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
        $("input[data-type='float']:first-child").trigger("change");
    }, 100);
});

function addMiscCalc() {
    var button_default_text = $('[name="submit"]').html();

    $("[data-type='int'], [data-type='float']").each(function() {
        if ($(this).val() == '')
            $(this).val(0);
    });

    var total_stock = parseFloat(NumberHelper.clearThousandSep($('[data-input-sum=".total_row"]').html()));
    var total_non_inventory = parseFloat(NumberHelper.clearThousandSep($('[data-input-sum=".ni_total_row"]').html()));
    var total_payment = parseFloat(NumberHelper.clearThousandSep($('[data-input-sum=".total_payment_row"]').html()));


    $(".total_inventory").html(NumberHelper.thousandSep(total_stock));
    $(".total_non_inventory").html(NumberHelper.thousandSep(total_non_inventory));

    if (isNaN(total_stock))
        total_stock = 0;
    if (isNaN(total_non_inventory))
        total_non_inventory = 0;


    var total_gross_sales = total_non_inventory + total_stock;
    $(".total_gross_sales").html(NumberHelper.thousandSep(total_gross_sales));

    // console.log(total_gross_sales, total_non_inventory, total_stock);

    $(".total_payment_amt, .total_receipts").html(NumberHelper.thousandSep(NumberHelper.round(total_payment)));

    var invoice_discount = NumberHelper.calcPercentValue(total_gross_sales, NumberHelper.parseInput($("#invoice_discount_percentage")), NumberHelper.parseInput($("#invoice_discount_amount")));
    $(".total_discount").html(NumberHelper.thousandSep(NumberHelper.round(invoice_discount)));
    $("#calc_discount").val(invoice_discount);

    var gst = NumberHelper.calcPercentValue(total_gross_sales - invoice_discount, NumberHelper.parseInput($("#gst_percentage")), 0);
    $(".total_gst_tax").html(NumberHelper.thousandSep(NumberHelper.round(gst)));
    $("#calc_gst").val(gst);

    var transport_amount = NumberHelper.parseInput($("#transport_amount"))
    $(".total_transport_amount").html(NumberHelper.thousandSep(NumberHelper.round(transport_amount)));

    var total_sales = total_gross_sales - invoice_discount + gst + transport_amount;
    $(".total_sales").html(NumberHelper.thousandSep(total_sales));

    $('#total_invoice_amount').val(total_sales);

    var prev_balance = parseFloat($("#prev_balance").val());
    $(".previous_balance").html(NumberHelper.thousandSep(prev_balance));

    var final_amount = total_sales + prev_balance;
    $(".final_amount").html(NumberHelper.thousandSep(final_amount));

    $(".remaining_balance").html(NumberHelper.thousandSep(final_amount - total_payment)); // After Paid remaining amount

    if (NumberHelper.parseInput($("#credit_limit")) !== 0 && (final_amount - total_payment) > NumberHelper.parseInput($("#credit_limit")) && is_sales_voucher)
        $('[name="submit"]').addClass("bg-warning").html('<i class="mdi mdi-exclamation-thick"></i> Credit Limit Exceeded');
    else
        $('[name="submit"]').removeClass("bg-warning").html(button_default_text);
}
function getBalance(account_id)
{
    $.get("../chart_of_account/ajax_get_balance.php",
        {
            account_id: account_id,
            date_time: $("#voucher_date").val(),
            voucher_type: voucher_type_id,
            voucher_number: $("#voucher_number").val()
        },
        function(data) {
            if (isNaN(parseFloat(data)))
                data = 0;

            $("#prev_balance").val(NumberHelper.round(data));
            $(".previous_balance").html(NumberHelper.thousandSep(data));
        }
    );
}
function getDiscountPercentage(account_id) {
    $.getJSON("../chart_of_account/ajax_get_additional_info.php",
        {
            account_id: account_id
        },
        function(data) {
            var input = $('#invoice_discount_percentage');

            if (input.val() === "0.00" || input.val() === "")
            {
                input.val(data.discount_percentage);
                input.trigger("change");
            }
            $("#credit_limit").val(data.credit_limit);
            $(".credit_limit").html(NumberHelper.thousandSep(data.credit_limit));

            $("#delivery_phone").val(data.phone);
            $("#delivery_address").val(data.address);
        }
    );
}
