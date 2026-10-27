<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Security Groups';
$_GET['_page_title_text'] = 'Displaying All Groups';

include('../includes/inc_header.php');

$oSecurityGroup = new SecurityShellGroup();
$SelectGroups = $oSecurityGroup->getAll();
?>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <? $oSecurityGroup->showVoucherButtonsWithOptions([["form_type" => $oSecurityGroup->getFormType(), "additional_param" => ""]], [], false); ?>
            <table class="table table-striped">
                <? while ($RowGroups = DBQM::fetch_assoc($SelectGroups)) { ?>
                <tr>
                    <td><?=$RowGroups['name']?></td>
                    <td><?=$RowGroups['userlist']?></td>
                    <td class="text-right">
                        <? $oSecurityGroup->getReportButtons($RowGroups['id'], 'group_management.php'); ?>
                    </td>
                </tr>
                <? } ?>
            </table>
        </div>
    </div>
</div>
<? include('../includes/inc_footer.php'); ?>