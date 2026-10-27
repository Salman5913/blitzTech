<?
$_GET['_page_title'] = "Success";
include_once("includes/inc_header.php");
?>
<div class="row status-page">
    <div class="col-md-12 status success">
        <h3><i class="mdi mdi-check-circle-outline"></i> Success</h3>
        <ul>
            <li><?= urldecode($_GET['message']) ?> </li>
        </ul>
    </div>
</div>
<? include_once("includes/inc_footer.php"); ?>