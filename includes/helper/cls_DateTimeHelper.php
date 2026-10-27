<?php

class DateTimeHelper
{
    static function display($DateTime, bool $WithBreakLink=false)
    {
        if (date("Y-m-d") == date("Y-m-d", strtotime($DateTime)))
            return date("h:i a", strtotime($DateTime));
        else
            return !$WithBreakLink
                ? date("M d, y h:i a", strtotime($DateTime))
                : date("M d, y<br><small>h:i a</small>", strtotime($DateTime));
    }

    static function duration($StartDate, $EndDate)
    {
        $start_datetime = new DateTime($StartDate);
        $diff = $start_datetime->diff(new DateTime($EndDate));

        $Remaining = '';
        $NumberOfDays = $diff->days;
        $WeekendDays = self::numberOfWeekendDays($StartDate, $EndDate, $NumberOfDays);

        if ($NumberOfDays > 0)
            $Remaining = "{$NumberOfDays} Days";
        else
            $Remaining = "{$diff->h} Hours, ($diff->i) Minutes";

        return $Remaining;
    }

    static function numberOfWeekendDays($StartDate, $EndDate, $DaysCount=24)
    {
        $weekendDays = 0;
        $startTimestamp = strtotime($StartDate);
        $endTimestamp = strtotime($EndDate);

        for ($i = $startTimestamp; $i <= $endTimestamp; $i = $i + (60 * 60 * $DaysCount)) {
            if (date("N", $i) > 5) $weekendDays = $weekendDays + 1;
        }
        return $weekendDays;
    }
}