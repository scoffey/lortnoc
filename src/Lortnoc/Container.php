<?php

// Copyright (C) 2011 by Santiago Coffey <scoffey@itba.edu.ar>
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

require_once 'Exception.php';

/**
 * Lortnoc_Container is a dependency injection (DI) container.
 * 
 * See library documentation for examples on how to setup a DI container
 * with an array of component dependencies and an array of configuration
 * parameters.
 * 
 * @author scoffey
 *
 */
class Lortnoc_Container
{

    /**
     * Component dependencies configuration map.
     * 
     * @var array(string => array(string => mixed))
     */
    protected $components;
    
    /**
     * Configuration parameters map.
     * 
     * @var array(string => mixed)
     */
    protected $params;
    
    /**
     * Map of cached component instances. Holds singleton instances and other
     * objects set in the container.
     * 
     * @var array(string => mixed)
     */
    protected $instances;
    
    /**
     * Internal list used for detecting dependency loops.
     * 
     * @var array(string)
     */
    protected $solving;

    /**
     * Helper method that returns a readable representation of any object.
     * 
     * @param mixed $object
     * @return string
     */
    public static function repr($object) {
        $json = json_encode($object);
        return ($json ? $json : (string) $object);
    }
    
    /**
     * Escapes a value that must not be parsed as a reference in an argument
     * list of a component dependencies configuration. Only strings are
     * affected. Arrays are recursively walked.
     * 
     * @see Lortnoc_Container::dereference
     * @param mixed $object
     * @return mixed
     */
    public static function escape($object) {
        if (is_string($object)) {
            $specialchar = $object[0];
            if ($specialchar == '@' || $object[0] == '%') {
                $object = $specialchar . $object;
            }
        } else if (is_array($object)) {
            $object = array_map(array(__CLASS__, 'escape'), $object);
        }
        return $object;
    }

    /**
     * Lortnoc_Container constructor.
     * 
     * @param array(string => array(string => mixed)) $components
     * @param array(string => mixed) $params
     */
    public function __construct(array $components = array(), array $params = array()) {
        $this->components = $components;
        $this->params = $params;
        $this->instances = array();
    }

