<?php

require_once 'Lortnoc/Container.php';

class Lortnoc_ContainerTest extends PHPUnit_Framework_TestCase {
	
	public function testConstructor() {
		// this only tests the default constructor; for non-default arguments,
		// see getComponents and getConfigParams tests
		$container = new Lortnoc_Container();
		$this->assertEmpty($container->getComponents());
		$this->assertEmpty($container->getConfigParams());
	}
	
	public function testMerge() {
		$container = new Lortnoc_Container();
		$components = array(
			'Lortnoc_ContainerTest_DummyClass' => array(
				'class' => 'Lortnoc_ContainerTest_DummyClass',
			),
			'dummy' => array(
				'alias' => 'Lortnoc_ContainerTest_DummyClass',
			),
		);
		$params = array('foo' => 'bar', 'baz' => 42);
		$container->merge($components, $params);
		$this->assertEquals($components, $container->getComponents());
		$this->assertEquals($params, $container->getConfigParams());
		$moreComponents = array(
			'Lortnoc_ContainerTest_AnotherClass' => array(
				'class' => 'Lortnoc_ContainerTest_AnotherClass',
			),
			'dummy' => array(
				'alias' => 'Lortnoc_ContainerTest_AnotherClass',
			),
		);
		$moreConfig = array('quux' => TRUE, 'baz' => 31);
		$container->merge($moreComponents, $moreConfig);
		$this->assertEquals(array_merge($components, $moreComponents),
				$container->getComponents());
		$this->assertEquals(array_merge($params, $moreConfig),
				$container->getConfigParams());
	}
	
	public function testDereference() {
		$components = array(
			'Lortnoc_ContainerTest_DummyClass' => array(
				'class' => 'Lortnoc_ContainerTest_DummyClass',
			),
			'Lortnoc_ContainerTest_AnotherClass' => array(
				'class' => 'Lortnoc_ContainerTest_AnotherClass',
			),
		);
		$params = array('foo' => 'bar', 'baz' => 42);
		$container = new Lortnoc_Container($components, $params);
		$instance = $container->getComponent('Lortnoc_ContainerTest_DummyClass');
		$other = $container->getComponent('Lortnoc_ContainerTest_AnotherClass');
		$this->assertEquals('quux', $container->dereference('quux'));
		$this->assertEquals(31, $container->dereference(31));
		$this->assertEquals(TRUE, $container->dereference(TRUE));
		$this->assertEquals(NULL, $container->dereference(NULL));
		$this->assertEquals(1.5, $container->dereference(1.5));
		$this->assertEquals('Lortnoc_ContainerTest_DummyClass',
				$container->dereference('Lortnoc_ContainerTest_DummyClass'));
		$this->assertEquals($instance,
				$container->dereference('@Lortnoc_ContainerTest_DummyClass'));
		$this->assertEquals('@Lortnoc_ContainerTest_DummyClass',
				$container->dereference('@@Lortnoc_ContainerTest_DummyClass'));
		$this->assertEquals('foo', $container->dereference('foo'));
		$this->assertEquals($container->getConfigParam('foo'),
				$container->dereference('%foo'));
		$this->assertEquals('%foo', $container->dereference('%%foo'));
		$value = array('quux', 31, TRUE, NULL, 1.5,
				array(11, 'a', array(22, 'b'), 33, 'c'), 'z');
		$this->assertEquals($value, $container->dereference($value));
		$value = array('%foo', 31, TRUE, NULL, 1.5,
				array(11, 'a', array(22, '@Lortnoc_ContainerTest_DummyClass'),
				33, '%baz'), '@Lortnoc_ContainerTest_AnotherClass');
		$expected = array('bar', 31, TRUE, NULL, 1.5,
				array(11, 'a', array(22, $instance), 33, 42), $other);
		$this->assertEquals($expected, $container->dereference($value));
	}
	
