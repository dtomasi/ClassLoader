<?php

/**
 * Class ClassLoader
 *
 * @author Dominik Tomasi <dominik.tomasi@gmail.com>
 * @copyright tomasiMEDIA 2014
 */

class ClassLoader {

    /**
     * Caching Mode
     * @var bool
     */
    private $cached;

    /**
     * The Cache-File-Name
     * @var string
     */
    private $cacheFile = 'classMap.cache';

    /**
     * Default Class Extension
     * @var string
     */
    private $classExtension = '.php';

    /**
     * Registered Namespaces
     * @var array
     */
    private $namespaces = array();

    /**
     * Map of Registered Classes
     * @var array
     */
    private $classMap = array();

    /**
     * Array of loaded Classes
     * @var array
     */
    private $loadedClasses = array();


    /**
     * Init ClassLoader with optional settings for Caching
     * @param bool $loadFromCache
     */
    public function __construct($loadFromCache = true)
    {
        $this->cached = $loadFromCache;
        if ($this->cached)
        {
            $this->loadFromCache();
        }

    }

    /**
     * Write ClassMap to File
     */
    public function __destruct()
    {
        if ($this->cached && count($this->classMap))
        {
            file_put_contents(__DIR__.'/'.$this->cacheFile,serialize($this->classMap));
        }
    }

    /**
     * Register SPL-Autoload-Function
     * @param bool $prepend
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this,'loadClass'), true, $prepend);
    }

    /**
     * Load ClassMap from Cache-File
     */
    public function loadFromCache($strFile = null)
    {
        if ($strFile !== null)
        {
            $this->cacheFile = $strFile;
        }

        if (file_exists(__DIR__.'/'.$this->cacheFile))
        {
            $this->classMap = unserialize(file_get_contents(__DIR__.'/'.$this->cacheFile));
        }
    }

    /**
     * Get registered Namespaces
     * @return array
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Get loaded Classes
     * @return array
     */
    public function getLoadedClasses()
    {
        return $this->loadedClasses;
    }

    /**
     * Register a new Namespace
     * @param $strNamespace
     * @param $strDirectory
     * @return bool
     */
    public function registerNamespace($strNamespace,$strDirectory)
    {
        try {

            if (!is_dir($strDirectory))
            {
                throw new Exception("try to register a namespace, but given directory does not exist");
            }

            $this->namespaces[$strNamespace][] = $strDirectory;

            // Return true on success
            return true;

        } catch (Exception $e) {

            echo $e->getMessage();
        }
        return false;
    }

    /**
     * Register multiple Namespaces
     * @param array $arrNamespaces
     */
    public function registerNamespaces(array $arrNamespaces)
    {
        foreach ($arrNamespaces as $strNamespace => $strDirectory) {
            $this->registerNamespace($strNamespace,$strDirectory);
        }
    }

    /**
     * Register a single Class
     * @param $strClass
     * @param $strFile
     * @return bool
     */
    public function registerClass($strClass,$strFile)
    {
        try {

            if (!file_exists($strFile))
            {
                throw new Exception("File on $strFile does not exist");
            }

            $this->classMap[$strClass] = $strFile;

            // Return true on success
            return true;

        } catch (Exception $e) {

            echo $e->getMessage();
        }
        return false;
    }

    /**
     * Register multiple Classes
     * @param array $arrClasses
     */
    public function registerClasses(array $arrClasses)
    {
        foreach ($arrClasses as $strClass => $strFile)
        {
            $this->registerClass($strClass,$strFile);
        }
    }

    /**
     *
     * Load a Class -> Function called by SPL-Autoload
     * @param $strClass
     * @return bool
     */
    private function loadClass($strClass)
    {

        if ($file = $this->findFile($strClass))
        {
            /** @noinspection PhpIncludeInspection */
            require $file;
            $this->loadedClasses[$strClass] = $file;
            return true;
        }
        return false;
    }

    /**
     * Find the ClassFile
     * @param $strClass
     * @return bool|string
     */
    private function findFile($strClass)
    {

        // try to get Filepath from ClassMapArray
        if ($file = $this->getFromClassMap($strClass))
        {
            return $file;
        }

        // try to get Filepath by PSR-0 Path
        if ($file = $this->getByPSR0($strClass))
        {
            return $this->classMap[$strClass] = $file;
        }

        // try to find Class in Registered Namespaces
        if ($file = $this->getFromRegisteredNamespace($strClass))
        {
            return $this->classMap[$strClass] = $file;
        }

        // try to find by Classname in Filesystem
        if ($file = $this->getFromFileSystem($strClass))
        {
            return $this->classMap[$strClass] = $file;
        }

        // no ClassFile found
        return false;
    }

    /**
     * Try to get Class from ClassMap
     * Maybe it is already registered by Cache, so searching is not necessary
     * @param $strClass
     * @return bool
     */
    private function getFromClassMap($strClass)
    {
        if (array_key_exists($strClass,$this->classMap))
        {
            return $this->classMap[$strClass];
        }
        return false;
    }

    /**
     * Find file by PSR-0-Standard.
     * File and FolderStructure is equal to Namespace
     * @param $strClass
     * @return bool|string
     */
    private function getByPSR0($strClass)
    {
        $file = implode(DIRECTORY_SEPARATOR,explode('\\',$strClass)).$this->classExtension;
        if (file_exists($file))
        {
            return $this->classMap[$strClass] = $file;
        }
        return false;
    }

    /**
     * Try to get File in registered Namespaces
     * @param $strClass
     * @return bool|string
     */
    private function getFromRegisteredNamespace($strClass)
    {
        if (false !== $separatorPosition = strrpos($strClass, '\\'))
        {
            $namespace = substr($strClass, 0, $separatorPosition);
            $className = substr($strClass, $separatorPosition + 1);

            foreach ($this->namespaces as $regNamespace => $dirs) {

                if (strpos($namespace, $regNamespace) !== 0) {
                    continue;
                }

                foreach ($dirs as $dir) {
                    $file = $dir.DIRECTORY_SEPARATOR.$className.$this->classExtension;
                    if (is_file($file)) {
                        return $this->classMap[$strClass] = $file;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Find by ClassName in FileSystem
     * @param $strClass
     * @return bool|string
     */
    private function getFromFileSystem($strClass)
    {
        if (false !== $separatorPosition = strrpos($strClass, '\\'))
        {
            $arrFrag = explode('\\',$strClass);
            $strFileName = end($arrFrag).$this->classExtension;
        } else {
            $strFileName = $strClass.$this->classExtension;
        }

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__), \RecursiveIteratorIterator::SELF_FIRST );;

        /**
         * @var $dir \SplFileInfo
         */
        foreach ($it as $dir)
        {
            if ($dir->getFilename() == $strFileName)
            {
                return $dir->getPathname();
            }
        }
        return false;
    }
}
