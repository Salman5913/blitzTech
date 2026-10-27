<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 6/16/14
 * Time: 3:00 PM
 */

class SecurityShellGroup extends BasicForm
{
    public function __construct()
    {
        parent::__construct("sh_group", "sh_grouproles", "group_id", "SECURITY GROUP MANAGEMENT");
        $this->setAccessAreas(CompanyModule::SECURITY_SHELL, 'Groups');
    }

    function getAllAppAreaGroups()
    {
        return DBQM::query("SELECT apa.main FROM cp_company_module cm, cp_company_module_app_areas cma, sh_app_area apa WHERE cm.id = cma.company_module_id AND apa.id = cma.app_area_id AND cm.is_active = 'Y' GROUP BY main ORDER BY main");
    }

    function getAllAppAreaGroupElements($GroupName)
    {
        return DBQM::query("SELECT apa.* FROM cp_company_module cm, cp_company_module_app_areas cma, sh_app_area apa WHERE cm.id = cma.company_module_id AND apa.id = cma.app_area_id AND cm.is_active = 'Y' AND apa.main = ".escape($GroupName)." ORDER BY sub");
    }

    function getAll()
    {
        return DBQM::query("SELECT sg.*, GROUP_CONCAT(sl.username SEPARATOR ', ') as userlist FROM sh_group sg LEFT JOIN sh_usergroup su on sg.id = su.group_id LEFT JOIN sh_login sl on sl.id = su.login_user_id GROUP BY sg.id ORDER BY name");
    }
} 