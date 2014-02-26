<?php
/**
 * Created by PhpStorm.
 * User: dtomasi
 * Date: 25.02.14
 * Time: 10:42
 */

namespace tests;

use customNamespace\classInCustomNamespace;

require ('ClassLoader.php');
require ('tests/Registry.php');

$cl = new \ClassLoader(false);
$cl->register();
\Registry::set('ClassLoader',$cl);

class ClassLoaderTest extends \PHPUnit_Framework_TestCase {

    /**
     * @return bool|\ClassLoader
     */
    public function classLoader()
    {
        return \Registry::get('ClassLoader');
    }
    /**
     * Setup Tests
     */
    public function testRegisterNamespaceThrowsException()
    {
        $this->setExpectedException('Exception',"try to register a namespace, but given directory does not exist");
        $this->classLoader()->registerNamespace('test','notExistingDir');
    }

    public function testRegisterNamespaceReturnTrueOnSuccess()
    {
        $this->assertTrue($this->classLoader()->registerNamespace('tests','tests'));
    }

    public function testRegisterClassThrowsException()
    {
        $strFile = "testClass.notExisting";
        $this->setExpectedException('Exception',"File on $strFile does not exist");
        $this->classLoader()->registerClass('test',$strFile);
    }

    public function testRegisterClassReturnsTrueOnSuccess()
    {
        $this->assertTrue($this->classLoader()->registerClass('ClassLoader','ClassLoader.php'));
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
 