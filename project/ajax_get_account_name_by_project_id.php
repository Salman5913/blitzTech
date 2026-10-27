<?php
include_once ("../connection/localhost.php");

$oProject = new Project();
echo $oProject->getAccountNameByProjectId($_GET['project_id']);