	public function testEscape() {
		$this->assertEquals('quux', Lortnoc_Container::escape('quux'));
		$this->assertEquals(31, Lortnoc_Container::escape(31));
		$this->assertEquals(TRUE, Lortnoc_Container::escape(TRUE));
		$this->assertEquals(NULL, Lortnoc_Container::escape(NULL));
		$this->assertEquals(1.5, Lortnoc_Container::escape(1.5));
		$this->assertEquals('Lortnoc_ContainerTest_DummyClass',
				Lortnoc_Container::escape('Lortnoc_ContainerTest_DummyClass'));
		$this->assertEquals('@@Lortnoc_ContainerTest_DummyClass',
				Lortnoc_Container::escape('@Lortnoc_ContainerTest_DummyClass'));
		$this->assertEquals('@@@Lortnoc_ContainerTest_DummyClass',
				Lortnoc_Container::escape('@@Lortnoc_ContainerTest_DummyClass'));
		$this->assertEquals('foo', Lortnoc_Container::escape('foo'));
		$this->assertEquals('%%foo', Lortnoc_Container::escape('%foo'));
		$this->assertEquals('%%%foo', Lortnoc_Container::escape('%%foo'));
		$value = array('quux', 31, TRUE, NULL, 1.5,
				array(11, 'a', array(22, 'b'), 33, 'c'), 'z');
		$this->assertEquals($value, Lortnoc_Container::escape($value));
		$value = array('%foo', 31, TRUE, NULL, 1.5,
				array(11, 'a', array(22, '@Lortnoc_ContainerTest_DummyClass'),
				33, '%baz'), '@Lortnoc_ContainerTest_AnotherClass');
		$expected = array('%%foo', 31, TRUE, NULL, 1.5,
				array(11, 'a', array(22, '@@Lortnoc_ContainerTest_DummyClass'),
				33, '%%baz'), '@@Lortnoc_ContainerTest_AnotherClass');
		$this->assertEquals($expected, Lortnoc_Container::escape($value));
	}
	
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
	
	public function testGetConfigParams() {
		$params = array('foo' => 'bar', 'baz' => 42);
		$container = new Lortnoc_Container(array(), $params);
		$this->assertEquals($params, $container->getConfigParams());
	}
	
	public function testGetConfigParam() {
		$params = array('foo' => 'bar', 'baz' => 42);
		$container = new Lortnoc_Container(array(), $params);
		$this->assertEquals('bar', $container->getConfigParam('foo'));
		$this->assertEquals(42, $container->getConfigParam('baz'));
		$this->assertEquals(NULL, $container->getConfigParam('quux'));
		$this->assertEquals('x', $container->getConfigParam('quux', 'x'));
	}
	
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
	
	public function testGetComponents() {
		$components = array(
			'Lortnoc_ContainerTest_DummyClass' => array(
				'class' => 'Lortnoc_ContainerTest_DummyClass',
			),
			'Lortnoc_ContainerTest_AnotherClass' => array(
				'class' => 'Lortnoc_ContainerTest_AnotherClass',
			),
		);
		$container = new Lortnoc_Container($components);
		$this->assertEquals($components, $container->getComponents());
	}
	
	public function testGetComponent() {
		$components = array(
			'Lortnoc_ContainerTest_DummyClass' => array(
				'class' => 'Lortnoc_ContainerTest_DummyClass',
			),
			'Lortnoc_ContainerTest_AnotherClass' => array(
				'class' => 'Lortnoc_ContainerTest_AnotherClass',
			),
		);
		$container = new Lortnoc_Container($components);
		$instance = new Lortnoc_ContainerTest_DummyClass();
		$this->assertEquals($instance, $container->getComponent(
				'Lortnoc_ContainerTest_DummyClass'));
	}
	
	public function testSetComponent() {
		$container = new Lortnoc_Container();
		$instance = new Lortnoc_ContainerTest_DummyClass();
		$retval = $container->setComponent('Lortnoc_ContainerTest_DummyClass',
				$instance);
		$this->assertEquals($instance, $container->getComponent(
				'Lortnoc_ContainerTest_DummyClass'));
		$this->assertSame($retval, $container);
	}
	
