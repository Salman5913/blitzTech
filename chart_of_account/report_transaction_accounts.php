<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Transaction Account Report';
$_GET['_page_title_text'] = 'View / Edit Transaction Account';

include_once("../includes/inc_header.php");

$oChartOfAccount = new ChartOfAccount();
$oChartOfAccountCategory = new ChartOfAccountCategory();

$Select = $oChartOfAccount->getReport($_GET['account_id'], $_GET['coa_id'], $_GET['phone'], $_GET['email']);
?>
<form action="" method="get" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <? $oChartOfAccount->showVoucherButtonsWithOptions([
                        ["form_type" => $oChartOfAccount->getFormType(), "additional_param" => "?coa_id=".$_GET['coa_id']."&is_employee=".$_GET['is_employee'], "form_class" => get_class($oChartOfAccount)],
                        ["form_type" => $oChartOfAccountCategory->getFormType(), "additional_param" => "", "form_class" => get_class($oChartOfAccountCategory)]
                ]); ?>
                <div class="row filter hidden">
                    <div class="col-xl-6 col-md-6">
                        <div class="form-group">
                            <label for="account_id">Name</label>
                            <input type="text" id="account_id" name="account_id" <? Autocomplete::ACSearchAccount($_GET['account_id']) ?>>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-3">
                        <div class="form-group">
                            <label for="phone">Mobile # 1</label>
                            <input type="text" id="phone" name="phone" value="<?=$_GET['phone']?>">
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-3">
                        <div class="form-group">
                            <label for="email">Mobile # 2</label>
                            <input type="text" id="email" name="email" value="<?=$_GET['email']?>">
                        </div>
                    </div>
                    <div class="col-xl-6 col-md-6">
                        <div class="form-group">
                            <label for="coa_id">Chart Of Account</label>
                            <input type="text" id="coa_id" name="coa_id" <? Autocomplete::ACSearchCOA($_GET['coa_id']) ?>>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <input type="hidden" name="is_employee" value="<?=$_GET['is_employee']?>">
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
                            <th>Name</th>
                            <th>Category</th>
                            <th class="text-right"><i class="mdi mdi-cogs"></i>  Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? while ($Row = DBQM::fetch_assoc($Select)) {
                            $AdditionalLink = [];

                            if ($Row['coa_id'] == getConstantCategory()->getEmployee())
                                $AdditionalLink['report_salary_history.php?account_id='.$Row['id'].'&search='] = '<i class="mdi mdi-history"></i> Salary History';
                            ?>
                        <tr>
                            <td><?=$Row['name']?></td>
                            <td><?=$Row['category_name']?></td>
                            <td class="text-right report-actions">
                                <? $oChartOfAccount->getReportButtons($Row['id'], 'manage_accounts.php', $AdditionalLink, '&is_employee='.$_GET['is_employee']) ?>
                            </td>
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