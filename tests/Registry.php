<?php

/**
 * Class ClassLoader
 *
 * A universal Classloader with several functions to Find Classes and Caching.
 *
 * ClassLoader can load Classes by :
 * registering a Class and File directly,
 * register a custom namespace and directory to load,
 * in a PSR-0-Standard Environment (Namespaces are equal to FolderStructure)
 * and by Searching the Classname as Filename in Subdirectories
 *
 * @author Dominik Tomasi <dominik.tomasi@gmail.com>
 * @copyright tomasiMEDIA 2014
 */

// !!!!!!!!!!!! This is just a helper-Class for Testing ClassLoader !!!!!!!!!!!!!! //

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