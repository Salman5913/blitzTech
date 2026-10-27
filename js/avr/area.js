$(function() {

    $.get("ajax_get_all_areas.php", function (data) {
        $(".ajax-table").html(data);

        $('#area_list').dataTable({
            paging: false
        });

    });
});