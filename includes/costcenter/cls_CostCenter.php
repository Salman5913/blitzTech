<?php

class CostCenter extends BasicForm
{
    public function __construct()
    {
        parent::__construct(
            "cc_cost_center",
            "",
            "",
            "Cost Center"
        );
    }

    function getSearchQuery(): mysqli_result
    {
        return DBQM::query("
            SELECT 
                cc.*
            FROM
                cc_cost_center cc
        ");
    }

    static function isSingleCostCenterAssigned()
    {
        $Select = DBQM::query("SELECT * FROM sh_user_cost_center WHERE user_id = ".escape(SecurityShell::getLoggedInUserId()));
        return DatabaseHelper::getRecordCount($Select) == 1;
    }

    static function getSingleCostCenterId()
    {
        $Select = DBQM::query("SELECT id FROM sh_user_cost_center WHERE user_id = ".escape(SecurityShell::getLoggedInUserId()));
        return DBQM::result($Select, 0);
    }
}