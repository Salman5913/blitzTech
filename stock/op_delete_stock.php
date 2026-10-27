<?php
include_once ('../connection/localhost.php');

$oStock = new Stock();

if (isset($_GET['id']))
{
    $oStock->delete($_GET['id']);
    SuccessHandler::setMessage("Stock Deleted Successfully");
    Utils::redirect('report_stock.php?item_name='.$_POST['name'].'&color=&size=&show_on_web=&search=&DataTables_Table_0_length=10');
}