    /**
     * Merges current component dependencies and configuration parameters
     * with given associative arrays.
     * 
     * @param array(string => array(string => mixed)) $components
     * @param array(string => mixed) $params
     * @return Lortnoc_Container
     */
    public function merge(array $components = array(), array $params = array()) {
        $this->components = array_merge($this->components, $components);
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * Replaces references to container components or configuration parameters
     * by their corresponding values. Only strings are affected. Arrays are
     * recursively walked.
     * 
     * @param mixed $object
     * @return mixed
     */
    public function dereference($object) {
        if (is_string($object)) {
            $specialchar = $object[0];
            if ($specialchar == '@') {
                $name = substr($object, 1);
                return ($name[0] == '@' ? $name : $this->getInstance($name));
            } else if ($specialchar == '%') {
                $key = substr($object, 1);
                return ($key[0] == '%' ? $key : $this->getConfigParam($key));
            }
        } else if (is_array($object)) {
            return array_map(array($this, 'dereference'), $object);
        }
        return $object;
    }
    
    /**
     * Gets the configuration parameters map.
     * 
     * @return array(string => mixed)
     */
    public function getConfigParams() {
        return $this->params;
    }

    /**
     * Gets a configuration parameter by key.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfigParam($key, $default = NULL) {
        return (array_key_exists($key, $this->params)
                ? $this->params[$key] : $default);
    }

    /**
     * Sets a configuration parameter by key.
     * 
     * @param string $key
     * @param mixed $value
     * @return Lortnoc_Container
     */
    public function setConfigParam($key, $value) {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Gets the component dependencies configuration map used by the container.
     * 
     * @return array(string => array(string => mixed))
     */
    public function getComponents() {
        return $this->components;
    }

    /**
     * Gets a container component by name.
     * 
     * @param string $name
     * @throws Lortnoc_Exception
     * @return mixed
     */
    public function getComponent($name) {
        // Reset solving array (used to detect dependency loops in
        // indirect recursive calls to getInstance)        
        $this->solving = array();
        return $this->getInstance($name);
    }

    /**
     * Sets a container component by name. It is cached as a singleton.
     * 
     * @param string $name
     * @param mixed $instance
     */
    public function setComponent($name, $instance) {
        $this->instances[$name] = $instance;
        return $this;
    }

    /**
     * Predicate method that indicates if the container has a component
     * identified by the given name (that is, if an instance is cached
     * or can be created).
     * 
     * @param string $name
     * @return bool
     */
    public function hasComponent($name) {
        return array_key_exists($name, $this->components)
                || array_key_exists($name, $this->instances);
    }

    /**
     * Removes a component by name from the container cache so that a new
     * instance is created the next time it is retrieved.
     * 
     * @param string $name
     */
    public function clearComponent($name) {
        unset($this->instances[$name]);
        return $this;
    }

    /**
     * Overloads the object member getter to mimic
     * Lortnoc_Container::getComponent.
     * 
     * @see Lortnoc_Container::getComponent
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->getComponent($name);
    }

    /**
     * Overloads the object member setter to mimic
     * Lortnoc_Container::setComponent.
     * 
     * @see Lortnoc_Container::setComponent
     * @param string $name
     * @param mixed $instance
     */
    public function __set($name, $instance) {
        $this->setComponent($name, $instance);
    }

    /**
     * Overloads behaviour of calls to isset on object members to mimic
     * Lortnoc_Container::hasComponent.
     * 
     * @see Lortnoc_Container::hasComponent
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        return $this->hasComponent($name);
    }

    /**
     * Overloads behaviour of calls to unset on object members to mimic
     * Lortnoc_Container::clearComponent.
     * 
     * @see Lortnoc_Container::clearComponent
     * @param string $name
     */
    public function __unset($name) {
        $this->clearComponent($name);
    }

    /**
     * This protected method is the same as Lortnoc_Container::getComponent
     * but can be recursively called to detect dependency loops.
     * 
     * @param string $name
     * @throws Lortnoc_Exception
     * @return mixed
     */
    protected function getInstance($name) {
        
        if (array_key_exists($name, $this->instances)) {
            return $this->instances[$name];
        }
        if (!array_key_exists($name, $this->components)) {
            throw new Lortnoc_Exception_NotFound(
                    'Component not found in container: ' . self::repr($name));
        }
        
        $loop = in_array($name, $this->solving);
        $this->solving[] = $name;
        if ($loop) {
            throw new Lortnoc_Exception_DependencyLoop(
                    'Dependency loop detected: '
                    . implode(' -> ', $this->solving));
        }
        
        $conf = $this->getComponentConfiguration($name);
        $instance = NULL;
        if (array_key_exists('alias', $conf)) {
            $instance = $this->getInstance($conf['alias']);
        } else {
            $instance = $this->create($name);
            $scope = (array_key_exists('scope', $conf)
                    ? $conf['scope'] : 'singleton');
            if ($scope != 'prototype') {
                $this->instances[$name] = $instance;
            }
        }

        array_pop($this->solving);
        return $instance;
    }
    
    /**
     * Returns a component configuration by name.
     * 
     * @param string $name
     * @throws Lortnoc_Exception_ConfigError
     * @return array(string => mixed)
     */
    protected function getComponentConfiguration($name) {
        $conf = $this->components[$name];
        if (is_null($conf)) {
        	$conf = array();
        } else if (is_string($conf)) {
        	$conf = array('class' => $conf);
        } else if (!is_array($conf)) {
            throw new Lortnoc_Exception_ConfigError('Component configuration '
                    . 'is not an array: ' . self::repr($conf));
        }
        return $conf;
    }

    /**
     * Creates a new component instance by name, either by calling its factory
     * method or function or its class constructor with configured arguments.
     * 
     * References to other container components or configuration parameters
     * are replaced by their corresponding values in the list of arguments.
     * 
     * After creating the new component instance, properties are set and
     * methods are called according to component configuration.
     * 
     * @param string $name
     * @throws Lortnoc_Exception
     * @return mixed
     */
    protected function create($name) {
        
        $instance = NULL;
        $conf = $this->getComponentConfiguration($name);
        $arguments = (array_key_exists('arguments', $conf)
                ? $conf['arguments'] : array());
        
        if (array_key_exists('factory', $conf)) {
            $factory = $conf['factory'];
            $instance = $this->createFromFactory($factory, $arguments);
        } else {
            $class = (array_key_exists('class', $conf)
                    ? $conf['class'] : $name);
            $instance = $this->createFromClass($class, $arguments);
        }
        
        $properties = (array_key_exists('properties', $conf)
                ? $this->dereference($conf['properties']) : array());
        foreach ($properties as $key => $value) {
            $instance->$key = $value;
        }
        
        $methods = (array_key_exists('methods', $conf)
                ? $conf['methods'] : array());
        foreach ($methods as $conf) {
            $this->callMethod($instance, $conf);
        }

        return $instance;
    }
    
    /**
     * Creates a new component by calling a factory function or method.
     * 
     * References to other container components or configuration parameters
     * are replaced by their corresponding values in the list of arguments.
     * 
     * @param callable $factory
     * @param array $arguments
     * @throws Lortnoc_Exception
     * @return mixed
     */
    protected function createFromFactory($factory, array $arguments) {
        if (!is_callable($factory)) {
            throw new Lortnoc_Exception_ConfigError(
                    'Factory is not callable: ' . self::repr($factory));
        }
        $args = $this->dereference($arguments);
        return call_user_func_array($factory, $args);
    }
    
    /**
     * Creates a new component by calling the class constructor.
     * 
     * References to other container components or configuration parameters
     * are replaced by their corresponding values in the list of arguments.
     * 
     * @param string $class
     * @param array $arguments
     * @throws Lortnoc_Exception
     * @return mixed
     */
    protected function createFromClass($class, array $arguments) {
        if (!class_exists($class)) {
            throw new Lortnoc_Exception_ReflectionError(
                    'Class not found: ' . self::repr($class));
        }
        try {
            $reflection = new ReflectionClass($class);
            $args  = $this->dereference($arguments);
            return (is_null($reflection->getConstructor())
                    ? $reflection->newInstance()
                    : $reflection->newInstanceArgs($args));
        } catch (ReflectionException $e) {
            throw new Lortnoc_Exception_ReflectionError(
                    'Cannot create instance by reflection: '
                    . $e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Calls a method on given instance according to given configuration.
     * 
     * @param mixed $instance
     * @param array $conf
     * @throws Lortnoc_Exception
     * @return mixed
     */
    protected function callMethod($instance, array $conf) {
        if (!array_key_exists('method', $conf)) {
            throw new Lortnoc_Exception_ConfigError(
                    'Missing method in: ' . self::repr($conf));
        }
        try {
            $method = new ReflectionMethod($instance, $conf['method']);
            $args = (array_key_exists('arguments', $conf)
                    ? $this->dereference($conf['arguments']) : array());
            return $method->invokeArgs($instance, $args);
        } catch (ReflectionException $e) {
            throw new Lortnoc_Exception_ReflectionError(
                    'Cannot call method by reflection: '
                    . $e->getMessage(), $e->getCode(), $e);
        } 
    }

}
