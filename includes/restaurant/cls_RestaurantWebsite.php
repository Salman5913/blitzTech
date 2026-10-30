<?php
/**
 * Restaurant website On / Off (rs_website_settings row id 1), managed from restaurant/website_status.php.
 *
 * Off: closed until switched on again. On: open, only between the opening and closing time when they are set
 * (closing before opening = open past midnight). While closed the website shows a popup with the message and
 * the API refuses new orders; restaurant_bridge/api/index.php works out the same state (websiteStatus()).
 */
class RestaurantWebsite
{
    static function getSettings()
    {
        $Row = DBQM::fetch_assoc(DBQM::query("SELECT * FROM rs_website_settings WHERE id = 1"));

        return $Row ?: ['is_open' => 1, 'open_time' => null, 'close_time' => null, 'closed_message' => null];
    }

    static function save(bool $isOpen, $OpenTime, $CloseTime, $ClosedMessage)
    {
        DBQM::query("
            INSERT INTO rs_website_settings (id, is_open, open_time, close_time, closed_message)
            VALUES (1, " . ($isOpen ? 1 : 0) . ", " . self::timeSql($OpenTime) . ", " . self::timeSql($CloseTime) . ", " . escape(trim($ClosedMessage), 'text', true) . ")
            ON DUPLICATE KEY UPDATE is_open = VALUES(is_open), open_time = VALUES(open_time), close_time = VALUES(close_time), closed_message = VALUES(closed_message)
        ");
    }

    /** "18:00" from an <input type="time">, or null */
    private static function timeSql($Time)
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', trim((string)$Time)) ? escape(trim($Time) . ':00') : 'null';
    }

    /** "6:00 PM", or '' */
    static function formatTime($Time)
    {
        return empty($Time) ? '' : date('g:i A', strtotime($Time));
    }

    /**
     * What the website shows right now
     * @return array ['open' => bool, 'message' => string]
     */
    static function getStatus()
    {
        $Settings = self::getSettings();
        $Now = date('H:i:s');
        $Open = $Settings['open_time'] ?: '00:00:00';
        $Close = $Settings['close_time'] ?: '24:00:00';

        $isOpen = $Settings['is_open'] && ($Open <= $Close ? ($Now >= $Open && $Now < $Close) : ($Now >= $Open || $Now < $Close));

        return ['open' => (bool)$isOpen, 'message' => $isOpen ? '' : self::getClosedMessage($Settings)];
    }

    static function getClosedMessage(array $Settings)
    {
        if (trim((string)$Settings['closed_message']) !== '')
            return trim($Settings['closed_message']);

        return $Settings['open_time']
            ? "We're closed right now. Kindly visit at " . self::formatTime($Settings['open_time']) . "."
            : "We're closed right now. Kindly visit again later.";
    }
}
