<?

include('../connection/localhost.php');

$oSecurityShellGroup = new SecurityShellGroup();
$oSecurityShellGroup->initForEdit();

$SelectAppAreas = $oSecurityShellGroup->getAllAppAreaGroups();
$SelectVoucherTypes = VoucherType::getAllVoucherTypes();

$SelectGroupRoles = $oSecurityShellGroup->getData();
$RowDetails = $oSecurityShellGroup->getDetails();

$SelectPivotTableReports = PivotTableVoucher::getAllReports();

$SelectedAccessArea = [];
$SelectedVoucherTypes = [];
$SelectedVoucherTypesEdits = [];
$SelectedPivotTableIdEdits = [];

while ($RowGroupRoles = DBQM::fetch_assoc($SelectGroupRoles)) {
    $SelectedAccessArea[$RowGroupRoles['area_id']] = $RowGroupRoles['area_id'];
    $SelectedVoucherTypes[$RowGroupRoles['voucher_type_id']] = $RowGroupRoles['voucher_type_id'];
    $SelectedPivotTableIdEdits[$RowGroupRoles['pivot_table_id']] = $RowGroupRoles['pivot_table_id'];

    if (!empty($RowGroupRoles['voucher_type_is_edit']))
        $SelectedVoucherTypesEdits[$RowGroupRoles['voucher_type_id']] = $RowGroupRoles['voucher_type_is_edit'];
}

include('../includes/inc_header.php');
?>
<form method="post" action="op_group_management.php" <? Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Basic Details</h3>
                <div class="row">
                    <div class="col-xl-6 col-md-6">
                        <div class="form-group">
                            <label for="group_name">Name</label>
                            <input type="text" id="group_name" name="group_name" <? $oSecurityShellGroup->checkForUnique("name", $RowDetails['name']); Utils::isRequired()?> />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <h3 class="card-title"> Access Area</h3>
                <ol class="group-management-list">
                    <? $i = 0; while ($RowAppGroup = DBQM::fetch_assoc($SelectAppAreas)) { ?>
                        <li> <? echo $RowAppGroup['main']; ?>
                            <? $SelectAppGroupElement = $oSecurityShellGroup->getAllAppAreaGroupElements($RowAppGroup['main']); ?>
                            <ol type="i">
                                <? while ($RowGroupElement = DBQM::fetch_assoc($SelectAppGroupElement)) { ?>
                                    <li>
                                        <label>
                                            <input type="checkbox" name="area_id[]" <?=(array_key_exists($RowGroupElement['id'], $SelectedAccessArea)) ? ' checked="checked"' : '' ?> value="<? echo $RowGroupElement['id']; ?>" />
                                            <? echo $RowGroupElement['sub']; ?>
                                        </label>
                                    </li>
                                <? } ?>
                            </ol>
                        </li>
                    <? }  ?>
                </ol>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <h3 class="card-title"> Voucher Types</h3>
                <ol class="group-management-list">
                    <? while ($RowVoucherType = DBQM::fetch_assoc($SelectVoucherTypes)) { ?>
                        <li>
                            <label>
                                <input type="checkbox" name="voucher_type_id[]" <?=(array_key_exists($RowVoucherType['id'], $SelectedVoucherTypes)) ? ' checked="checked"' : '' ?> value="<? echo $RowVoucherType['id']; ?>" />
                                <? echo $RowVoucherType['abbr']; ?>
                            </label>
                            <ul>
                                <li><input type="checkbox" name="voucher_type_edit[<?=$RowVoucherType['id']?>]" <?=(array_key_exists($RowVoucherType['id'], $SelectedVoucherTypesEdits)) ? ' checked="checked"' : '' ?> value="<? echo $RowVoucherType['id']; ?>" /> Edit</li>
                            </ul>
                        </li>
                    <? }  ?>
                </ol>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <h3 class="card-title">Customized Reports</h3>
                <ol class="group-management-list">
                    <? while ($RowPivotTable = DBQM::fetch_assoc($SelectPivotTableReports)) { ?>
                        <li>
                            <label>
                                <input type="checkbox" name="pivot_table_report_id[]" <?=(array_key_exists($RowPivotTable['id'], $SelectedPivotTableIdEdits)) ? ' checked="checked"' : '' ?> value="<? echo $RowPivotTable['id']; ?>" />
                                <? echo $RowPivotTable['report_name']; ?>
                            </label>
                        </li>
                    <? }  ?>
                </ol>
            </div>
        </div>
    </div>
    <? $oSecurityShellGroup->showVoucherButtons(); ?>
</form>
<? include('../includes/inc_footer.php'); ?>