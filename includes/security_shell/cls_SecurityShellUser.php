<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 6/16/14
 * Time: 3:00 PM
 */

class SecurityShellUser extends BasicForm
{
    public function __construct()
    {
        parent::__construct("sh_login", "sh_usergroup", "login_user_id", "Application User Management");
        $this->setAccessAreas(CompanyModule::SECURITY_SHELL, 'Users');
    }

    function getAll()
    {
        return DBQM::query("
                    SELECT 
                        l.*, 
                        GROUP_CONCAT(sg.name SEPARATOR ', ') AS group_names 
                    FROM 
                         sh_login l 
                         LEFT JOIN 
                            sh_usergroup su 
                             on 
                                 l.id = su.login_user_id 
                         LEFT JOIN 
                            sh_group sg 
                             on 
                                 su.group_id = sg.id
                    WHERE
                        is_hidden <> 'Y'
                    GROUP BY
                        l.id
                    ORDER BY 
                         username, sg.name
        ");
    }
} 