<?
include_once("../connection/localhost.php");

$oChartOfAccount = new ChartOfAccount();
$oChartOfAccount->initForEdit();
$oBranch = new Branch();

$RowAccountDetails = $oChartOfAccount->getDetails();

$SelectBioData = $oChartOfAccount->getData(ChartOfAccount::IND_BIO_DATA);
$RowBioData = DBQM::fetch_assoc($SelectBioData);

$SelectHRData = $oChartOfAccount->getData(ChartOfAccount::IND_HR_ACCOUNTS_DATA);
$RowHRData = DBQM::fetch_assoc($SelectHRData);

$SelectBankStatementIdentifierData = $oChartOfAccount->getData(ChartOfAccount::IND_BANK_STATEMENT_IDENTIFIER);
$CountIdentifier = DatabaseHelper::getRecordCount($SelectBankStatementIdentifierData) +1;

$CategoryId = isset($_GET['coa_id']) ? $_GET['coa_id'] : $RowAccountDetails['coa_id'];
$isEmployee = isset($_GET['is_employee']) && $_GET['is_employee'] == 'Y';

if ($oChartOfAccount->isEdit() and empty($RowAccountDetails['id'])) die("Invalid Selection");

include_once("../includes/inc_header.php");
?>
<script src="../js_live/chart_of_account/accounts.min.js" type="text/javascript"></script>
<form method="post" action="op_save_accounts.php" <? Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-7">
            <div class="card">
                <h3 class="card-title"> Basic Details</h3>
                <div class="row justify-content-center">
                    <div class="col-xl-6 col-lg-6">
                        <div class="form-group">
                            <label for="name">Account Name</label>
                            <input type="text" name="name" id="name" class="form-control form-control-sm" <? UniquenessChecker::checkForUnique("acc_accounts", "name", $RowAccountDetails['name']); Utils::isRequired() ?>>
                        </div>
                    </div>
                    <div class="col-md-6 hidden">
                        <div class="form-group">
                            <label for="currency_code">Currency</label>
                            <select name="currency_code" id="currency_code" <? Utils::isRequired(); ?>><? Utils::getDropDownOptionsFromSelect(Currency::getAll(), 'code', 'code', empty($RowAccountDetails['currency_code']) ? ProjectHelper::DEFAULT_CURRENCY : $RowAccountDetails['currency_code']); ?></select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-sm-6">
                                    <label for="category_id">Account Category</label>
                                </div>
                                <div class="col-sm-6 text-right <?=$oChartOfAccount->isEdit() ? 'hidden' : ''?>">
                                    <a href="manage_coa_categories.php" target="_blank" class="shadow-sm button btn-sm bg-soft-warning font-weight-bold"><i class="mdi mdi-plus"></i> New Category</a>
                                </div>
                            </div>
                            <input type="text" name="category_id" class="form-control form-control-sm" id="category_id" <? Autocomplete::ACSearchCOA($CategoryId); Utils::isRequired(); ?>>
                        </div>
                    </div>
                </div>
                <? $oChartOfAccount->showVoucherButtons(); ?>
                <div class="clearfix"></div>
            </div>
        </div>
        <div class="col-sm-5">
            <div class="card">
                <h3 class="card-title"> Hierarchy</h3>
                <div class="hierarchy-table"></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <h3 class="card-title"> Profile Details</h3>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="email1">Email 1</label>
                            <input type="text" name="email1" id="email1" value="<?=$RowBioData['email1']?>" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="email2">Email 2</label>
                            <input type="text" name="email2" id="email2" value="<?=$RowBioData['email2']?>" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="ph1">Phone 1</label>
                            <input type="text" name="ph1" id="ph1" value="<?=$RowBioData['ph1']?>" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="ph2">Phone 2</label>
                            <input type="text" name="ph2" id="ph2" value="<?=$RowBioData['ph2']?>" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="mob">Mobile 1</label>
                            <input type="text" name="mob" id="mob" value="<?=$RowBioData['mob']?>" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="mob2">Mobile 2</label>
                            <input type="text" name="mob2" id="mob2" value="<?=$RowBioData['mob2']?>" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea name="address" id="address"><?=$RowBioData['address']?></textarea>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" name="city" id="city" value="<?=$RowBioData['city']?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <h3 class="card-title"> Other Details</h3>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label for="cnic">CNIC</label>
                            <input type="text" name="cnic" id="cnic" value="<?=$RowBioData['cnic']?>" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="ntn">NTN</label>
                            <input type="text" name="ntn" id="ntn" value="<?=$RowBioData['ntn']?>" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="strn">STRN</label>
                            <input type="text" name="strn" id="strn" value="<?=$RowBioData['strn']?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <h3 class="card-title"> Legal Details</h3>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="credit_limit">Credit Limit</label>
                            <input type="text" name="credit_limit" id="credit_limit" value="<?=$RowBioData['credit_limit']?>" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="sales_disc_per">Sales Disc. %</label>
                            <input type="text" name="sales_disc_per" id="sales_disc_per" value="<?=$RowBioData['sales_disc_per']?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-8 <?=$isEmployee ? '' : 'hidden'?>">
            <div class="card table-hover">
                <h3 class="card-title"> Employee Details</h3>
                <div class="row">
                    <div class="col-md-6">
                        <label for="emp_id">EMP Id </label>
                        <input type="text" name="emp_id" id="emp_id" value="<?=$RowHRData['emp_id']?>" data-old-value="<?=$RowHRData['emp_id']?>" />
                    </div>
                    <div class="col-md-6">
                        <label for="branch_id">Branch</label>
                        <select name="branch_id" id="branch_id"><? $oBranch->getBranchSelectOption($RowHRData['branch_id'])?></select>
                    </div>
                    <div class="col-md-6">
                        <label for="emp_cnic">CNIC</label>
                        <input type="text" name="emp_cnic" id="emp_cnic" value="<?=$RowHRData['cnic']?>" />
                    </div>
                    <div class="col-md-6">
                        <label for="emp_join_date">DOB</label>
                        <input type="text" name="emp_join_date" id="emp_join_date" data-type="date" value="<?=DateDecode($RowHRData['join_date'])?>" />
                    </div>
                    <div class="col-md-6">
                        <label for="basic_salary">Basic Salary </label>
                        <input type="text" name="basic_salary" id="basic_salary" value="<?=$RowHRData['basic_salary']?>" />
                    </div>
                    <div class="col-md-6">
                        <label for="can_overtime">is Calc OT? </label>
                        <select id="can_overtime" name="can_overtime"><? Utils::getDropDownOptionsFromArray(['N'=>'No', 'Y'=>'Yes'], $RowHRData['can_overtime']) ?></select>
                    </div>
                    <div class="col-md-6">
                        <label for="is_active">is Active?</label>
                        <select name="is_active" id="is_active"><? Utils::getDropDownOptionsFromArray(['Y' => 'Yes', 'N' => 'No'], $RowHRData['is_active'])?></select>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card green">
                <h3 class="card-title"> Bank Statement Identifier</h3>
                <table class="table dynamic color">
                    <thead>
                    <tr>
                        <th>Identifier</th>
                    </tr>
                    </thead>
                    <? $i=0; while ($RowIdentifier = DBQM::fetch_assoc($SelectBankStatementIdentifierData) or $i < $CountIdentifier) { ?>
                        <tr>
                            <td><label><input type="text" name="identifier[]" value="<?=$RowIdentifier['identifier']?>"></label></td>
                        </tr>
                        <? $i++; } ?>
                </table>
            </div>
        </div>
    </div>
</form>
<? include_once("../includes/inc_footer.php") ?>