<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Stock Selection';
$_GET['_page_title_text'] = 'Stock Manufacture';

if (isset($_GET['id']))
    Utils::redirect(ProjectHelper::SiteURL() . "Controller/" . VoucherType::getVoucherClass(VoucherType::BMV)."?voucher_type_id=".VoucherType::BMV."&id=".$_GET['id']);

include_once("../includes/inc_header.php");
?>
<form action="<?=ProjectHelper::SiteURL() . "Controller/" . VoucherType::getVoucherClass(VoucherType::BMV)?>" method="get" <? Utils::formValidate() ?>>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <h3 class="card-title">Finish Good Produced</h3>
                <div class="row">
                    <div class="col-xl-4 col-lg-4">
                        <div class="form-group">
                            <label for="finish_stock_id">Finish Good Name</label>
                            <input type="text" class="form-control" name="finish_stock_id" id="finish_stock_id" <? Autocomplete::ACSearchStock('', ''); Utils::isRequired() ?>>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4">
                        <div class="form-group">
                            <label for="finish_qty">Finish Qty</label>
                            <input type="text" class="form-control" name="finish_qty" id="finish_qty" data-type="float" value="<?=$_GET['finish_qty']?>" <? Utils::isRequired() ?>>
                        </div>
                    </div>
                    <div class="col-sm-12 text-right">
                        <button type="submit" class="btn button color" name="search" id="search"><i class="mdi mdi-file-document-box-search"></i> Go To Process</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="voucher_type_id" value="<?=VoucherType::BMV?>">
</form>
<? include_once("../includes/inc_footer.php"); ?>