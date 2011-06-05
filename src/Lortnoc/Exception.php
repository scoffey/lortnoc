<?php

/**
 * Base class for exceptions in Lortnoc library.
 * 
 * @author scoffey
 *
 */
class Lortnoc_Exception extends Exception
{
}

/**
 * Exception thrown when configuration is invalid (either in syntax or
 * semantically).
 *
 */
class Lortnoc_Exception_ConfigError extends Lortnoc_Exception
{
}

/**
 * Exception thrown when a component is not found in a DI container.
 *
 */
class Lortnoc_Exception_NotFound extends Lortnoc_Exception
{
}

/**
 * Exception thrown when a components cannot be instantiated by reflection
 * or when a method or function cannot be called by reflection.
 *
 */
class Lortnoc_Exception_ReflectionError extends Lortnoc_Exception
{
}

/**
 * Exception thrown when a dependency loop is detected between components.
 *
 */
class Lortnoc_Exception_DependencyLoop extends Lortnoc_Exception
{
}
