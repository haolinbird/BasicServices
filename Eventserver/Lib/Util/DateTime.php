<?php
namespace Lib\Util;

class DateTime{
    public static function formatTimeWithMicro($timeStamp, $format='Y-m-d H:i:s.u')
    {
        $dt = new \DateTime(date('Y-m-d H:i:s', $timeStamp).'.'.sprintf('%06d',($timeStamp - floor($timeStamp))*1000000));
        return $dt->format($format);
    }
}