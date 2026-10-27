<?php
include_once ("../connection/localhost.php");

SecurityShell::redirectIfNotLoggedIn();

if (isset($_POST['stage_id'])) {
    CRMInquiry::updateSortNumber($_POST['lead_id'], $_POST['stage_id']);
}