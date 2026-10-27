$(function() {

    $("table").on("change", "input[data-type='int'], input[data-type='float']", function() {

        $("[data-type='int'], [data-type='float']").each(function () {
            if ($(this).val() == '')
                $(this).val(0);
        });

        var working_hours = parseFloat($("[data-js='working_hours']").val());
        
        var deduction = 0;
        var final_amount = 0;
        var total_salary = 0;
        var total_hours_work = 0;
        var total_deduction = 0;
        var total_final_amount = 0;
        // *************** Salary**************** //
        $("table.salary tbody tr").each(function() {

            var salary = parseInt($("[data-js='salary']", this).val());
            var hours_work = parseFloat($("[data-js='hours_work']", this).val());
            var increment_decrement = parseFloat($("[data-js='increment_decrement']", this).val());

            deduction = (salary - ((salary / working_hours) * hours_work));
            final_amount = (salary - deduction);

            if (increment_decrement > 0)
                final_amount += increment_decrement;
            else
                final_amount -= increment_decrement;

            total_salary += salary;
            total_hours_work += hours_work;
            total_deduction += deduction;
            total_final_amount += final_amount;

            $(".total_row_deduction", this).html(NumberHelper.thousandSep(Math.round(deduction)));
            $(".total_row_final", this).html(NumberHelper.thousandSep(Math.round(final_amount)));
        });

        $(".total_salary").html(NumberHelper.thousandSep(NumberHelper.round(total_salary)));
        $(".total_hours_work").html(NumberHelper.thousandSep(NumberHelper.round(total_hours_work)));
        $(".total_deduction").html(NumberHelper.thousandSep(NumberHelper.round(total_deduction)));
        $(".total_final_amount").html(NumberHelper.thousandSep(NumberHelper.round(total_final_amount)));
        // *************** Salary **************** //

    });
});
