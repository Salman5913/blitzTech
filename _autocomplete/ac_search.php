<?php
include_once("../connection/localhost.php");

$oAutocomplete = new Autocomplete($_GET['autocomplete_type']);
$Select = $oAutocomplete->searchData($_GET['term']);

$Output = array();
$i=0;

while ($Row = DBQM::fetch_assoc($Select))
{
    $Output[$i]['value'] = $Row['col_data'];
    $Output[$i]['id'] = $Row['col_id'];

    $i++;
}

echo json_encode($Output);