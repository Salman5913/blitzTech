<?php
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'CRM Dashboard';
$_GET['_page_title_text'] = '<a '.Utils::AjaxLoadedVoucher(VoucherType::CRMI).' class="btn bg-white text-color"><i class="mdi mdi-plus"></i> New Inquiry</a>';


SecurityShell::redirectIfNoAccess("CRM", "Dashboard");

$oCRMReport = new CRMReport();
$SelectLeads = $oCRMReport->getLeads();
$SelectStages = $oCRMReport->getAllStages(false);

include_once ("../includes/inc_header.php");
?>
<section class="crm-dashboard">
    <div class="scrollable-row">
        <? while ($RowStages = DBQM::fetch_assoc($SelectStages)) { ?>
            <div class="card stage">
                <h3 class="card-title"><?=$RowStages['stage_name']?></h3>
                <ul class="sortable p-5" data-stage="<?=$oCRMReport->cleanStageName($RowStages['stage_name'])?>" data-stage-id="<?=$RowStages['id']?>">

                </ul>
            </div>
        <? } ?>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="bin-card card stage">
                <h3 class="card-title">Trash</h3>
                <ul class="sortable" data-stage="bin" data-stage-id="6"></ul>
            </div>
        </div>
    </div>
    <div class="row hidden">
        <div class="col-12">
            <?
            while ($RowInquiry = DBQM::fetch_assoc($SelectLeads))
                $oCRMReport->getPanelHTML($RowInquiry);
            ?>
        </div>
    </div>
</section>

<script>
$(function() {
    $("[data-crm-stage]").each(function () {
        var _this = $(this);
        var stage = _this.attr("data-crm-stage");

        $("ul.sortable[data-stage='"+stage+"']").append(_this);
    });

    $(".sortable").sortable({
        connectWith: ".sortable",
        placeholder: "ui-state-highlight",
        update: function (event, ui) {
            // Get the updated order of items in this list
            const updatedOrder = $(this)
                .children("li")
                .map(function () {
                    return $(this).data("id"); // Retrieve the unique data-id of each item
                })
                .get();
            console.log(updatedOrder);
            const stage = $(this).data("stageId"); // Get the current stage of this list

            // Save the updated order to the database via AJAX
            $.ajax({
                url: SiteURL+"crm/op_update_sort_number.php", // Replace with your API endpoint
                method: "POST",
                data: {
                    stage_id: stage, // Send the stage to identify the column
                    lead_id: updatedOrder, // Send the updated order of IDs
                },
                success: function (response) {
                },
                error: function (error) {
                    Notifications.showTemporary("Error saving sorting!", "fail");
                },
            });
        },
        receive: function (event, ui) {
            // Update the stage value of the lead when it is dropped
            const leadId = ui.item.data("id");
            const newStage = $(this).data("stageId");

            // Optional: Save the updated stage to the database via AJAX
            $.ajax({
                url: SiteURL+"crm/op_update_stage.php", // Replace with your endpoint
                method: "POST",
                data: {
                    lead_id: leadId,
                    stage_id: newStage,
                },
                success: function (response) {
                    Notifications.showTemporary("Stage updated!", "success");
                },
                error: function (error) {
                    Notifications.showTemporary("Error updating stage!", "fail");
                },
            });
        }
    });

    App.autoRefreshPageInSetMin();
});
</script>
<?php
include_once ("../includes/inc_footer.php");