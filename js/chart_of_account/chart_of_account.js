$(function() {

    $.get("ajax_get_all_categories.php", function (data) {
        $(".ajax-table").html(data);

        $('#coa_categories').dataTable({
            paging: false
        });

    });

    $("#parent_id").on('coaSelected', function() {
        getHierarchy($(this).attr("data-ac-id"));
    });

    if ($("#voucher_id").length > 0)
        getHierarchy($("#parent_id").attr("data-ac-id"));
});

function getHierarchy(coa_id) {
    $.get("ajax_get_hierarchy.php", {id: coa_id}, function (data) {
        $(".hierarchy-table").html(data);
    });
}