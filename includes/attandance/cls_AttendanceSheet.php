<?php

class AttendanceSheet extends BasicForm
{
	private $BranchId;
	private $EmpTime = [];
	private $LogId = 0;

    public function __construct()
    {
        parent::__construct("hr_attendance_log","","","Attendance Log");
        $this->setAccessAreas(CompanyModule::HR, 'Attendance Sheet');
    }

    function generateManualAttendance($LogId, $StartDate, $EndDate, $TimeIn, $TimeOut, $isNight)
    {
    	$SelectBranchId = DBQM::query("SELECT branch_id FROM hr_attendance_log WHERE id = ".escape($LogId));
    	$BranchId = DBQM::result($SelectBranchId, 0);

        $oChartOfAccount = new ChartOfAccount();
        $SelectAccounts = $oChartOfAccount->getAllEmployeesByBranchId($BranchId);

        $start    = new DateTime(DateEncode($StartDate));
        $end      = new DateTime(DateEncode($EndDate)." 23:59:59");
        $interval = DateInterval::createFromDateString('1 day');
        $period   = new DatePeriod($start, $interval, $end);

        while ($Row = DBQM::fetch_assoc($SelectAccounts))
        {
            $AccountId = $Row['id'];

            foreach ($period as $dt)
            {
                // if not sunday
                if ($dt->format("N") != 7)
                {
                    $this->createLogData($LogId, $AccountId, $dt->format("Y-m-d"), $TimeIn, $TimeOut, $isNight);
                }
            }
        }
    }

    function addLogAttendance($LogId, $StartDate, $TimeIn, $TimeOut, $isNight)
    {
        $oChartOfAccount = new ChartOfAccount();
        $SelectAccounts = $oChartOfAccount->getAllEmployees();

        $start    = new DateTime(DateEncode($StartDate));
        $end      = new DateTime(DateEncode($EndDate)." 23:59:59");
        $interval = DateInterval::createFromDateString('1 day');
        $period   = new DatePeriod($start, $interval, $end);

        while ($Row = DBQM::fetch_assoc($SelectAccounts))
        {
            $AccountId = $Row['id'];

            foreach ($period as $dt)
            {
                // if not sunday
                if ($dt->format("N") != 7)
                {
                    $this->createLogData($LogId, $AccountId, $dt->format("Y-m-d"), $TimeIn, $TimeOut, $isNight);
                }
            }
        }
    }

    function createLogData($LogId, $AccountId, $Date, $TimeIn, $TimeOut, $isNight)
    {
        DBQM::query("INSERT INTO hr_attendance_log_data VALUES (
                                                                      0, 
                                                                      ".escape($LogId, 'int').", 
                                                                      ".escape($AccountId, 'int').",
                                                                      ".escape($Date).",
                                                                      ".escape($TimeIn).",
                                                                      ".escape($TimeOut).",
                                                                      ".escape($isNight, 'int')."
        )");

        return DatabaseHelper::getLastInsertId();
    }

    function updateManualAttendance($Id, $TimeIn, $TimeOut)
    {
        DBQM::query("UPDATE hr_attendance_log_data SET time_in = ".escape($TimeIn).", time_out = ".escape($TimeOut)." WHERE id = ".escape($Id)." ");
        return DatabaseHelper::isRecordDeletedOrUpdatedSuccessfully();
    }

