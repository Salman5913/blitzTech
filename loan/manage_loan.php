<?
include_once("../connection/localhost.php");

$oLoan = new Loan();
$oLoan->initForEdit();
$canDelete = true;

if ($oLoan->isEdit() && $_GET['can_delete'] == 'N')
    $canDelete = false;

$RowLoanDetails = $oLoan->getDetails();
$Type = $_GET['type'] ?? $RowLoanDetails['type'];

include_once("../includes/inc_header.php");
?>
<script src="../js_live/loan/loan.min.js"></script>
<form method="post" action="op_save_loan.php" <? Utils::formValidate() ?>>
    <? $oLoan->showVoucherDeleteNotification(); ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Basic Details</h3>
                <div class="row">
                    <div class="col-md-6">
                        <label for="account_id">Account</label>
                        <input type="text" name="account_id" id="account_id" <?Autocomplete::ACSearchAccountByCOAId([getConstantCategory()->getEmployee()], $RowLoanDetails['account_id'])?> <?Utils::isRequired();?>>
                    </div>
                    <div class="col-md-3">
                        <dl>
                            <dt>EMP Id</dt>
                            <dd class="text-success font-weight-bolder" id="selected_emp_id"></dd>
                        </dl>
                    </div>
                    <div class="col-md-3">
                        <dl>
                            <dt>Branch</dt>
                            <dd class="text-success font-weight-bolder" id="selected_emp_branch"></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <label for="date">Date</label>
                        <input type="text" data-type="date" name="voucher_date" id="date" value="<?=DateDecode($RowLoanDetails['voucher_date'])?>" <? Utils::isRequired() ?>>
                    </div>
                    <? CostCenter::designVoucherField($RowLoanDetails['cost_center_id'] ?? '', $oLoan->isEdit(), 'col-md-6'); ?>
                    <div class="col-md-6">
                        <label for="amount">Loan/Advance Amount</label>
                        <input type="text" data-type="int" name="amount" id="amount" value="<?=$RowLoanDetails['amount']?>" <? Utils::isRequired() ?>>
                    </div>
                    <div class="col-md-6">
                        <label for="cash_bank_id">Cash / Bank</label>
                        <input type="text" name="cash_bank_id" id="cash_bank_id" <?Autocomplete::ACSearchAccountByCOAId([getConstantCategory()->getCashInHand(), getConstantCategory()->getBank()], $RowLoanDetails['cash_bank_id'])?>>
                    </div>
                    <div class="col-md-6">
                        <label for="monthly_installment">Monthly Installment</label>
                        <input type="text" data-type="int" name="monthly_installment" id="monthly_installment" value="<?=$RowLoanDetails['monthly_installment']?>" <? Utils::isRequired() ?>>
                    </div>
                    <div class="col-md-6">
                        <label for="type">Type</label>
                        <select id="type" name="type"><?Utils::getDropDownOptionsFromArray(['L' => 'Loan', 'A' => 'Advance'], $Type)?></select>
                    </div>
                </div>
            </div>
            <? $oLoan->showVoucherButtons(); ?>
        </div>
    </div>
</form>
<? include_once("../includes/inc_footer.php") ?>