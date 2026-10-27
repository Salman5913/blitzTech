<?php
include_once("../connection/localhost.php");

if (!ChartOfAccountCategory::isCOAAvailable($_GET['name']))
    echo "Name Already in Use";