<?php
include_once ("../connection/localhost.php");

$oStockCategory = new StockCategory();
$oStockCategory->initForEdit();
?>
<form method="post" class="auto_account" action="op_auto_save_category.php">
    <div class="row">
        <div class="col-sm-12">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-6">
                    <div class="form-group">
                        <label for="new_name">Name</label>
                        <input type="text" name="new_name" id="new_name" class="form-control form-control-sm" <? UniquenessChecker::checkForUnique("st_stock_category", "name", ''); Utils::isRequired() ?>>
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
        </div>
    </div>
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
            url:SiteURL+"stock_category/op_auto_save_category.php",
            method:"POST",
            data: data,
            dataType:"json",
            success: function(data)
            {
                if(data != '')
                {
                    $('input[data-auto-select="Y"]').attr("data-ac-id", data.category_id);
                    $('input[data-auto-select="Y"]').attr("data-ac-value", data.category_name);
                    $('input[data-auto-select="Y"]').val(data.category_name);
                    $('input[data-auto-select="Y"]').trigger("blur");
                }
            }
        });
    }
</script>
