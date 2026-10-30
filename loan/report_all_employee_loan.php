<?
$_GET['_page_title'] = 'All Employee Loan Report';
$_GET['_page_title_text'] = 'View / Edit Loans';

include_once("../includes/inc_header.php");

$oLoan = new Loan();
$oBranch = new Branch();

$SelectEmployeeLoans = $oLoan->getAllEmpLoans('', true, $_GET['branch_id'], DateEncode($_GET['date_till']), $_GET['cost_center_id'] ?? '');
?>
<div class="row">
    <div class="col-sm-12">
        <form action="" method="get" <? Utils::formValidate() ?>>
            <div class="card">
                <h3 class="card-title"> Filters</h3>
                <div class="row">
                    <div class="col-md-6">
                        <label for="branch_id">Branch</label>
                        <select id="branch_id" name="branch_id"><option value="">All</option><? $oBranch->getBranchSelectOption($_GET['branch_id']) ?></select>
                    </div>
                    <div class="col-md-6">
                        <label for="date_till">Date</label>
                        <input type="text" data-type="date" id="date_till" name="date_till" value="<?=DateDecode($_GET['date_till'])?>">
                    </div>
                    <? CostCenter::designReportFilter('col-md-6'); ?>
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="fa fa-search"></i> Search</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
	<div class="col-sm-12">
		<div class="card">
			<h3 class="card-title"> Search Results</h3>
			<div class="table-responsive">
                <table class="table data-table-report color table-striped">
                    <thead>
                    <tr>
                        <th>Account</th>
                        <th>EMP Id</th>
                        <th>Branch</th>
                        <th class="text-center">Installment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?
                    while ($Row = DBQM::fetch_assoc($SelectEmployeeLoans)) {
                        if ($Row['total_loan'] - $Row['total_paid'] == 0) continue;
                        ?>
                        <tr>
                            <td><?=$Row['name']?></td>
                            <td><?=$Row['emp_id']?></td>
                            <td><?=$Row['branch_name']?></td>
                            <td class="text-right"><?=Utils::format($Row['total_loan'] - $Row['total_paid'], 0)?></td>
                        </tr>
                    <? } ?>
                    </tbody>
                </table>
            </div>
		</div>
	</div>
</div>
<? include_once("../includes/inc_footer.php") ?>