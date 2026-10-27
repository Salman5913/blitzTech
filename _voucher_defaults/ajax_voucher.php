<?php
include_once ("../connection/localhost.php");

$_GET['is_ajax_voucher_call'] = true;

ob_start();
$oVoucherDesigner = new VoucherDesigner();
$oVoucherDesigner->formDesigner(true, false);
$Content = ob_get_clean();

$Output = [];
$Output['data'] = $Content;
$Output['voucher_name'] = $_GET['_page_title'];
$Output['is_view_only'] = isset($_GET['view']) ? 'y' : 'n';

echo json_encode($Output);