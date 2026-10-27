$(function() {

    $.get("ajax_get_all_categories.php", function (data) {
        $(".ajax-table").html(data);

        $('#stock_categories').dataTable({
            paging: false
        });

    });

    $("#parent_id").autocomplete({
        source: "ac_category_search.php",
        minLength: 0,
        select: function(e, ui) {
            AutoCompleteHandler.setAutoCompleteOnSelectValues(this, ui);
            $(this).removeClass("not-allowed").focus();

            getHierarchy(ui.item.id);
        }
    });

    if ($("#voucher_id").length > 0)
        getHierarchy($("#parent_id").attr("data-ac-id"));
});

function getHierarchy(stock_id) {
    $.get("ajax_get_hierarchy.php", {id: stock_id}, function (data) {
        $(".hierarchy-table").html(data);
    });
}