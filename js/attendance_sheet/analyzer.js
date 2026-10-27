$(function () {
    $("input#select_all_none").click( function() {
        $('input:checkbox').not(this).prop('checked', this.checked);
    });
});
