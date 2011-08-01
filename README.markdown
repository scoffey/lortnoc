Lortnoc: A simple inversion of control framework for PHP
========================================================

What is inversion of control?
-----------------------------

**[Inversion of control (IoC)][1] is an abstract principle in software architecture design by which the traditional flow of control of a system is inverted.** Business logic is no longer the central part of the program that calls reusable subroutines to perform specific functions. Instead, reusable generic code controls the execution of problem-specific code.

**[Dependency injection (DI)][2] is a design pattern that makes inversion of control possible by separating behavior from dependency resolution, and thus decoupling highly dependent components.** A dependency injection container is the object that, given the configuration of component dependencies, is responsible for setting up and providing the components of an application.

Dependency injection eliminates the need to hard-code component implementations with creational code inside methods (that is, statements that create new instances). In this way, instead of instantiating a particular implementation of a dependent component, the main component only needs to provide a way to inject it, for example via the constructor or a setter method (no need to implement interfaces or depend upon the DI container). The implementation of the dependent component can then be changed seamlessly in the component dependency configuration of the application.

In order to separate behavior from configuration too, the Lortnoc framework also supports configuration parameters to be passed to the DI container. By decoupling both dependency resolution and configuration from component behavior, it encourages optimal configurability, modularity and reusability of components.

For an in-depth explanation, read Martin Fowler's article on [Inversion of Control Containers and the Dependency Injection pattern][3].

[1]: http://en.wikipedia.org/wiki/Inversion_of_control
[2]: http://en.wikipedia.org/wiki/Dependency_injection
[3]: http://martinfowler.com/articles/injection.html

Features
--------

- Supports configuration parameters (as an associative array).
- Supports constructor injection by configuring component instantiation (by class name or factory method) with its corresponding arguments.
- Supports setter injection by configuring methods to be called after components are instantiated.
- Supports property injection by configuring a map of values to be directly set on the component after being instantiated. (Properties are writeable component member variables in this context.)
- Same conventions in the configuration of arguments for constructor, factory method and setter method calls.
- Simple syntax to include references to other components (strings starting with `'@'`) or configuration parameters (strings starting with `'%'`) among arguments.
- Supports 3 scope types: singleton (internally cached instances), prototype (new instance created on every retrieval) or alias (reuses component configuration with different name).
- Configuration can be built upon arrays and scalars (boolean, integer, float and string). It does not need anonymous functions, instantiated objects and pre-loaded classes.
- Configuration data may be loaded directly from a PHP file or else data files in other format, like xml, yaml, ini, json, etc. (This configuration loading utility is not provided by the DI container.)

Getting started
---------------

A good way to learn how to configure the `Lortnoc_Container` is by example:

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
	), // here '%eyeColor' references the value of the configuration parameter named 'eyeColor'

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
	), // here strings starting with '@' reference components; e.g.: '@Nose' references component 'Nose'

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
$face = $container->Face; // returns a singleton created as in: new RoundFace(0xEFD0CF, $container->LeftEye,
                          // $container->RightEye, $container->Nose, $container->Mouth, $container->Chin);
