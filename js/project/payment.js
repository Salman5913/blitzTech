
$(function() {
    $("#_project_id").on("approvedProjectSelected", function () {
        $.get(SiteURL + "project/ajax_get_account_name_by_project_id.php", { project_id: $(this).attr("data-ac-id")},
            function(data) {
                $('#account_name').val(data);
            }
        );
    });
});