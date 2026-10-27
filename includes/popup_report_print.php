<style>
    .table thead tr th {
        font-weight: bold !important;
    }
    table{
        width: 1000px!important;
        margin-left: -10px;
        zoom: 100%;
    }
</style>
<?
include_once("inc_popup_header.php");
?>
<div class="row">
    <div class="col-12 mb-3">
        <table width="100%">
            <tr>
                <td width="80%">
                    <h3 class="popup-header">Report</h3>
                    <p class="short-description"></p>
                </td>
            </tr>
        </table>
    </div>
</div>
<div class="row">
    <div class="col-sm-12 table-data">

    </div>
    <div class="col-sm-12 text-center">
        <? ProjectHelper::getFooterNote() ?>
    </div>
</div>

<? include_once("inc_popup_footer.php") ?>
<? UtilsPrint::childInit() ?>


<!--

/*.page-headers, .page-header-space {
    height: 90px;
    margin-top: 5px;
    width: 100%;
}
.page-footer, .page-footer-space {
    height: 35px;
}
.page-headers {
    position: fixed;
    top: 0;
}
.page-footer {
    position: fixed;
    bottom: 0;
}

.page {
    page-break-after: always;
}

@media print {
    thead {display: table-header-group;}
    tfoot {display: table-footer-group;}
}*/

<table width="100%">
    <thead>
    <tr>
        <td>
            <div class="page-header-space"></div>
        </td>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>

        </td>
    </tr>
    </tbody>
    <tfoot>
    <tr>
        <td>
            <div class="page-footer-space"></div>
        </td>
    </tr>
    </tfoot>
</table>
<div class="page-footer"></div>-->

