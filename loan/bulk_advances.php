<?
include_once("../connection/localhost.php");

$oLoan = new Loan();
$oLoan->initForEdit();

$RowLoanDetails = $oLoan->getDetails();

$oChartOfAccount = new ChartOfAccount();
$SelectEmployee = $oChartOfAccount->getAllEmployeeList();

include_once("../includes/inc_header.php");
?>
    <script src="../js_live/loan/loan.min.js"></script>
    <form method="post" action="op_save_bulk_advances.php" id="bulk_advances" <? Utils::formValidate() ?>>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <h3 class="card-title"> Basic Details</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="voucher_date">Date</label>
                            <input type="text" data-type="date" name="voucher_date" id="voucher_date" value="<?=DateDecode($RowLoanDetails['date'])?>" <? Utils::isRequired() ?>>
                        </div>
                        <? CostCenter::designVoucherField('', false, 'col-md-6'); ?>
                        <div class="col-md-6">
                            <label for="cash_bank_id">Advance Given From</label>
                            <input type="text" name="cash_bank_id" id="cash_bank_id" class="large" <?Autocomplete::ACSearchAccountByCOAId([getConstantCategory()->getCashInHand(), getConstantCategory()->getBank()],$RowLoanDetails['cash_bank_id']); Utils::isRequired(); ?>>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="row">
                        <div class="col-auto mr-auto">
                            <h3 class="card-title"> Employee List</h3>
                        </div>
                        <div class="col-auto ml-auto">
                            <? UtilsPrint::button() ?>
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" name="search_data" id="search_data">
                                    <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fa fa-search"></i>
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                    <table id="table" class="table data-table-report table-striped color">
                        <thead>
                        <tr>
                            <th>Account</th>
                            <th>EMP Id</th>
                            <th>EMP Branch</th>
                            <th>Advance Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        <? while($RowEmployee = DBQM::fetch_assoc($SelectEmployee)) { ?>
                            <tr>
                                <td><?=$RowEmployee['name']?></td>
                                <td><?=$RowEmployee['emp_id']?></td>
                                <td><?=$RowEmployee['branch_name']?></td>
                                <td><label><input type="text" data-type="float" name="advance_amount[<?=$RowEmployee['account_id']?>]" data-js="advance" value=""></label></td>
                            </tr>
                        <? } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">&nbsp;</td>
                                <td class="font-weight-bolder" data-js="total_advances_amount" <? Utils::sumInputs('[data-js="advance"]');?>></td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>
                </div>
            </div>
        </div>
        <? $oLoan->showVoucherButtons(); ?>
    </form>

    <script>
        $(function () {
            $('#table').DataTable( {
                destroy: true,
                searching: false
            } );
            $("#search_data").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("table tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            $("table.data-table-report").dataTable().fnLengthChange( -1 );
        });
    </script>
<? include_once("../includes/inc_footer.php") ?>