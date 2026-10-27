<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Application Users';
$_GET['_page_title_text'] = 'Displaying All Users';

include('../includes/inc_header.php');

$oSecurityShellUser = new SecurityShellUser();
$SelectUsers = $oSecurityShellUser->getAll();
?>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <? $oSecurityShellUser->showVoucherButtonsWithOptions([["form_type" => $oSecurityShellUser->getFormType(), "additional_param" => ""]], [], false); ?>
            <table class="table table-striped color">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Group</th>
                        <th>is Active?</th>
                        <th class="text-right"> Actions</th>
                    </tr>
                </thead>
                <? while ($RowUsers = DBQM::fetch_assoc($SelectUsers)) { ?>
                    <tr class="<?=$RowUsers['is_active']=='Y' ? 'bg-soft-success' : '' ?>">
                        <td><?=$RowUsers['username'] ?></td>
                        <td><?=$RowUsers['group_names'] ?></td>
                        <td><?=$RowUsers['is_active'] == 'Y' ? 'Yes' : 'No'?></td>
                        <td class="text-right"><? $oSecurityShellUser->getReportButtons($RowUsers['id'], 'user_management.php') ?></td>
                    </tr>
                <? } ?>
            </table>
        </div>
    </div>
</div>
<? include('../includes/inc_footer.php'); ?>