```

FAQ
---

- Which exceptions are thrown by the `Lortnoc_Container` when getting a component?
    - `Lortnoc_Exception_ConfigError` if component configuration is syntactically or semantically invalid
    - `Lortnoc_Exception_NotFound` if no such component is configured in the container
    - `Lortnoc_Exception_ReflectionError` if component cannot be created by reflection
    - `Lortnoc_Exception_DependencyLoop` if a circular dependency is detected

- Can I configure a component _not_ to be a singleton?
Yes. You can set the scope to `'prototype'`, which means that every time the component is retrieved, a new instance will be created. This is valid for any creation strategy, either by constructor or factory method.

- Can I have different names for the same component configuration?
Yes. You can set an `'alias'` to reuse a component configuration with another name.

- How can I inject dependencies after instance construction?
You can configure setter methods to inject other components into the main one, as well as to set it up with other parameters that cannot be passed by constructor.

- How can I reference another component in the list of arguments?
Prepend its name with `'@'`. For example: `'@Controller'` references component identified by `'Controller'`.

- How can I reference a configuration parameter in the list of arguments?
Prepend its name with `'%'`. For example: `'%color'` references configuration parameter identified by `'color'`.

- How can I escape strings that start with special characters (`'@'` or `'%'`) in the list of arguments?
Repeat the special character in order to escape them. For example: `'%%color'` stand for the string `'%color'` and `'@@Controller'` stands for the string `'@Controller'`.

- Are references nested inside array values also supported?
Yes. This means that the container replaces all references in argument lists, even recursively in array values.

- How can I automatically escape strings to avoid possible references in nested arrays?
You can use the static method `Lortnoc_Container::escape` to escape values that should not be treated as references in argument lists. Note that this method can be called upon any value but only affects strings and arrays recursively.

- How can I prepare runtime-dependent values for the list of arguments of a constructor or factory method?
The recommended way to achieve this is to implement a factory method or function that is able to prepare such values and return the target component, given the corresponding components and configuration parameters.

- Do argument lists support keywords (associative arrays)?
Yes. But keys are ignored and only values are passed (in the same order) to the method or function upon being called.

- Should all component configurations be associative arrays?
Yes. But when the component is to be created by class name without arguments, another possibility is to replace the configuration array by a string (the class name) or `NULL` value (in which case the component name is assumed to be the class name).

- How can I configure properties to be set after a component is instantiated?
Include an associative array in the `'properties'` key of the component configuration. The keys stand for the names of the component member variables and the values are either the final property value or a reference to other components or configuration parameters (as in an argument list). Note that the container sets properties before calling methods when the component configuration includes both.

- How can I configure methods to be called after a component is instantiated?
Include an array in the `'methods'` key of the component configuration. Each element of that array should be an associative array containing the method name in `'method'` and the argument list in `'arguments'`. Methods are called in the given order. Note that the same method can be called multiple times.

- Which is the default value for each component configuration key?
    - `'class'`: when not present, class name is assumed to be the same as the component name (i.e., the component map key).
    - `'factory'`: when not present, creation by class name is assumed. (Precedence note: `'class'` is ignored when `'factory'` is present.)
    - `'alias'`: when not present, it just doesn't make a component alias. (Precedence note: has more precedence than the other keys.)
    - `'scope'`: defaults to `'singleton'`.
    - `'arguments'`: defaults to an empty list; i.e., no arguments. This applies to constructors, factory methods or functions and method calls.
    - `'properties'`: defaults to an empty map; i.e., no properties to be set.
    - `'methods'`: defaults to an empty list; i.e., no methods to be called.

Roadmap
-------

Any feedback on why it's important to have some feature or not is welcome! And will help me set priorities for future development.

Planned features:

- References in array callbacks: This would allow to create components by calling factory methods on other components in the container.

- Reference to self (container): This would allow to reference the container in argument lists.

- Take arguments literally, without dereference (finalargs): This would eliminate the need to escape argument lists when it is clear that no references are used.

- Builder that provides fluent interface to configure container: This would be useful to generate configurations on runtime by chaining method calls in a readable form.

- Auto-configuration via annotations: This would help to minimize component configuration.

- Session scope using a `Zend_Session_Namespace`: This would allow the container to save state across a session, as in many DI frameworks used for web development.

Other discarded or low priority ideas:

- Use class constants for conventional strings: Maybe for the sake of programming style.

- Preload all singletons, without lazy instantiation: This might be useful for validation, but it is already possible by iterating on the configured components and getting every instance. 

- Implement `IteratorAggregate` to iterate on component instances: This behavior overloading might be confusing in a DI container.

- Implement `arrayaccess` to access configuration parameters: This behavior overloading might be confusing in a DI container.

- Hooks before/after instance created, properties set and methods called: This adds a lot of complexity to the container that could be solved with proper factory methods.

Reminder: Always:

- Check style

- Check unit test coverage

- Improve documentation
