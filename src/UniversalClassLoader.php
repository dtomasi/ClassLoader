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

namespace dtomasi;

/**
 * Class ClassLoader
 * @package dtomasi
 */

class UniversalClassLoader implements \Serializable {

    /**
     * The Rootpath for searching by DirectoryIterator
     * @var null|string
     */
    private $rootPath = null;

    /**
     * Default Class Extension
     * @var array
     */
    private $classExtensions = array('php','inc');

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
     * Optionally Register Function for SplAutoload on construct
     * @param bool $blnRegister
     */
    public function __construct($blnRegister = true)
    {
        $this->register();
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
     * Serialize the Classloader
     * @return string|void
     */
    public function serialize()
    {
        $arrSerial = array(
            $this->rootPath,
            $this->classExtensions,
            $this->namespaces,
            $this->classMap
        );

        return serialize($arrSerial);
    }

    /**
     * Unserialize form String
     * NOTE: this only can be done if no classes are already loaded
     * @param string $string
     */
    public function unserialize($string) {

        // do not overwrite if classes are loaded
        if (!empty($this->loadedClasses)) {
            return;
        }

        $arrSerial = @unserialize($string);

        if (is_array($arrSerial) && !empty($arrSerial)) {
            $this->rootPath = array_shift($arrSerial);
            $this->classExtensions = array_shift($arrSerial);
            $this->namespaces = array_shift($arrSerial);
            $this->classMap = array_shift($arrSerial);
        }

    }

    /**
     * Add a accepted Class-Extension
     * @param $strExtension
     */
    public function addAcceptedExtension($strExtension) {
        array_push($this->classExtensions,$strExtension);
    }

    /**
     * Set the Rootpath
     * @param $strPath
     */
    public function setRootPath($strPath) {
        if (is_dir($strPath)) {
            $this->rootPath = $strPath;
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
                throw new \Exception("try to register a namespace, but given directory does not exist");
            }

            $this->namespaces[$strNamespace][] = $strDirectory;

            // Return true on success
            return true;

        } catch (\Exception $e) {

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
                throw new \Exception("File on $strFile does not exist");
            }

            $this->classMap[$strClass] = $strFile;

            // Return true on success
            return true;

        } catch (\Exception $e) {

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

        // try to find Class in Registered Namespaces
        if ($file = $this->getFromRegisteredNamespace($strClass))
        {
            return $this->classMap[$strClass] = $file;
        }

        // try to get Filepath by PSR-0 Path
        if ($file = $this->getByPSR0($strClass))
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
        foreach ($this->classExtensions as $extension) {
            $file = implode(DIRECTORY_SEPARATOR,explode('\\',$strClass)).'.'.$extension;

                if (file_exists($file))
                {
                    return $this->classMap[$strClass] = $file;
                }
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
        if (false !== $separatorPosition = strpos($strClass, '\\'))
        {
            $namespace = substr($strClass, 0, $separatorPosition);

            // exit if namespace is not in array of registered namespaces
            if (!array_key_exists($namespace,$this->namespaces)) {
                return false;
            }

            $className = substr($strClass, $separatorPosition + 1);

            // if Classname has a sub-namespace
            if (strpos($className,'\\') !== false) {
                $className = str_replace('\\',DIRECTORY_SEPARATOR,$className);
            }

            // loop through registered dirs
            foreach ($this->namespaces[$namespace] as $dir) {

                foreach ($this->classExtensions as $extension) {
                    $file = $dir.DIRECTORY_SEPARATOR.$className.'.'.$extension;
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
            $strFileName = end($arrFrag);
        } else {
            $strFileName = $strClass;
        }
        $path = $this->getRootPath();
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        /**
         * @var $dir \SplFileInfo
         */
        foreach ($it as $dir)
        {
            $filename = $dir->getBasename('.'.$dir->getExtension());
            if ($filename == $strFileName && in_array($dir->getExtension(),$this->classExtensions))
            {
                return $dir->getPathname();
            }
        }
        return false;
    }

    /**
     * get RootPath or try to get from $_SERVER['DOCUMENT_ROOT']
     * @return null|string
     */
    private function getRootPath() {

        if ($this->rootPath !== null) {
            return $this->rootPath;
        }

        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            $rootPath = $_SERVER['DOCUMENT_ROOT'];
        } else {
            $rootPath = __DIR__;
        }

        $rootPath = str_replace('//', '/', $rootPath);
        $rootPath = str_replace('\\', '/', $rootPath);
        $rootPath = dirname($rootPath);

        if (is_link($rootPath))
        {
            return readlink($rootPath);
        }
        return $this->rootPath = $rootPath;

    }
}
