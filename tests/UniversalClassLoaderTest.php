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

namespace dtomasi\tests;

use customNamespace\classInCustomNamespace;
use dtomasi\UniversalClassLoader;

require ('src/UniversalClassLoader.php');
require ('tests/Registry.php');

$cl = new UniversalClassLoader(false);
$cl->register();
\Registry::set('ClassLoader',$cl);

class UniversalClassLoaderTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);
    }

    public function tearDown() {
        unset($_SERVER['DOCUMENT_ROOT']);
    }
    /**
     * @return bool| \dtomasi\UniversalClassLoader
     */
    public function classLoader()
    {
        return \Registry::get('ClassLoader');
    }

    public function testRegisterNamespaceReturnTrueOnSuccess()
    {
        $this->assertTrue($this->classLoader()->registerNamespace('tests','tests'));
    }

    public function testRegisterClassReturnsTrueOnSuccess()
    {
        $this->assertTrue($this->classLoader()->registerClass('UniversalClassLoader','src/UniversalClassLoader.php'));
    }

    public function testRegisterClass()
    {
        $this->classLoader()->registerClass('RegisteredClass','tests/notRegisteredNamespace/RegisteredClass.php');
        $class = new \RegisteredClass();
        $this->assertInstanceOf('RegisteredClass',$class);
    }

    public function testFindByPsr0()
    {
        $class = new \tests\notRegisteredNamespace\FindByPSR0();
        $this->assertInstanceOf('\tests\notRegisteredNamespace\FindByPSR0',$class);
    }

    public function testFindClassInCustomNamespace()
    {
        $this->classLoader()->registerNamespace('customNamespace','tests/customNamespace');
        $class = new classInCustomNamespace();
        $this->assertInstanceOf('\customNamespace\classInCustomNamespace',$class);
    }

    public function testFindClassInFileSystem()
    {
        $class = new \ClassForSearchInFileSystem();
        $this->assertInstanceOf('\ClassForSearchInFileSystem',$class);
    }
}
 