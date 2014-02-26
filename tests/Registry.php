<?php
/**
 * Created by PhpStorm.
 * User: dtomasi
 * Date: 26.02.14
 * Time: 13:11
 */

class Registry {

    private static $arrReg;

    /**
     * Set Key and Value to Registry
     * @param $key
     * @param $value
     */
    public static function set($key,$value)
    {
        self::$arrReg[$key] = $value;
    }

    /**
     * Get a Value
     * @param $key
     * @return bool|mixed
     */
    public static function get($key)
    {
        return (array_key_exists($key,self::$arrReg) ? self::$arrReg[$key] : false);
    }

} 