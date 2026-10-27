<?
$_GET['_page_title'] = "Error";
include_once("includes/inc_header.php"); ?>
<div class="row status-page">
    <div class="col-md-12 status error spacer">
        <h3><i class="mdi mdi-alert"></i> Error</h3>
        <ul>
            <li><?= urldecode($_GET['error_msg']) ?> </li>
        </ul>
        <div class="clearfix"></div>
    </div>
</div>
<? include_once("includes/inc_footer.php"); ?>