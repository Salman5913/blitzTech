$(function() {

    $.get("ajax_get_all_attributes.php", function (data) {
        $(".ajax-table").html(data);

        $('#stock_attributes').dataTable({
            paging: false
        });

    });
});