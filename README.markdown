Lortnoc: A simple inversion of control framework for PHP
========================================================

What is inversion of control?
-----------------------------

**[Inversion of control (IoC)][1] is an abstract principle in software architecture design by which the traditional flow of control of a system is inverted.** Business logic is no longer the central part of the program that calls reusable subroutines to perform specific functions. Instead, reusable generic code controls the execution of problem-specific code.

**[Dependency injection (DI)][2] is a design pattern that makes inversion of control possible by separating behavior from dependency resolution, and thus decoupling highly dependent components.** A dependency injection container is the object that, given the configuration of component dependencies, is responsible for setting up and providing the components of an application.

Dependency injection eliminates the need to hard-code component implementations with creational code inside methods (that is, statements that create new instances). In this way, instead of instantiating a particular implementation of a dependent component, the main component only needs to provide a way to inject it, for example via the constructor or a setter method (no need to implement interfaces or depend upon the DI container). The implementation of the dependent component can then be changed seamlessly in the component dependency configuration of the application.

_Lortnoc_ framework also supports configuration parameters to be passed to the DI container in order to separate behavior from configuration too. By decoupling both dependency resolution and configuration from component behavior, it maximizes the configurability, modularity and reusability of components.

For an in-depth explanation, read Martin Fowler's article on [Inversion of Control Containers and the Dependency Injection pattern][3].

[1]: http://en.wikipedia.org/wiki/Inversion_of_control
[2]: http://en.wikipedia.org/wiki/Dependency_injection
[3]: http://martinfowler.com/articles/injection.html

DI container features
---------------------

- Supports configuration parameters (associative array).
- Supports constructor injection by configuring component instantiation (by class name or factory method) with its corresponding arguments.
- Supports setter injection by configuring methods to be called after components are instantiated.
- Same conventions in the configuration of arguments for constructor, factory method and setter method calls.
- Simple syntax to include references to other components (strings starting with '@') or configuration parameters (strings starting with '%') among arguments.
- Supports 3 scope types: singleton (internally cached instances), prototype (new instance created on every retrieval) or alias (reuses component configuration with different name).
- Configuration can be built upon arrays and scalars (boolean, integer, float and string). It does not need anonymous functions, instantiated objects and pre-loaded classes.
- Configuration data may be loaded directly from a PHP file or else data files in other format, like xml, yaml, ini, json, etc. (This configuration loading utility is not provided by the DI container.)

How to configure a DI container
-------------------------------

Learn by example:

```php
$components = array(
	'Chin' => array(), // implicit class name ('Chin'), no arguments
	'Mouth' => array(
		'class' => 'RegularMouth',
	), // explicit class name, no arguments
	'Hair' => array(
		'class' => 'WavyHair',
		'arguments' => array('color' => 'brown', 'length' => 3, 'bald' => FALSE),
	), // singleton, explicit class name, with arguments (array keys are optional)
	'LeftEye' => array(
		'class' => 'Eye',
		'arguments' => array('%eyeColor'),
	), // here '%eyecolor' will be replaced by the configuration parameter named 'eyecolor'
	'RightEye' => array(
		'class' => 'Eye',
		'arguments' => array('%eyeColor'),
	),
	'Nose' => array(
		'factory' => array('RegularNose', 'createFromTemplate'),
		'arguments' => array('%noseType'),
	), // creation by factory method, with arguments
	'Face' => array(
		'class' => 'RoundFace',
		'arguments' => array('%skinColor', '@LeftEye', '@RightEye', '@Nose', '@Mouth', '@Chin'),
	), // here references starting with '@' are replaced by corresponding components;
	   // for example: '@Nose' by component 'Nose'
);

$params = array(
    'eyeColor' => 'green',
    'noseType' => 2,
    'skinColor' => 0xEFD0CF,
);

$container = new Lortnoc_Container($components, $params);
$chin = $container->Chin; // returns a singleton created as in: new Chin();
$mouth = $container->Mouth; // returns a singleton created as in: new RegularMouth();
$hair = $container->Hair; // returns a singleton created as in: new WavyHair('brown', 3, FALSE);
$leftEye = $container->LeftEye; // returns a singleton created as in: new Eye('green');
$rightEye = $container->RightEye; // returns a singleton created as in: new Eye('green');
$nose = $container->Nose; // returns a singleton created as in: RegularNose::createFromTemplate(2);
$face = $container->Face; // returns a singleton created as in: new RoundFace(0xEFD0CF, $container->LeftEye, $container->RightEye, $container->Nose, $container->Mouth, $container->Chin);
```

More examples yet to be documented
----------------------------------

```php
<?php

class Blue {
	public $name = __CLASS__;
}

class Red {
	public $name = __CLASS__;
}

class Yellow {
	public $name = __CLASS__;
	public $args;
	public function __construct($str, $int, $bool, $null, $arr) {
		$this->args = array($str, $int, $bool, $null, $arr);
	}
}

class Green {
	public $name = __CLASS__;
	public $args;
	public function __construct($other, $int, $foo, $bar) {
		$this->args = array($other, $int, $foo, $bar);
	}
}

class Orange {
	public $name = __CLASS__;
	public static function make($str, $int, $bool, $null, $arr) {
		return new Orange($str, $int, $bool, $null, $arr);
	}
	public function __construct($str, $int, $bool, $null, $arr) {
		$this->args = array($str, $int, $bool, $null, $arr);
	}
}

class Black {
	public $name = __CLASS__;
	public function __construct($other=NULL) {
		$this->args = array($other);
	}
}

$components = array(
	'Blue' => array(),
	'Red' => array(
		'class' => 'Red',
	),
	'Yellow' => array(
		'class' => 'Yellow',
		'arguments' => array('foo', 123, TRUE, NULL, array(4, 5, 6)),
	),
	'Green' => array(
		'class' => 'Green',
		'arguments' => array('@Blue', '@Yellow', '%xyzzy', '%%lalala'),
	),
	'Orange' => array(
		'factory' => array('Orange', 'make'),
		'arguments' => array('foo', '@Green', '@@Green', '%spam', '%%spam'),
	),
	'Purple' => array(
		'factory' => array('Orange', 'make'),
		'arguments' => Lortnoc_Container::escape(array('foo', '@Green', '@@Green', '%spam', '%%spam')),
	),
	'Black' => array(
		'class' => 'Black',
		//'arguments' => array('@Black'),
	),
);

$params = array(
	'xyzzy' => 789,
	'spam' => array(11, 22, 33),
);
```

