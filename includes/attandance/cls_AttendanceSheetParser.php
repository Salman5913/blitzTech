<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 7/25/2017
 * Time: 12:20 PM
 */
if (is_file("../connection/localhost.php"))
	include_once("../connection/localhost.php");
else
	include_once("../../connection/localhost.php");

class AttendanceSheetParser extends AttendanceSheet
{
	public $CanOvertime;

	public $SundayCount = 0;
	public $DaysWorked = 0;
	public $Leave = 0;
	public $Overtime = 0;
	public $SundayOvertime = 0;
    public $HolidayOvertime = 0;
	public $LateHours = 0;
	public $isNightEmployee;
	private $LogId;

	public $StartTime;
	public $EndTime;

	private $Select;

	private $AccountId;

	public $Holidays;
	public $HolidaysCount;

	public $LateCount;


	function __construct($LogId, $AccountId, $StartTime, $EndTime, $NighStartTime, $NighEndTime, $DateFrom, $DateTill, $isNightShift, $Holidays) {
		$this->Select = $this->getReport($AccountId, $DateFrom, $DateTill, $LogId);

		$oChartOfAccount = new ChartOfAccount();
		$this->CanOvertime = $oChartOfAccount->getHRData($AccountId)['can_overtime'] == 'Y';

		$this->isNightEmployee = $isNightShift == 'Y';
		$this->DaysWorked = DatabaseHelper::getRecordCount($this->Select);

		$this->AccountId = $AccountId;
		$this->LogId = $LogId;

		$this->Holidays = $Holidays;
		$this->HolidaysCount = count((array)$Holidays);

		if ($this->isNightEmployee)
		{
			$this->StartTime = $NighStartTime;
			$this->EndTime = $NighEndTime;
		}
		else
		{
			$this->StartTime = $StartTime;
			$this->EndTime = $EndTime;
		}

		if ($this->HolidaysCount > 0)
        {
            $this->DaysWorked = $this->DaysWorked + $this->HolidaysCount;
        }
	}

	function getAllRows()
	{
		return $this->Select;
	}

	function rowCalculate($Row)
	{
		$RowLate = 0;
        $RowHolidayOvertime = 0;
		$RowOvertime = 0;
		$Hint = '';

		$DayName = date("D", strtotime($Row['date']));
		$STRTTimeIn = strtotime($Row['time_in']);
		$STRTStartTime = strtotime($this->StartTime);

		if ($DayName == 'Sun')
		{
			$this->DaysWorked--;
		}

		if ($this->isNightEmployee)
		{
			/**
			 * if only 1 entry is recorded, check if its logout time or login time.
			 */
			if ($Row['time_out'] == '00:00:00' and $DayName != 'Sun')
			{
				if (date('a', $STRTTimeIn) == 'pm')
				{
					$Row['time_out'] = $Row['time_in'];
					$Row['time_in'] = "00:00:00";
				}
			}

			/**
			 * If night shift, set login time = logout time,
			 * logout time = next day's login time
			 *
			 * If its sunday, and no logout time is recorded, means sunday was not working day.
			 */
			if ($DayName == 'Sun' and $Row['time_out'] == "00:00:00")
			{
				$this->DaysWorked--;
				return false; // skip this row
			}

			$Row['time_in'] = $Row['time_out'];

			$Row['time_out'] = $this->getNextDateLogInTime($this->AccountId, $Row['date'], $this->LogId);
		}


		if ($STRTTimeIn > $STRTStartTime) // if late
		{
			$RowLate += strtotime($Row['date']." ".$Row['time_in']) - strtotime($Row['date']." ".$this->StartTime);
			$this->LateHours += $RowLate;
		}
		if ($Row['time_out'] != "00:00:00" and strtotime($Row['time_out']) < strtotime($this->EndTime)) // if left early
		{
			$RowLate += strtotime($Row['date']." ".$this->EndTime) - strtotime($Row['date']." ".$Row['time_out']);
			$this->LateHours += $RowLate;
		}

		if (date("D", strtotime($Row['date'])) != 'Sun')
		{
			if ($STRTTimeIn < $STRTStartTime ) // early in, overtime
			{
				$RowOvertime += strtotime($Row['date']." ".$this->StartTime) - strtotime($Row['date']." ".$Row['time_in']);
			}

			if ($Row['time_out'] != "00:00:00" and strtotime($this->EndTime) < strtotime($Row['time_out'])) // late out, overtime
			{
				$RowOvertime += strtotime($Row['date']." ".$Row['time_out']) - strtotime($Row['date']." ".$this->EndTime);
			}

            $this->Overtime += $RowOvertime;
		}
		else
		{
			$RowOvertime += strtotime($Row['date']." ".$Row['time_out']) - strtotime($Row['date']." ".$Row['time_in']);
			$this->SundayOvertime += $RowOvertime;
			$this->SundayCount++;
		}

		if (in_array($Row['date'], (array)$this->Holidays))
        {
            /*$RowHolidayOvertime += strtotime($Row['date']." ".$Row['time_out']) - strtotime($Row['date']." ".$Row['time_in']);
            $this->HolidayOvertime += $RowHolidayOvertime;*/

            /**
             * If worked on holidays, remove holiday as working day count as we are giving overtime for it separately.
             */
            $this->DaysWorked--;
            $this->HolidayOvertime += 1;
        }


		if ($Row['time_out'] == "00:00:00")
		{
			$Hint .= 'No Logout Time Recorded. ';
			$this->DaysWorked--;

			$this->Overtime -= $RowOvertime;
            //$this->HolidayOvertime -= $RowHolidayOvertime;
			$this->LateHours -= $RowLate;
		}
		elseif ($Row['time_in'] == "00:00:00")
		{
			$Hint .= 'No Login Time Recorded. ';
			$this->DaysWorked--;

			$this->Overtime -= $RowOvertime;
            //$this->HolidayOvertime -= $RowHolidayOvertime;
			$this->LateHours -= $RowLate;
		}
		elseif ($RowLate > 0 or $RowOvertime > 0 or $RowHolidayOvertime > 0)
		{
			if ($RowLate > 0)
				$Hint .= round($RowLate / 60, 2)." Minutes Late. ";
			if ($RowOvertime > 0 and $this->CanOvertime)
				$Hint .= round($RowOvertime / 60, 2)." Minutes Overtime. ";
            /*if ($RowHolidayOvertime > 0 and $this->CanOvertime)
                $Hint .= round($RowHolidayOvertime / 60, 2)." Minutes Holiday Overtime. ";*/
		}


		if (!$this->CanOvertime)
        {
            $this->Overtime = 0;
            $this->HolidayOvertime = 0;
        }

		return [$Row, $Hint];
	}
}