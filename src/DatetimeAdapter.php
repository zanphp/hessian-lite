<?php

namespace ZanPHP\HessianLite;

use DateTime;

class DatetimeAdapter
{
    public static function toObject($ts, $parser)
    {
        $date = date('c', $ts);
        return new Datetime($date);
    }

    public static function writeTime(DateTime $date, Writer $writer)
    {
        $ts = $date->format('U');
        $stream = $writer->writeDate($ts);
        return new StreamResult($stream);
    }
}