<?
$_GET['_page_title'] = 'Cost Center Report';
$_GET['_page_title_text'] = 'View / Edit Cost Centers';

include_once("../includes/inc_header.php");

$oCostCenter = new CostCenter();
$Select = $oCostCenter->getSearchQuery();
?>
<form action="" method="get">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Search Results</h3>
                <div class="table-responsive">
                <table class="table data-table-report color table-striped">
                    <thead>
                        <tr>
                            <th>Cost Center Name</th>
                            <th class="text-right"><i class="mdi mdi-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                            <tr>
                                <td><?=$Row['cost_center_name']?></td>
                                <td class="text-right report-actions">
                                    <? $oCostCenter->getReportButtons($Row['id'], 'cost_center_manager.php'); ?>
                                </td>
                            </tr>
                        <? } ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</form>
<? include_once("../includes/inc_footer.php") ?>