<?php

// How to run tests: Run this command at the project root:
// phpunit --include-path src test

require_once 'Lortnoc/Container.php';

/**
 * Test case for Lortnoc_Container.
 * 
 * @author scoffey
 *
 */
class Lortnoc_ContainerTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * @covers Lortnoc_Container::__construct
     */
    public function testConstructor() {
        // Only tests the default constructor; for non-default arguments,
        // see getComponents and getConfigParams tests
        $container = new Lortnoc_Container();
        $this->assertEmpty($container->getComponents());
        $this->assertEmpty($container->getConfigParams());
    }
    
    /**
     * @covers Lortnoc_Container::merge
     */
    public function testMerge() {
        $container = new Lortnoc_Container();
        $components = array(
            'Lortnoc_ContainerTest_Alpha' => array(
                'class' => 'Lortnoc_ContainerTest_Alpha',
            ),
            'dummy' => array(
                'alias' => 'Lortnoc_ContainerTest_Alpha',
            ),
        );
        $params = array('foo' => 'bar', 'baz' => 42);
        $container->merge($components, $params);
        $this->assertEquals($components, $container->getComponents());
        $this->assertEquals($params, $container->getConfigParams());
        $moreComponents = array(
            'Lortnoc_ContainerTest_Beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
            ),
            'dummy' => array(
                'alias' => 'Lortnoc_ContainerTest_Beta',
            ),
        );
        $moreConfig = array('quux' => TRUE, 'baz' => 31);
        $container->merge($moreComponents, $moreConfig);
        $this->assertEquals(array_merge($components, $moreComponents),
                $container->getComponents());
        $this->assertEquals(array_merge($params, $moreConfig),
                $container->getConfigParams());
    }
    
    /**
     * @covers Lortnoc_Container::dereference
     */
    public function testDereference() {
        $components = array(
            'Lortnoc_ContainerTest_Alpha' => array(
                'class' => 'Lortnoc_ContainerTest_Alpha',
            ),
            'Lortnoc_ContainerTest_Beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
            ),
        );
        $params = array('foo' => 'bar', 'baz' => 42);
        $container = new Lortnoc_Container($components, $params);
        $instance = $container->getComponent('Lortnoc_ContainerTest_Alpha');
        $other = $container->getComponent('Lortnoc_ContainerTest_Beta');
        $this->assertEquals('quux', $container->dereference('quux'));
        $this->assertEquals(31, $container->dereference(31));
        $this->assertEquals(TRUE, $container->dereference(TRUE));
        $this->assertEquals(NULL, $container->dereference(NULL));
        $this->assertEquals(1.5, $container->dereference(1.5));
        $this->assertEquals('Lortnoc_ContainerTest_Alpha',
                $container->dereference('Lortnoc_ContainerTest_Alpha'));
        $this->assertEquals($instance,
                $container->dereference('@Lortnoc_ContainerTest_Alpha'));
        $this->assertEquals('@Lortnoc_ContainerTest_Alpha',
                $container->dereference('@@Lortnoc_ContainerTest_Alpha'));
        $this->assertEquals('foo', $container->dereference('foo'));
        $this->assertEquals($container->getConfigParam('foo'),
                $container->dereference('%foo'));
        $this->assertEquals('%foo', $container->dereference('%%foo'));
        $value = array('quux', 31, TRUE, NULL, 1.5,
                array(11, 'a', array(22, 'b'), 33, 'c'), 'z');
        $this->assertEquals($value, $container->dereference($value));
        $value = array('%foo', 31, TRUE, NULL, 1.5,
                array(11, 'a', array(22, '@Lortnoc_ContainerTest_Alpha'),
                33, '%baz'), '@Lortnoc_ContainerTest_Beta');
        $expected = array('bar', 31, TRUE, NULL, 1.5,
                array(11, 'a', array(22, $instance), 33, 42), $other);
        $this->assertEquals($expected, $container->dereference($value));
    }
    
    /**
     * @covers Lortnoc_Container::escape
     */
    public function testEscape() {
        $this->assertEquals('quux', Lortnoc_Container::escape('quux'));
        $this->assertEquals(31, Lortnoc_Container::escape(31));
        $this->assertEquals(TRUE, Lortnoc_Container::escape(TRUE));
        $this->assertEquals(NULL, Lortnoc_Container::escape(NULL));
        $this->assertEquals(1.5, Lortnoc_Container::escape(1.5));
        $this->assertEquals('Lortnoc_ContainerTest_Alpha',
                Lortnoc_Container::escape('Lortnoc_ContainerTest_Alpha'));
        $this->assertEquals('@@Lortnoc_ContainerTest_Alpha',
                Lortnoc_Container::escape('@Lortnoc_ContainerTest_Alpha'));
        $this->assertEquals('@@@Lortnoc_ContainerTest_Alpha',
                Lortnoc_Container::escape('@@Lortnoc_ContainerTest_Alpha'));
        $this->assertEquals('foo', Lortnoc_Container::escape('foo'));
        $this->assertEquals('%%foo', Lortnoc_Container::escape('%foo'));
        $this->assertEquals('%%%foo', Lortnoc_Container::escape('%%foo'));
        $value = array('quux', 31, TRUE, NULL, 1.5,
                array(11, 'a', array(22, 'b'), 33, 'c'), 'z');
        $this->assertEquals($value, Lortnoc_Container::escape($value));
        $value = array('%foo', 31, TRUE, NULL, 1.5,
                array(11, 'a', array(22, '@Lortnoc_ContainerTest_Alpha'),
                33, '%baz'), '@Lortnoc_ContainerTest_Beta');
        $expected = array('%%foo', 31, TRUE, NULL, 1.5,
                array(11, 'a', array(22, '@@Lortnoc_ContainerTest_Alpha'),
                33, '%%baz'), '@@Lortnoc_ContainerTest_Beta');
        $this->assertEquals($expected, Lortnoc_Container::escape($value));
    }
    
    /**
     * @covers Lortnoc_Container::repr
     */
    public function testRepr() {
        $this->assertEquals('"quux"', Lortnoc_Container::repr('quux'));
        $this->assertEquals('31', Lortnoc_Container::repr(31));
        $this->assertEquals('true', Lortnoc_Container::repr(TRUE));
        $this->assertEquals('null', Lortnoc_Container::repr(NULL));
        $this->assertEquals('1.5', Lortnoc_Container::repr(1.5));
        $value = array('quux', 31, TRUE, NULL, 1.5,
                array(11, 'a', array(22, 'b'), 33, 'c'), 'z');
        $this->assertEquals('["quux",31,true,null,1.5,[11,"a",[22,"b"],'
                . '33,"c"],"z"]', Lortnoc_Container::repr($value));
        $value = array('spam' => 'eggs', 'foo' => 31, 'bar' => TRUE,
                'baz' => array('quux' => 'xyzzy'));
        $this->assertEquals('{"spam":"eggs","foo":31,"bar":true,'
                . '"baz":{"quux":"xyzzy"}}', Lortnoc_Container::repr($value));
        $value = (object) array('spam' => 'eggs', 'foo' => 31, 'bar' => TRUE,
                'baz' => (object) array('quux' => 'xyzzy'));
        $this->assertEquals('{"spam":"eggs","foo":31,"bar":true,'
                . '"baz":{"quux":"xyzzy"}}', Lortnoc_Container::repr($value));
    }
    
    /**
     * @covers Lortnoc_Container::getConfigParams
     */
    public function testGetConfigParams() {
        $params = array('foo' => 'bar', 'baz' => 42);
        $container = new Lortnoc_Container(array(), $params);
        $this->assertEquals($params, $container->getConfigParams());
    }
    
    /**
     * @covers Lortnoc_Container::getConfigParam
     */
    public function testGetConfigParam() {
        $params = array('foo' => 'bar', 'baz' => 42);
        $container = new Lortnoc_Container(array(), $params);
        $this->assertEquals('bar', $container->getConfigParam('foo'));
        $this->assertEquals(42, $container->getConfigParam('baz'));
        $this->assertEquals(NULL, $container->getConfigParam('quux'));
        $this->assertEquals('x', $container->getConfigParam('quux', 'x'));
    }
    
    /**
     * @covers Lortnoc_Container::setConfigParam
     */
    public function testSetConfigParam() {
        $params = array('foo' => 'bar');
        $container = new Lortnoc_Container(array(), $params);
        $this->assertEquals('bar', $container->getConfigParam('foo'));
        $container->setConfigParam('foo', 'oof');
        $this->assertEquals('oof', $container->getConfigParam('foo'));
        $this->assertEquals(NULL, $container->getConfigParam('quux'));
        $container->setConfigParam('quux', 'xuuq');
        $this->assertEquals('xuuq', $container->getConfigParam('quux'));
    }
    
    /**
     * @covers Lortnoc_Container::getComponents
     */
    public function testGetComponents() {
        $components = array(
            'Lortnoc_ContainerTest_Alpha' => array(
                'class' => 'Lortnoc_ContainerTest_Alpha',
            ),
            'Lortnoc_ContainerTest_Beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
            ),
        );
        $container = new Lortnoc_Container($components);
        $this->assertEquals($components, $container->getComponents());
    }
    
    /**
     * @covers Lortnoc_Container::getComponent
     */
    public function testGetComponent() {
        $components = array(
            'Lortnoc_ContainerTest_Alpha' => array(
                'class' => 'Lortnoc_ContainerTest_Alpha',
            ),
            'Lortnoc_ContainerTest_Beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
            ),
        );
        $container = new Lortnoc_Container($components);
        $instance = new Lortnoc_ContainerTest_Alpha();
        $this->assertEquals($instance, $container->getComponent(
                'Lortnoc_ContainerTest_Alpha'));
    }
    
    /**
     * @covers Lortnoc_Container::setComponent
     */
    public function testSetComponent() {
        $container = new Lortnoc_Container();
        $instance = new Lortnoc_ContainerTest_Alpha();
        $retval = $container->setComponent('Lortnoc_ContainerTest_Alpha',
                $instance);
        $this->assertEquals($instance, $container->getComponent(
                'Lortnoc_ContainerTest_Alpha'));
        $this->assertSame($container, $retval);
    }
    
    /**
     * @covers Lortnoc_Container::hasComponent
     */
    public function testHasComponent() {
        $components = array(
            'Lortnoc_ContainerTest_Alpha' => array(
                'class' => 'Lortnoc_ContainerTest_Alpha',
            ),
            'Lortnoc_ContainerTest_Beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
            ),
        );
        $container = new Lortnoc_Container($components);
        $instance = $container->getComponent(
                'Lortnoc_ContainerTest_Alpha');
        $this->assertTrue($container->hasComponent(
                'Lortnoc_ContainerTest_Alpha'));
        $this->assertTrue($container->hasComponent(
                'Lortnoc_ContainerTest_Beta'));
        $this->assertFalse($container->hasComponent('tmp'));
        $container->setComponent('tmp', (object) array('foo' => 'bar'));
        $this->assertTrue($container->hasComponent('tmp'));
    }
    
    /**
     * @covers Lortnoc_Container::clearComponent
     */
    public function testClearComponent() {
        $components = array(
            'Lortnoc_ContainerTest_Alpha' => array(
                'class' => 'Lortnoc_ContainerTest_Alpha',
            ),
            'Lortnoc_ContainerTest_Beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
            ),
        );
        $container = new Lortnoc_Container($components);
        $i = $container->getComponent('Lortnoc_ContainerTest_Beta');
        $i->name = 'test';
        $j = $container->getComponent('Lortnoc_ContainerTest_Beta');
        $this->assertSame($i, $j);
        $retval = $container->clearComponent(
                'Lortnoc_ContainerTest_Beta');
        $this->assertTrue($container->hasComponent(
                'Lortnoc_ContainerTest_Beta'));
        $k = $container->getComponent('Lortnoc_ContainerTest_Beta');
        $this->assertNotSame($i, $k);
        $this->assertSame($container, $retval);
    }
    
    /**
     * @covers Lortnoc_Container::__get
     * @covers Lortnoc_Container::__set
     * @covers Lortnoc_Container::__isset
     * @covers Lortnoc_Container::__unset
     */
    public function testPropertyOverloadingMethods() {
        $components = array(
            'Lortnoc_ContainerTest_Alpha' => array(
                'class' => 'Lortnoc_ContainerTest_Alpha',
            ),
            'Lortnoc_ContainerTest_Beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
            ),
        );
        $container = new Lortnoc_Container($components);
        $instance = new Lortnoc_ContainerTest_Alpha();
        $this->assertEquals($instance,
                $container->Lortnoc_ContainerTest_Alpha);
        $component = (object) array('foo' => 'bar');
        $this->assertFalse(isset($container->test));
        $container->test = $component;
        $this->assertTrue(isset($container->test));
        $this->assertEquals($component, $container->test);
        unset($container->test);
        $this->assertFalse(isset($container->test));
    }
    
    /**
     * @covers Lortnoc_Container::getInstance
     */
    public function testGetComponentReturningCachedSingleton() {
        $components = array(
            'Lortnoc_ContainerTest_Beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
            ),
        );
        $container = new Lortnoc_Container($components);
        $instance = $container->getComponent('Lortnoc_ContainerTest_Beta');
        $this->assertEquals('Lortnoc_ContainerTest_Beta', $instance->name);
        $instance->name = 'Test';
        $other = $container->getComponent('Lortnoc_ContainerTest_Beta');
        $this->assertEquals('Test', $other->name);
    }
    
    /**
     * @covers Lortnoc_Container::getInstance
     */
    public function testGetComponentThrowingNotFoundException() {
        $components = array(
            'Lortnoc_ContainerTest_Beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
            ),
        );
        $container = new Lortnoc_Container($components);
        $this->setExpectedException('Lortnoc_Exception_NotFound',
                'Component not found in container: '
                . '"Lortnoc_ContainerTest_Alpha"');
        $instance = $container->getComponent('Lortnoc_ContainerTest_Alpha');
    }
    
    /**
     * @covers Lortnoc_Container::getInstance
     */
    public function testGetComponentThrowingDependencyLoopException() {
        $components = array(
            'Lortnoc_ContainerTest_Gamma' => array(
                'class' => 'Lortnoc_ContainerTest_Gamma',
                'arguments' => array('@Lortnoc_ContainerTest_Delta'),
            ),
            'Lortnoc_ContainerTest_Delta' => array(
                'class' => 'Lortnoc_ContainerTest_Delta',
                'arguments' => array(1, '@Lortnoc_ContainerTest_Epsilon'),
            ),
            'Lortnoc_ContainerTest_Epsilon' => array(
                'class' => 'Lortnoc_ContainerTest_Epsilon',
                'arguments' => array(2, 3, '@Lortnoc_ContainerTest_Gamma'),
            ),
        );
        $container = new Lortnoc_Container($components);
        $this->setExpectedException('Lortnoc_Exception_DependencyLoop',
                'Dependency loop detected: Lortnoc_ContainerTest_Gamma -> '
                . 'Lortnoc_ContainerTest_Delta -> Lortnoc_ContainerTest_Epsilon'
                . ' -> Lortnoc_ContainerTest_Gamma');
        $instance = $container->getComponent('Lortnoc_ContainerTest_Gamma');
    }
    
    /**
     * @covers Lortnoc_Container::getInstance
     */
    public function testGetComponentThrowingConfigErrorIfNotArray() {
    	// Also tests other possible configuration types (NULL and string)
        $components = array(
            'Lortnoc_ContainerTest_Alpha' => NULL,
            'beta' => 'Lortnoc_ContainerTest_Beta',
            'Lortnoc_ContainerTest_Beta' => array(),
            'bogus' => 1,
        );
        $container = new Lortnoc_Container($components);
        $instance = $container->getComponent('Lortnoc_ContainerTest_Alpha');
        $this->assertInstanceOf('Lortnoc_ContainerTest_Alpha', $instance);
        $instance = $container->getComponent('beta');
        $this->assertInstanceOf('Lortnoc_ContainerTest_Beta', $instance);
        $instance = $container->getComponent('Lortnoc_ContainerTest_Beta');
        $this->assertInstanceOf('Lortnoc_ContainerTest_Beta', $instance);
        $this->setExpectedException('Lortnoc_Exception_ConfigError',
                'Component configuration is not an array: 1');
        $instance = $container->getComponent('bogus');
    }
    
    /**
     * @covers Lortnoc_Container::getInstance
     */
    public function testGetComponentReturningComponentByAlias() {
        $components = array(
            'beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
            ),
            'anotherNameForBeta' => array(
                'alias' => 'beta',
            ),
        );
        $container = new Lortnoc_Container($components);
        $instance = $container->getComponent('beta');
        $instance->name = 'Test';
        $other = $container->getComponent('anotherNameForBeta');
        $this->assertSame($instance, $other);
        $instance = $container->getComponent('beta');
        $this->assertSame($instance, $other);
    }
    
    /**
     * @covers Lortnoc_Container::getInstance
     */
    public function testGetComponentReturningComponentAsPrototype() {
        $components = array(
            'beta' => array(
                'class' => 'Lortnoc_ContainerTest_Beta',
                'scope' => 'prototype',
            ),
        );
        $container = new Lortnoc_Container($components);
        $instance = $container->getComponent('beta');
        $other = $container->getComponent('beta');
        $this->assertNotSame($instance, $other);
    }
    
    /**
     * @covers Lortnoc_Container::create
     */
    public function testGetComponentCreatedWithoutArguments() {
    }
    
    /**
     * @covers Lortnoc_Container::createFromFactory
     */
    public function testGetComponentCreatedFromFactory() {
    }
    
    /**
     * @covers Lortnoc_Container::createFromFactory
     */
    public function testGetComponentCreatedFromNonCallableFactory() {
    }
    
    /**
     * @covers Lortnoc_Container::createFromFactory
     */
    public function testGetComponentCreatedFromFactoryWithBadArguments() {
    }
    
    /**
     * @covers Lortnoc_Container::createFromClass
     */
    public function testGetComponentCreatedFromClass() {
    }
    
    /**
     * @covers Lortnoc_Container::createFromClass
     */
    public function testGetComponentCreatedFromNonExistingClass() {
    }
    
    /**
     * @covers Lortnoc_Container::createFromClass
     */
    public function testGetComponentCreatedFromClassWithBadArguments() {
    }
    
    /**
     * @covers Lortnoc_Container::create
     */
    public function testGetComponentWithPropertiesSet() {
    }
    
    /**
     * @covers Lortnoc_Container::callMethod
     */
    public function testGetComponentWithMethodCalls() {
    }
    
    /**
     * @covers Lortnoc_Container::callMethod
     */
    public function testGetComponentWithMethodCallsThrowingConfigError() {
    }
    
    /**
     * @covers Lortnoc_Container::callMethod
     */
    public function testGetComponentWithMethodCallsThrowingReflectionError() {
    }
    
    /**
     * @covers Lortnoc_Container::callMethod
     */
    public function testGetComponentWithMethodCallsWithBadArguments() {
    }
    
}

/**
 * Helper inner class.
 *
 */
class Lortnoc_ContainerTest_Alpha
{
}

/**
 * Helper inner class.
 *
 */
class Lortnoc_ContainerTest_Beta
{
    public $name = __CLASS__;
}

/**
 * Helper inner class.
 *
 */
class Lortnoc_ContainerTest_Gamma
{
    public $a;
    public function __constructor($a) {
        $this->a = $a;
    }
}

/**
 * Helper inner class.
 *
 */
class Lortnoc_ContainerTest_Delta
{
    public $a;
    public $b;
    public function __constructor($a, $b) {
        $this->a = $a;
        $this->b = $b;
    }
}

/**
 * Helper inner class.
 *
 */
class Lortnoc_ContainerTest_Epsilon
{
    public $a;
    public $b;
    public $c;
    public function __constructor($a, $b, $c) {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }
}

