<?php
include_once ("../connection/localhost.php");

$SelectModificationList = BasicVoucherArchive::getModificationList($_GET['id'], $_GET['voucher_type_id']);
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <table class="table color table-bordered">
                <thead>
                <tr>
                    <th>Modification Date</th>
                    <th>Modified By</th>
                    <th>View</th>
                </tr>
                </thead>
                <tbody>
                <? while($Row = DBQM::fetch_assoc($SelectModificationList)) { ?>
                    <tr>
                        <td><?=DateDecode($Row['updated_date_time'], 'M d, y @ h:i')?></td>
                        <td><?=SecurityShell::getUserNameById($Row['updated_by'])?></td>
                        <td><a target="_blank" href="<?=ProjectHelper::SiteURL()?>_voucher_defaults/voucher_print.php?voucher_class=<?=$_GET['voucher_class']?>&id=<?=$_GET['id']?>&voucher_type_id=<?=$_GET['voucher_type_id']?>&__archive_view=true&__archive_id=<?=$Row['id']?>">View Voucher</a></td>
                    </tr>
                <? } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

