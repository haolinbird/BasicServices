<?php
namespace Lib\Util;

class String{
    /**
     * Callback for uksort
     */
    public static function uksortCB($str1, $str2){
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);
        return strcmp($str1, $str2);
    }

    /**
     * @param $email
     * @return bool
     */
    public static function isValidEmail($email){
        return preg_match('#^[\da-zA-Z\._]+@[\da-zA-Z_-]+(\.[a-zA-Z]{2,3}){1,4}$#', $email) === 1;
    }

    /**
     * @param $phoneNumber
     * @return bool
     */
    public static function isValidPhoneNumber($phoneNumber){
        return preg_match('#^\+?\d{1,12}(-\d{1,6}){0,4}$#', $phoneNumber) === 1;
    }
}