<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Employee Salary History Report';
$_GET['_page_title_text'] = 'View Employee Wise & Month Wise Complete Salary List';

include_once("../includes/inc_header.php");

$oChartOfAccount = new ChartOfAccount();
$Select = false;

if (isset($_GET['search']))
{
    $Select = $oChartOfAccount->getSalaryHistory($_GET['account_id']);
}
?>
<form action="" method="get" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Filters</h3>
                <div class="row">
                    <div class="col-xl-6 col-md-6">
                        <div class="form-group">
                            <label for="account_id">Account Name</label>
                            <input type="text" id="account_id" name="account_id" <? Autocomplete::ACSearchAccountByCOAId([getConstantCategory()->getEmployee()], $_GET['account_id']) ?>>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn button color d-block" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title"> Search Results</h3>
                <div class="table-responsive">
                <table class="table data-table-report color table-striped">
                    <thead>
                        <tr>
                            <th width="50%">Name</th>
                            <th width="25%">Updated Date</th>
                            <th width="25%" class="text-center">Basic Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? while ($Row = DBQM::fetch_assoc($Select)) { ?>
                        <tr>
                            <td><?=$Row['name']?></td>
                            <td><?=DateDecode($Row['date_time'], "M d, Y")?></td>
                            <td class="text-center"><?=Utils::format($Row['salary'])?></td>
                        </tr>
                        <? } ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</form>
<? include_once("../includes/inc_footer.php") ?>