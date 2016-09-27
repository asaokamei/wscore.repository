<?php
namespace WScore\Repository\Helpers;

use DateTimeImmutable;

/**
 * Class CurrentDateTime
 * 
 * a simple factory for DateTimeImmutable object. 
 * able to set current time externally as static variable. 
 *
 * @package WScore\Repository\Helpers
 */
class CurrentDateTime extends DateTimeImmutable
{
    private static $now;

    /**
     * @param DateTimeImmutable $now
     */
    public static function setCurrentTime($now)
    {
        self::$now = $now;
    }
    
    /**
     * @return DateTimeImmutable
     */
    public static function forge()
    {
        return self::$now ?: new DateTimeImmutable();
    }
}