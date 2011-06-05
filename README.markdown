Lortnoc: A simple inversion of control framework for PHP
========================================================

What is inversion of control?
-----------------------------

**[Inversion of control (IoC)][1] is an abstract principle in software architecture design by which the traditional flow of control of a system is inverted.** Business logic is no longer the central part of the program that calls reusable subroutines to perform specific functions. Instead, reusable generic code controls the execution of problem-specific code.

**[Dependency injection (DI)][2] is a design pattern that makes inversion of control possible by separating behavior from dependency resolution, and thus decoupling highly dependent components.** A dependency injection container is the object that, given the configuration of component dependencies, is responsible for setting up and providing the components of an application.

Dependency injection eliminates the need to hard-code component implementations with creational code inside methods (that is, statements that create new instances). In this way, instead of instantiating a particular implementation of a dependent component, the main component only needs to provide a way to inject it, for example via the constructor or a setter method (no need to implement interfaces or depend upon the DI container). The implementation of the dependent component can then be changed seamlessly in the component dependency configuration of the application.

In order to separate behavior from configuration too, _Lortnoc_ framework also supports configuration parameters to be passed to the DI container. By decoupling both dependency resolution and configuration from component behavior, it encourages optimal configurability, modularity and reusability of components.

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
- Simple syntax to include references to other components (strings starting with `'@'`) or configuration parameters (strings starting with `'%'`) among arguments.
- Supports 3 scope types: singleton (internally cached instances), prototype (new instance created on every retrieval) or alias (reuses component configuration with different name).
- Configuration can be built upon arrays and scalars (boolean, integer, float and string). It does not need anonymous functions, instantiated objects and pre-loaded classes.
- Configuration data may be loaded directly from a PHP file or else data files in other format, like xml, yaml, ini, json, etc. (This configuration loading utility is not provided by the DI container.)

How to configure a DI container
-------------------------------

Learn by example:

```php
<?php

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
$face = $container->Face; // returns a singleton created as in: new RoundFace(0xEFD0CF,
                          // $container->LeftEye, $container->RightEye, $container->Nose,
                          // $container->Mouth, $container->Chin);
```

FAQ
---

- What kind of exceptions can be thrown when getting a component?
    - `Lortnoc_Exception_ConfigError` if component configuration is detected as sintactically or semantically invalid
    - `Lortnoc_Exception_NotFound` if no such component is configured in the container
    - `Lortnoc_Exception_ReflectionError` if component cannot be created by reflection
    - `Lortnoc_Exception_DependencyLoop` if a circular dependency is detected

- Can I configure a component _not_ to be a singleton?
Yes. You can set the scope to 'prototype', which means that every time the component is retrieved, a new instance will be created. This is valid for any creation strategy, either by constructor or factory method.

- Can I have different names for the same component configuration?
Yes. You can set an 'alias' to reuse a component configuration with another name.

- How can a inject dependencies after instance construction?
You can configure setter methods to inject other components that the main component depends on, as well as to setup the component with other initialization parameters not available by constructor.

- How can I reference another component in the list of arguments?
Prepend its name with `'@'`. For example: `'@Controller'` references component identified by `'Controller'`.

- How can I reference a configuration parameter in the list of arguments?
Prepend its name with `'%'`. For example: `'%color'` references configuration parameter identified by `'color'`.

- How can I escape strings in the list of arguments that start with special characters (`'@'` or `'%'`)?
Repeat the special character in order to escape them. For example: `'%%color'` stand for the string `'%color'` and `'@@Controller'` stands for the string `'@Controller'`.

- How can I prepare runtime-dependent values for the list of arguments of a constructor or factory method?
The DI container does not support pre-calculations. The best way to achieve this is to implement a factory method or function that is able to prepare such values and return the target component, given the corresponding components and configuration parameters.

- Do argument list support keywords (associative arrays)?
Yes. But keys are ignored. Only values are taken, in the same order.


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

