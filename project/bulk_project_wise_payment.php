<?
$_GET['_page_title'] = 'Advance Information';
$_GET['_page_title_text'] = 'Create / Update Advance Information';

include_once("../connection/localhost.php");

$oProjectWisePayment = new ProjectWisePayment();
$oProjectWisePayment->initForEdit();

$SelectProjectDetails = false;
$ProjectAmount = $TotalRemainingAmount = 0;

if (isset($_GET['search']))
    $SelectProjectDetails = $oProjectWisePayment->getRemainingProjectsList($_GET['account_id']);

include_once("../includes/inc_header.php");
?>
<link href="../assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
<script src="../js_live/project/bulk_payment.min.js"></script>
<script src="../assets/libs/sweetalert2/sweetalert2.min.js"></script>
<style>
    .swal2-popup {
        font-size: 1.6rem !important;
        font-family: Georgia, serif;
    }
</style>
<form method="get" action="" <? Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Filters</h3>
                <div class="row">
                    <div class="col-md-6">
                        <label for="account_id">Account</label>
                        <input type="text" name="account_id" id="account_id" <?Autocomplete::ACSearchAccount($_GET['account_id']); Utils::isRequired(); ?>>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-12 text-right">
                        <input type="hidden" name="voucher_type_id" value="<?=$_GET['voucher_type_id']?>">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
    <form method="post" action="op_save_bulk_payments.php" id="bulk_advances" <? Utils::formValidate() ?>>
        <div class="row">
            <div class="col-sm-8">
                <div class="card">
                    <h3 class="card-title"> Basic Details</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="voucher_date">Date</label>
                            <input type="text" data-type="date" name="voucher_date" id="voucher_date" value="" >
                            <input type="hidden" name="voucher_type_id" value="<?=$_GET['voucher_type_id']?>">
                        </div>
                        <? CostCenter::designVoucherField('', false, 'col-md-4'); ?>
                        <div class="col-md-4">
                            <label for="cash_bank_id">Cash / Bank</label>
                            <input type="text" name="cash_bank_id" id="cash_bank_id" <?Autocomplete::ACSearchAccountByCOAId([getConstantCategory()->getCashInHand(), getConstantCategory()->getBank()], ''); Utils::isRequired(); ?>>
                        </div>
                        <div class="col-md-4">
                            <label for="payment_type">Payment Type</label>
                            <input type="text" name="payment_type" id="payment_type" <?Autocomplete::Search(Autocomplete::TYPE_PROJECT_PAYMENT_TYPE, ''); Utils::isRequired(); ?>>
                        </div>
                        <div class="col-md-4">
                            <label for="amount">Amount</label>
                            <input type="text" name="amount" id="amount" data-type="float" value="">
                        </div>
                        <div class="col-md-4">
                            <label for="description">description</label>
                            <textarea name="description" id="description"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <h3 class="card-title"> Cheque Details</h3>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="cheque_date">Cheque Date</label>
                            <input type="text" data-type="date" name="cheque_date" id="cheque_date" value="" >
                        </div>
                        <div class="col-md-12">
                            <label for="cheque_bank">Cheque Bank</label>
                            <input type="text" name="cheque_bank" id="cheque_bank" value="">
                        </div>
                        <div class="col-md-12">
                            <label for="cheque_number">Cheque Number</label>
                            <input type="text" name="cheque_number" id="cheque_number" value="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <h3 class="card-title"> Project List</h3>
                    <div class="table-responsive">
                        <table id="table" class="table table-striped color">
                            <thead>
                            <tr>
                                <th width="50%">Project</th>
                                <th width="17" class="text-right">Invoiced</th>
                                <th width="17" class="text-right">Received</th>
                                <th width="17" class="text-right">Remaining</th>
                                <th width="17" class="text-right">Adjusted</th>
                            </tr>
                            </thead>
                            <tbody>
                            <? while($RowProject = DBQM::fetch_assoc($SelectProjectDetails)) {
                                $RemainingAmount = $RowProject['amount'] - $RowProject['received_amount'];

                                if ($RemainingAmount <= 0 AND $RowProject['amount'] > 0)
                                    continue;

                                $ProjectAmount += $RowProject['amount'];
                                $TotalRemainingAmount += $RemainingAmount;
                                ?>
                                <tr>
                                    <td><?=$RowProject['ac_project']?></td>
                                    <td class="text-right" data-amount="<?=$RowProject['amount']?>" data-js="amount"><?=Utils::format($RowProject['amount'])?></td>
                                    <td class="text-right"><?=Utils::format($RowProject['received_amount'])?></td>
                                    <td class="text-right" data-remaining_amount="<?=$RemainingAmount?>" data-js="remaining_amount"><?=Utils::format($RemainingAmount)?></td>
                                    <td class="text-right"><label><input type="text" data-type="float" name="project_amount[<?=$RowProject['id']?>]" class="project_amount" value=""></label></td>
                                </tr>
                            <? } ?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td></td>
                                <td class="font-weight-bolder text-right"><?=Utils::format($ProjectAmount)?></td>
                                <td></td>
                                <td class="font-weight-bolder text-right"><?=Utils::format($TotalRemainingAmount)?></td>
                                <td class="font-weight-bolder text-right" data-js="total_project_amount" <? Utils::sumInputs('.project_amount');?>></td>
                            </tr>
                            <tr>
                                <td class="text-right" data-js="total_received_amount"> </td>
                                <td></td>
                                <td>&nbsp;Total Adjusted: <span <? Utils::sumInputs('.project_amount');?> class="text-right font-weight-bold"></span></td>
                                <td class="text-right"> Balance: <span class="font-weight-bolder text-right" data-js="amount_balance"></span></td>
                                <td data-js="balance_error"></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <? $oProjectWisePayment->showVoucherButtons(); ?>
    </form>
<? include_once("../includes/inc_footer.php") ?>