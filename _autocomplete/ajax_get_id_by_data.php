<?php
include_once("../connection/localhost.php");

$oAutocomplete = new Autocomplete($_GET['autocomplete_type']);
echo $oAutocomplete->getIdByData($_GET['data']);