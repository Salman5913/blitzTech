<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 018, 11/18/2014
 * Time: 10:27 AM
 */

if (is_file("../connection/localhost.php"))
    include_once("../connection/localhost.php");
else
    include_once("../../connection/localhost.php");

class ProjectHelper {

    const DEFAULT_CURRENCY = 'PKR';
    const TAX_LABEL = 'GST';
    const HAS_COST_CENTER = true;
    const HAS_PROJECT = true;

    static function SiteURL() {

        if ($_SERVER['SERVER_NAME'] == '10.0.0.172')
            return "http://10.0.0.172/demo_erp/";

        elseif ($_SERVER['SERVER_NAME'] == 'localhost')
            return "http://localhost/demos3/";

        elseif ($_SERVER['SERVER_NAME'] == 'demo.s3.3dm-sols.com')
	        return "https://demo.s3.3dm-sols.com/";

        return "";
    }

    static function allowedServerNames() {
        return ['localhost', '10.0.0.172', 'demo.s3.3dm-sols.com'];
    }

    static function getServer()
    {
        return $_SERVER['SERVER_NAME'];
    }

    static function getFooterNote()
    {
        ?>
        <div class="text-right"><span class="font-italic"><small>Printed on <?=date("M d, Y @ h:ia") ?> by <?=SecurityShell::getLoggedInUsername()?> - FINAC-ERP.com</small></span></div>
        <?
    }
}