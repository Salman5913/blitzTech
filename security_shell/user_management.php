<?

include('../connection/localhost.php');

$oSecurityShellUser = new SecurityShellUser();
$oSecurityShellUser->initForEdit();

$oSecurityShellGroup = new SecurityShellGroup();

$SelectGroups = $oSecurityShellGroup->getAll();

$RowDetails = $oSecurityShellUser->getDetails();
$SelectUserGroups = $oSecurityShellUser->getData();

$GrpArray = array();
while($RowUserGroups = DBQM::fetch_assoc($SelectUserGroups))
{
    $GrpArray[$RowUserGroups['group_id']] = $RowUserGroups['group_id'];
}

include('../includes/inc_header.php');
?>
<form method="post" action="op_user_management.php" <?php Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Basic Details</h3>
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" <? $oSecurityShellUser->checkForUnique("username", $RowDetails['username']); Utils::isRequired(); ?> />
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" value="" />
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6">
                        <div class="form-group">
                            <label for="branch_id">Branch </label>
                            <input type="text" name="branch_id" id="branch_id" <?Autocomplete::Search(Autocomplete::TYPE_BRANCH, $RowDetails['branch_id'])?>>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="form-group">
                            <label for="is_active">is Active?</label>
                            <select name="is_active" id="is_active"><? Utils::getDropDownOptionsFromArray(['Y' => 'Yes', 'N' => 'No'], $RowDetails['is_active']); ?></select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Group Details</h3>
                <ol class="group-management-list">
                    <? while ($RowGroup = DBQM::fetch_assoc($SelectGroups)) { ?>
                    <li>
                        <label>
                            <input type="checkbox" name="group_id[]" value="<?=$RowGroup['id']?>" <?=(array_key_exists($RowGroup['id'], $GrpArray)) ? ' checked="checked"' : ''?> /> <?=$RowGroup['name']?>
                        </label>
                    </li>
                    <? } ?>
                </ol>
            </div>
        </div>
    </div>

    <? $oSecurityShellUser->showVoucherButtons(); ?>
</form>
<? include('../includes/inc_footer.php'); ?>
