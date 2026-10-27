<?php
include_once ("../connection/localhost.php");
$oChartOfAccount = new ChartOfAccount();
$oChartOfAccount->initForEdit();
?>
<form method="post" class="auto_account" action="op_auto_save_accounts.php">
    <div class="row justify-content-center">
        <div class="col-xl-6 col-lg-6">
            <div class="form-group">
                <label for="new_name">Account Name</label>
                <input type="text" name="new_name" id="new_name" class="form-control form-control-sm" <? UniquenessChecker::checkForUnique("acc_accounts", "name", ''); Utils::isRequired() ?>>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="new_category_id">Account Category</label>
                <input type="text" name="new_category_id" class="form-control form-control-sm" id="new_category_id" <? Autocomplete::ACSearchCOA($_GET['category_id']); Utils::isRequired(); ?>>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-group">
                <label for="new_mob">Account Mobile1</label>
                <input type="text" name="new_mob" id="new_mob" value="" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-group">
                <label for="new_email1">Account Email1</label>
                <input type="text" name="new_email1" id="new_email1" value="" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-group">
                <label for="new_cnic">Account CNIC</label>
                <input type="text" name="new_cnic" id="new_cnic" value="" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-group">
                <label for="new_ntn">Account NTN</label>
                <input type="text" name="new_ntn" id="new_ntn" value="" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-group">
                <label for="new_city">Account City</label>
                <input type="text" name="new_city" id="new_city" value="" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-group">
                <label for="new_address">Account Address</label>
                <textarea name="new_address" id="new_address"></textarea>
            </div>
        </div>
    </div>
    <div class="row form-group voucher-buttons">
        <div class="col-auto ml-auto">
            <div class="btn-group">
                <button class="btn button color" name="submit" id="btn_auto_save" type="submit"><i class="mdi mdi-content-save"></i> Save</button>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
</form>
<script>
    $(function (){
       $("#btn_auto_save").on("click", function (event){
           event.preventDefault();

           saveData();
       });
    });
    function saveData()
    {

        FormHelper.nameToIdConvert();
        var data = $(".auto_account").serialize();

        FormHelper.idToNameConvert();

        $.ajax({
            url:SiteURL+"chart_of_account/op_auto_save_accounts.php",
            method:"POST",
            data: data,
            dataType:"json",
            success: function(data)
            {
                if(data != '')
                {
                    $('input[data-auto-select="Y"]').attr("data-ac-id", data.account_id);
                    $('input[data-auto-select="Y"]').attr("data-ac-value", data.account_name);
                    $('input[data-auto-select="Y"]').val(data.account_name);
                    $('input[data-auto-select="Y"]').trigger("blur");
                }
            }
        });
    }
</script>
