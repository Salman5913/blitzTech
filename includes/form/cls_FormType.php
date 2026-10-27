<?php

class FormType
{
    static function getLink($Type, $ClassName='')
    {
        $Link = "";

        switch ($Type)
        {
            case "Transaction Account";
                $Link = "chart_of_account/manage_accounts.php";
                break;

            case "Chart Of Account";
                $Link = "chart_of_account/manage_coa_categories.php";
                break;

            case "Branch";
                $Link = "branch/branch_manager.php";
                break;

            case "Cost Center";
                $Link = "costcenter/cost_center_manager.php";
                break;

            case "Inventory Profile";
                $Link = "stock/stock_management.php";
                break;

            case "Stock Category";
                $Link = "stock_category/manage_stock_category.php";
                break;

            case "Stock Attribute";
                $Link = "stock_attribute/stock_attr_manager.php";
                break;

            case "Attendance Log";
                $Link = "attendance_sheet/manage_logs.php";
                break;

            case "Application User Management";
                $Link = "security_shell/user_management.php";
                break;

            case "SECURITY GROUP MANAGEMENT";
                $Link = "security_shell/group_management.php";
                break;
        }

        return ProjectHelper::SiteURL().$Link;
    }
}