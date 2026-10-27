<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Branch Report';
$_GET['_page_title_text'] = 'View / Edit Branches';

include_once("../includes/inc_header.php");

$oBranch = new Branch();
$Select = $oBranch->getReport();
?>
<form action="" method="get">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <? $oBranch->showVoucherButtonsWithOptions([["form_type" => $oBranch->getFormType(), "additional_param" => ""]], [], false); ?>
                <div class="table-responsive">
                <table class="table data-table-report color table-striped">
                    <thead>
                        <tr>
                            <th>Branch Name</th>
                            <th>Branch Email</th>
                            <th>Branch Phone</th>
                            <th>Branch Fax</th>
                            <th>Branch Address</th>
                            <th>Branch Type</th>
                            <th class="text-right"><i class="mdi mdi-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                            <tr>
                                <td><?=$Row['name']?></td>
                                <td><?=$Row['email']?></td>
                                <td><?=$Row['phone']?></td>
                                <td><?=$Row['fax']?></td>
                                <td><?=nl2br($Row['address'])?></td>
                                <td><?=$oBranch->getTypeAbbr($Row['is_warehouse'])?></td>
                                <td class="text-right report-actions">
                                    <? $oBranch->getReportButtons($Row['id'], 'branch_manager.php'); ?>
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