	public function testHasComponent() {
		$components = array(
			'Lortnoc_ContainerTest_DummyClass' => array(
				'class' => 'Lortnoc_ContainerTest_DummyClass',
			),
			'Lortnoc_ContainerTest_AnotherClass' => array(
				'class' => 'Lortnoc_ContainerTest_AnotherClass',
			),
		);
		$container = new Lortnoc_Container($components);
		$instance = $container->getComponent(
				'Lortnoc_ContainerTest_DummyClass');
		$this->assertTrue($container->hasComponent(
				'Lortnoc_ContainerTest_DummyClass'));
		$this->assertTrue($container->hasComponent(
				'Lortnoc_ContainerTest_AnotherClass'));
		$this->assertFalse($container->hasComponent('tmp'));
		$container->setComponent('tmp', (object) array('foo' => 'bar'));
		$this->assertTrue($container->hasComponent('tmp'));
	}
	
	public function testClearComponent() {
		$components = array(
			'Lortnoc_ContainerTest_DummyClass' => array(
				'class' => 'Lortnoc_ContainerTest_DummyClass',
			),
			'Lortnoc_ContainerTest_AnotherClass' => array(
				'class' => 'Lortnoc_ContainerTest_AnotherClass',
			),
		);
		$container = new Lortnoc_Container($components);
		$i = $container->getComponent('Lortnoc_ContainerTest_AnotherClass');
		$i->name = 'test';
		$j = $container->getComponent('Lortnoc_ContainerTest_AnotherClass');
		$this->assertSame($i, $j);
		$retval = $container->clearComponent(
				'Lortnoc_ContainerTest_AnotherClass');
		$this->assertTrue($container->hasComponent(
				'Lortnoc_ContainerTest_AnotherClass'));
		$k = $container->getComponent('Lortnoc_ContainerTest_AnotherClass');
		$this->assertNotSame($i, $k);
	}
	
	public function testPropertyOverloadingMethods() {
		$components = array(
			'Lortnoc_ContainerTest_DummyClass' => array(
				'class' => 'Lortnoc_ContainerTest_DummyClass',
			),
			'Lortnoc_ContainerTest_AnotherClass' => array(
				'class' => 'Lortnoc_ContainerTest_AnotherClass',
			),
		);
		$container = new Lortnoc_Container($components);
		$instance = new Lortnoc_ContainerTest_DummyClass();
		$this->assertEquals($instance,
				$container->Lortnoc_ContainerTest_DummyClass);
		$component = (object) array('foo' => 'bar');
		$this->assertFalse(isset($container->test));
		$container->test = $component;
		$this->assertTrue(isset($container->test));
		$this->assertEquals($component, $container->test);
		unset($container->test);
		$this->assertFalse(isset($container->test));
	}
	
	public function testGetComponentReturningCachedSingleton() {
	}
	
	public function testGetComponentThrowingNotFoundException() {
	}
	
	public function testGetComponentThrowingDependencyLoopException() {
	}
	
	public function testGetComponentThrowingConfigErrorIfNotArray() {
	}
	
	public function testGetComponentReturningComponentByAlias() {
	}
	
	public function testGetComponentReturningComponentAsPrototype() {
	}
	
	public function testGetComponentCreatedWithoutArguments() {
	}
	
	public function testGetComponentCreatedFromFactory() {
	}
	
	public function testGetComponentCreatedFromNonCallableFactory() {
	}
	
	public function testGetComponentCreatedFromFactoryWithBadArguments() {
	}
	
	public function testGetComponentCreatedFromClass() {
	}
	
	public function testGetComponentCreatedFromNonExistingClass() {
	}
	
	public function testGetComponentCreatedFromClassWithBadArguments() {
	}
	
	public function testGetComponentWithMethodCalls() {
	}
	
	public function testGetComponentWithMethodCallsThrowingConfigError() {
	}
	
	public function testGetComponentWithMethodCallsThrowingReflectionError() {
	}
	
	public function testGetComponentWithMethodCallsWithBadArguments() {
	}
	
}

class Lortnoc_ContainerTest_DummyClass {
}

class Lortnoc_ContainerTest_AnotherClass {
	public $name = __CLASS__;
}

class Lortnoc_ContainerTest_YetAnotherClass {
}
