<?php
include_once ("../connection/localhost.php");

$Output = [];

if (UniquenessChecker::isUnique($_GET['table_name'], $_GET['column_name'], $_GET['new_value']))
    $Output['exist'] = 'N';
else
    $Output['exist'] = 'Y';

echo json_encode($Output);