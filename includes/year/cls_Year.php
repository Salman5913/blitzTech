<?php
/**
 * Created by PhpStorm.
 * User: musta
 * Date: 0029, 12, 29, 2019
 * Time: 11:26 AM
 */

class Year
{
	function getYears()
	{
		return DBQM::query("SELECT * FROM year ORDER BY id DESC");
	}

    static function getOpeningDate()
    {
        $Select = DBQM::query("SELECT start_date FROM year WHERE id = " . escape(Year::getYearId(), 'int'));
        return DateDecode(DBQM::result($Select, 0));
    }

	static function setLoginYearId($YearId)
	{
		$_SESSION['__year_id'] = $YearId;
		$_SESSION['__year_name'] = self::getYearName($YearId);
		$_SESSION['__year_is_closed'] = self::getIsYearClosed($YearId);
	}

	static function removeLoginYearId()
	{
		unset($_SESSION['__year_id']);
	}

	private static function getIsYearClosed($YearId)
	{
		$Select = DBQM::query("SELECT * FROM year WHERE id > ".escape($YearId));
		return !DatabaseHelper::isRecordSetEmpty($Select);
	}

	static function getYearName($YearId='')
	{
		if (!empty($YearId))
		{
			$Select = DBQM::query("SELECT year_name FROM year WHERE id = " . escape($YearId, 'int'));
			return DBQM::result($Select, 0);
		}
		else
			return $_SESSION['__year_name'];
	}

    static function getLastYearId()
    {
        $Select = DBQM::query("SELECT id FROM year ORDER BY id DESC LIMIT 1");
        return DBQM::result($Select, 0);
    }

	static function getYearId()
	{
		return $_SESSION['__year_id'];
	}

	static function isYearClosed()
	{
		return $_SESSION['__year_is_closed'];
	}

	static function getMonthsOfCurrentYear()
	{
		/*$Select = DBQM::query("SELECT * FROM sas_year WHERE id = ".escape(self::getYearId()));
		$Row = DBQM::fetch_assoc($Select);*/

		$timeStart = strtotime("first day of ".date("M Y", strtotime("-11 months"))." midnight");
		$timeEnd   = strtotime("last day of this month");
		$out       = [];
		$milestones[] = $timeStart;
		$timeEndMonth = strtotime('first day of next month midnight', $timeStart);
		while ($timeEndMonth < $timeEnd) {
			$milestones[] = $timeEndMonth;
			$timeEndMonth = strtotime('+1 month', $timeEndMonth);
		}
		$milestones[] = $timeEnd;
		$count = count($milestones);
		for ($i = 1; $i < $count; $i++) {
			$out[] = [
				'start' => date('Y-m-d H:i:s', $milestones[$i - 1]),
				'end'   => date('Y-m-d H:i:s', $milestones[$i] - 1)
			];
		}

		return $out;
	}

    static function getMonthsNameOfCurrentYear()
    {
        /*$Select = DBQM::query("SELECT * FROM sas_year WHERE id = ".escape(self::getYearId()));
        $Row = DBQM::fetch_assoc($Select);*/

        $timeStart = strtotime("first day of ".date("M Y", strtotime("-11 months"))." midnight");
        $timeEnd   = strtotime("last day of this month");
        $out       = [];
        $milestones[] = $timeStart;
        $timeEndMonth = strtotime('first day of next month midnight', $timeStart);
        while ($timeEndMonth < $timeEnd) {
            $milestones[] = $timeEndMonth;
            $timeEndMonth = strtotime('+1 month', $timeEndMonth);
        }
        $milestones[] = $timeEnd;
        $count = count($milestones);
        for ($i = 1; $i < $count; $i++) {
            $out[] = date('F Y', $milestones[$i - 1]);
        }

        return $out;
    }

    static function getQuartersOfThisAndPreviousYear()
    {
        $Quarters = [];
        for ($i=0; $i<4; $i++)
        {
            $Time = strtotime("-".($i*3)." Months");
            $Month = date("n", $Time);
            $Year = date("Y", $Time);

            $QuarterNumber = ceil($Month / 3);

            $Quarters[] = [$QuarterNumber, $Year];
        }

        return $Quarters;
    }
}