    function getAllLogs()
	{
		return DBQM::query("
									SELECT 
										al.*,
										b.name AS branch_name,
										s.id AS salary_id,
										get_no_of_attendance_by_log(al.id) AS no_of_attendance
									FROM 
										branch b,
										hr_attendance_log al
										LEFT JOIN
											hr_salary s
											ON al.id = s.log_id
									WHERE
										b.id = al.branch_id
									ORDER BY 
										al.id DESC
		");
	}

    function getAllEmployeesByLog($LogId)
    {
        return DBQM::query("
									SELECT 
									    a.name,
									    a.id AS account_id
									FROM 
										acc_accounts a,
										hr_attendance_log_data ald
									WHERE
										a.id = ald.account_id AND 
										ald.log_id = ".escape($LogId)."
									GROUP BY 
										ald.account_id DESC
									ORDER BY 
										ald.id DESC
		");
    }

	function deleteLog($Id)
	{
		DBQM::query("DELETE FROM hr_attendance_log WHERE id = ".escape($Id));
	}

	function getLogDetails($LogId)
	{
		$Select = DBQM::query("SELECT * FROM hr_attendance_log WHERE id = ".escape($LogId));
		return DBQM::fetch_assoc($Select);
	}

    function getLogDetailsDetailedView($LogId)
    {
        /*$Select = DBQM::query("SELECT l.*, l.start_date AS start_date, MAX(ld.date) AS end_date FROM hr_attendance_log l, hr_attendance_log_data ld WHERE l.id = ld.log_id AND l.id = ".escape($LogId));*/

        $Select = DBQM::query("SELECT l.* FROM hr_attendance_log l WHERE l.id = ".escape($LogId));
        return DBQM::fetch_assoc($Select);
    }

    function getLogData($LogId, $AccountId)
    {
        $Select = DBQM::query("SELECT * FROM hr_attendance_log_data WHERE log_id = ".escape($LogId)." AND account_id = ".escape($AccountId)." ");
        return $Select;
    }

    function deleteLogData($LogId, $AccountId)
    {
        $Select = DBQM::query("DELETE FROM hr_attendance_log_data WHERE log_id = ".escape($LogId)." AND account_id = ".escape($AccountId)." ");
        return $Select;
    }

    function getBranchIdByLog($LogId)
    {
        $Select = DBQM::query("SELECT branch_id FROM hr_attendance_log WHERE id = ".escape($LogId));
        return DBQM::result($Select, 0);
    }

	function getAllUnlinkedLogs()
	{
		return DBQM::query("
									SELECT 
										al.* 
									FROM
										hr_attendance_log al
									WHERE
										al.id NOT IN (select log_id from vw_salary where is_deleted <> 'Y')
		");
	}

    private function checkDataFromUploadedFile($FileName)
    {
        include_once ("../libs/PHPExcel.php");
        $inputFileName = $FileName;

        try {
            $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($inputFileName);
        } catch (Exception $e) {
            die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME) . '": ' .
                $e->getMessage());
        }

        $EmpIdCount = 0;

        try {

            $ExistAccountNameOnEmpId = [];

            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();


            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                    null, true, false);

                $rowData = $rowData[0];

                $EmpId = $rowData[0];
                $EmpName = $rowData[3];
                $Date = DateEncode($rowData[4],'Y-m-d');

                if (empty(ChartOfAccount::getAccountIdByNameANDEmpId($EmpId, $EmpName)) AND !empty(ChartOfAccount::getNameByEmpId($EmpId)))
                {
                    if (!in_array($EmpName, $ExistAccountNameOnEmpId))
                        $ExistAccountNameOnEmpId[] = $EmpId;

                    $EmpIdCount++;
                }
            }

            return array($EmpIdCount, $ExistAccountNameOnEmpId);

            unlink($FileName);
        } catch (PHPExcel_Exception $e)
        {
            ErrorHandler::setError($e->getMessage());
        }
    }

	function uploadFileNProcess($File, $LogId)
	{
		$this->LogId = $LogId;
		$FileName = "../client_uploads/".$File['name'];

		$this->BranchId = $this->getLogDetails($LogId)['branch_id'];

		move_uploaded_file($File['tmp_name'], $FileName);

		$this->recordDataFromUploadedFile($FileName);
	}

	private function recordDataFromUploadedFile($FileName)
	{
		include_once ("../libs/PHPExcel.php");
		$inputFileName = $FileName;

		try {
			$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objPHPExcel = $objReader->load($inputFileName);
		} catch (Exception $e) {
			die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME) . '": ' .
			    $e->getMessage());
		}

        $Count = 0;
		$this->truncateTable($this->LogId);

        $RowLogDetails = $this->getLogDetails($this->LogId);

        try {
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            list($EmpIdCount, $ExistAccountNameOnEmpId) = $this->checkDataFromUploadedFile($FileName);

            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                    null, true, false);

                $rowData = $rowData[0];

                $EmpId = $rowData[0];
                $EmpName = $rowData[2];

                $myDateTime = $rowData[3]; //DateTime::createFromFormat('d/m/Y', $rowData[4]);
                $Date = DateEncode($myDateTime); //$myDateTime->format('Y-m-d');

                if (empty($rowData[6]) && !empty($rowData[7]))
                {
                    $TimeIn = $RowLogDetails['start_time'];
                    $TimeOut = date("H:i:s", strtotime($rowData[7]));
                }
                elseif (!empty($rowData[6]) && empty($rowData[7]))
                {
                    $TimeIn = date("H:i:s", strtotime($rowData[6]));
                    $TimeOut = $RowLogDetails['end_time'];
                }
                elseif (empty($rowData[6]) && empty($rowData[7]))
                {
                    $TimeIn = "00:00";
                    $TimeOut = "00:00";
                }
                else
                {
                    $TimeIn = date("H:i:s", strtotime($rowData[6]));
                    $TimeOut = date("H:i:s", strtotime($rowData[7]));
                }

                if ($EmpIdCount == 0)
                {
                    if ($this->isAvailableAttendanceLogByDate($this->LogId, ChartOfAccount::getAccountIdByEmpId($EmpId, $EmpName), $Date)){
                        continue;
                    }

                    if (!empty($EmpName))
                        $this->insert($EmpId, $EmpName, $Date, $TimeIn, $TimeOut);
                }
            }

            if ($EmpIdCount > 0){
                foreach ($ExistAccountNameOnEmpId as $EId){
                    ErrorHandler::setError($EId." Emp id with a different name already exist!");
                }
            }

            if ($Count > 0)
                SuccessHandler::setMessage("Some data has been ignored as all data already available.");

            unlink($FileName);
        } catch (PHPExcel_Exception $e)
        {
            ErrorHandler::setError($e->getMessage());
        }
	}

	private function truncateTable($LogId)
	{
		DBQM::query("DELETE FROM hr_attendance_log_data WHERE log_id = ".escape($LogId));
	}

	private function insert($EmpId, $EmpName, $Date, $TimeIn, $TimeOut)
	{
		$AccountId = ChartOfAccount::getAccountIdByEmpId($EmpId, $this->BranchId);

		if (empty($AccountId))
		{
			$oChartOfAccount = new ChartOfAccount();
            $oChartOfAccount->initForSave();

			if (!empty($oChartOfAccount->getAccountIdByName($EmpName)))
				$EmpName = $EmpName." - ".$EmpId;

            $oChartOfAccount->saveMainTableData([
                getConstantCategory()->getEmployee(),
                $EmpName,
                ProjectHelper::DEFAULT_CURRENCY,
                'Y',
                'Y'
            ]);

            $AccountId = $oChartOfAccount->getVoucherId();

            if ($oChartOfAccount->isVoucherCreatedSuccessfully())
            {
                $oChartOfAccount->saveDataTableData([
                    '', '', '', '', '', '', 'Karachi', '', '', 0, 0, '', '', '', 'Y'
                ], ChartOfAccount::IND_BIO_DATA);

                $oChartOfAccount->saveDataTableData([
                    $EmpId,
                    $this->BranchId,
                    0,
                    'N',
                    '',
                    DatabaseHelper::DATE(),
                    'Y'
                ], ChartOfAccount::IND_HR_ACCOUNTS_DATA);
            }

			SuccessHandler::setMessage("New Employee Account Created - ".$EmpName);
		}

		DBQM::query("INSERT INTO hr_attendance_log_data VALUES (
													0,
													".escape($this->LogId).",
													".escape($AccountId).",
													".escape($Date).",
													".escape($TimeIn).",
													".escape($TimeOut).",
													0
		)");
	}

	private function update($EmpId, $Date, $TimeOut)
	{
		$AccountId = ChartOfAccount::getAccountIdByEmpId($EmpId, $this->BranchId);
		DBQM::query("UPDATE hr_attendance_log_data SET time_out = ".escape($TimeOut)." WHERE account_id = ".escape($AccountId)." AND date = ".escape($Date)." AND log_id = ".escape($this->LogId));
	}

	function getAllEmployeeList($LogId)
	{
		return DBQM::query("
									SELECT 
										hrad.emp_id, 
										a.name AS emp_name, 
										hrad.basic_salary,
									    (SUM(ats.is_night) * 9) AS night_overtime_hours,
										a.id AS account_id
									FROM 
										hr_attendance_log_data ats, 
										acc_accounts a, 
										hr_accounts_data hrad
									WHERE 
										ats.account_id = a.id AND
										a.id = hrad.account_id AND
										log_id = ".escape($LogId)." AND
										hrad.is_active <> 'N'
									GROUP BY 
										ats.account_id 
									ORDER BY 
										emp_name
		");
	}


	function getReport($AccountId, $DateFrom, $DateTill, $LogId)
	{
		return DBQM::query("
								SELECT 
									* 
								FROM 
									hr_attendance_log_data 
								WHERE 
									account_id = ".escape($AccountId) . " AND
									`date` BETWEEN ".escape(DateEncode($DateFrom))." AND ".escape(DateEncode($DateTill))." AND 
									log_id = ".escape($LogId)."
								ORDER BY 
									DATE ASC
		");
	}

	function getNextDateLogInTime($AccountId, $Date, $LogId)
	{
		$Select = DBQM::query("
								SELECT 
									time_in,
									time_out,
									`date`
								FROM 
									hr_attendance_log_data 
								WHERE 
									account_id = ".escape($AccountId) . " AND
									`date` = ".escape(date("Y-m-d", strtotime($Date . " +1 day")))." AND 
									log_id = ".escape($LogId)."
								ORDER BY 
									DATE ASC
		");

		$Row = DBQM::fetch_assoc($Select);

		if ($Row['time_out'] == '00:00:00' and date("D", strtotime($Row['date'])) != 'Sun')
		{
			if (date('a', strtotime($Row['time_in'])) == 'pm')
			{
				$Row['time_out'] = $Row['time_in'];
				$Row['time_in'] = "00:00:00";
			}
		}

		return $Row['time_in'];
	}

	function getNameById($AccountId)
	{
		return ChartOfAccount::getName($AccountId);
	}

	function getEmployeeIdByAccountId($AccountId)
	{
		return ChartOfAccount::getEmployeeIdByAccountId($AccountId);
	}

	function getDateRange($DateFrom, $DateTill)
	{
		$Array = [];

		$start    = new DateTime(DateEncode($DateFrom));
		$end      = new DateTime(DateEncode($DateTill)." 23:59:59");
		$interval = DateInterval::createFromDateString('1 day');
		$period   = new DatePeriod($start, $interval, $end);

		foreach ($period as $dt) {
			if ($dt->format("N") != 7) { // if not sunday
				//echo $dt->format("l Y-m-d") . "<br>\n";
				$Array[] = $dt->format("Y-m-d");
			}
		}

		return $Array;
	}

	function getSundayCount($DateFrom, $DateTill)
	{
		$Array = [];

		$start    = new DateTime(DateEncode($DateFrom));
		$end      = new DateTime(DateEncode($DateTill)." 23:59:59");
		$interval = DateInterval::createFromDateString('1 day');
		$period   = new DatePeriod($start, $interval, $end);


		foreach ($period as $dt) {
			if ($dt->format("N") == 7) { // if not sunday
				//echo $dt->format("l Y-m-d") . "<br>\n";
				$Array[] = $dt->format("Y-m-d");
			}
		}

		return count($Array);
	}

    function isAvailableAttendanceLogByDate($LogId, $EmployeeId, $Date)
    {
        $Select = DBQM::query("SELECT id FROM hr_attendance_log_data WHERE log_id = ".escape($LogId)." AND account_id = ".escape($EmployeeId)." AND date = ".escape($Date));
        return !DatabaseHelper::isRecordSetEmpty($Select);
    }
}
