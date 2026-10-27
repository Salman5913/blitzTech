var timeout_id = null;
$(function() {
    $("body").on("change", "input[data-type='int'], input[data-type='float']", function() {
        $("[data-type='int'], [data-type='float']").each(function () {
            if ($(this).val() == '')
                $(this).val(0);
        });

        var _table_rows = $("table.salary tbody tr");
        _table_rows.each(function () {
            var is_night = $("[data-js='is_night']").val() === 'Y';
            var working_days = $("[data-js='data-working-days']").val();
            var holidays_count = $("[data-js='data-holidays-count']").val();
            var sunday_count = $("[data-js='data-sunday-count']").val();

            var basic_salary = $("[data-js='data-basic-salary']", this).attr('data-basic-salary');
            var absent = $("[data-js='absent']", this).val();
            var actual_absent = absent;

            var is_absent_updated_by_system = $("[data-js='absent']", this).attr('data-is-updated-absent');
            //console.log(is_absent_updated_by_system);

            var absent_company_terms;

            if (is_absent_updated_by_system === 'N')
                absent_company_terms = absent;
            else
                absent_company_terms = $("[data-js='absent']", this).val();

            var present_days = working_days - absent_company_terms;



            var tea_allowance_amount = $("[data-js='tea_allowance']", this).val();
            var attendance_allowance_amount = $("[data-js='attendance_allowance']", this).val();

            var tea_allowance_employee = (working_days - actual_absent - holidays_count - sunday_count) * tea_allowance_amount;
            tea_allowance_employee = tea_allowance_employee > 0 ? tea_allowance_employee : 0;

            $("[data-js='tea_allowance']", this).val(tea_allowance_employee);
            $("[data-js='attendance_allowance']", this).val(absent_company_terms == 0 ? attendance_allowance_amount : 0);


            var attendance_allowance = NumberHelper.parseInput($("[data-js='attendance_allowance']", this));
            var overtime_allowance = NumberHelper.parseInput($("[data-js='overtime_allowance']", this));
            var night_overtime_allowance = NumberHelper.parseInput($("[data-js='night_overtime_allowance']", this));
            var holiday_overtime_allowance = NumberHelper.parseInput($("[data-js='holiday_overtime_allowance']", this));
            var late_hours_deduction = NumberHelper.parseInput($("[data-js='late_hours_deduction']", this));
            var adjustment = NumberHelper.parseInput($("[data-js='adjustment']", this));
            var tea_allowance = NumberHelper.parseInput($("[data-js='tea_allowance']", this));
            var loan_amount = $("[data-js='data-loan-amount']", this).attr("data-loan-amount");
            var loan_deduction = NumberHelper.parseInput($("[data-js='loan_deduction']", this));
            var advance_deduction = NumberHelper.parseInput($("[data-js='advance_deduction']", this));

            var gross_salary = (basic_salary / working_days) * present_days;

            var net_salary = gross_salary + attendance_allowance + (overtime_allowance + night_overtime_allowance + holiday_overtime_allowance) - late_hours_deduction + tea_allowance - loan_deduction - advance_deduction + adjustment;


            $("[data-js='present']", this).html(present_days);
            $("[data-js='gross_salary']", this).html(NumberHelper.thousandSep(Math.round(gross_salary)));
            $("[data-js='net_salary']", this).html(NumberHelper.thousandSep(Math.round(net_salary)));
            $("[data-js='net_salary_input']", this).val(net_salary);
            $("[data-js='loan_remaining']", this).html(NumberHelper.thousandSep(Math.round(loan_amount - loan_deduction - advance_deduction)));

            if (timeout_id !== null)
                clearTimeout(timeout_id);

            timeout_id = setTimeout(function () {
                var total_salary_full = 0;
                var total_gross_salary = 0;
                var total_night_over_time = 0;
                var total_over_time = 0;
                var total_holiday_overtime = 0;
                var total_late = 0;
                var total_adjustment = 0;
                var total_advance_deduction = 0;
                var total_loan_deduction = 0;
                var total_remaining = 0;


                $("[data-js='net_salary_input']").each(function () {
                    total_salary_full += NumberHelper.parseInput($(this));
                });
                $("[data-js='total_salary_full']").html(NumberHelper.thousandSep(Math.round(total_salary_full)));


                $("[data-js='gross_salary']").each(function () {
                    var gross_salary_val = $(this).text().replace(/\,/g,'');
                    total_gross_salary += NumberHelper.parseInput(gross_salary_val);
                });
                $("[data-js='total_gross_salary']").html(NumberHelper.thousandSep(Math.round(total_gross_salary)));


                $("[data-js='night_overtime_allowance']").each(function () {
                    total_night_over_time += NumberHelper.parseInput($(this));
                });
                $("[data-js='total_night_overtime']").html(NumberHelper.thousandSep(NumberHelper.round(total_night_over_time)));


                $("[data-js='holiday_overtime_allowance']").each(function () {
                    total_holiday_overtime += NumberHelper.parseInput($(this));
                });
                $("[data-js='total_holiday_overtime']").html(NumberHelper.thousandSep(NumberHelper.round(total_holiday_overtime)));

                $("[data-js='overtime_allowance']").each(function () {
                    total_over_time += NumberHelper.parseInput($(this));
                });
                $("[data-js='total_overtime']").html(NumberHelper.thousandSep(NumberHelper.round(total_over_time)));


                $("[data-js='late_hours_deduction']").each(function () {
                    total_late += NumberHelper.parseInput($(this));
                });
                $("[data-js='total_late']").html(NumberHelper.thousandSep(Math.round(total_late)));


                $("[data-js='adjustment']").each(function () {
                    total_adjustment += NumberHelper.parseInput($(this));
                });
                $("[data-js='total_adjustment']").html(NumberHelper.thousandSep(Math.round(total_adjustment)));


                $("[data-js='advance_deduction']").each(function () {
                    total_advance_deduction += NumberHelper.parseInput($(this).val());
                });
                $("[data-js='total_advance_deduction']").html(NumberHelper.thousandSep(Math.round(total_advance_deduction)));


                $("[data-js='loan_deduction']").each(function () {
                    total_loan_deduction += NumberHelper.parseInput($(this));
                });
                $("[data-js='total_loan_deduction']").html(NumberHelper.thousandSep(Math.round(total_loan_deduction)));

                $("[data-js='loan_remaining']").each(function () {
                    var remaining_val = $(this).text().replace(/\,/g,'');
                    total_remaining += NumberHelper.parseInput(remaining_val);
                });
                $("[data-js='total_remaining']").html(NumberHelper.thousandSep(Math.round(total_remaining)));

            }, 500);

        });
    });

    $("input[data-type='float']:first-child").trigger("change");
});
