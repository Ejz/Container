<?php

namespace Ejz;

use ReflectionClass;
use ReflectionFunction;
use InvalidArgumentException;
use Ejz\Exceptions\ContainerException;
use Ejz\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{
    /* array */
    private $resolved = [];

    /* array */
    private $definitions = [];

    /* array */
    private $parameters = [];

    /**
     * @param array $definitions (optional)
     */
    public function __construct(array $definitions = [])
    {
        $this->resolved[ContainerInterface::class] = $this;
        $this->setDefinitions($definitions);
    }

    /**
     * @param string $class
     * @param array  $arguments         (optional)
     * @param bool   $ignore_definition (optional)
     *
     * @throws ContainerException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function get($class, $arguments = [], bool $ignore_definition = false)
    {
        if (!is_string($class)) {
            throw new InvalidArgumentException(sprintf(
                'The $class parameter accepts arguments of type string, %s given!',
                is_object($class) ? get_class($class) : gettype($class)
            ));
        }
        $is_assoc = count(array_filter(array_keys($arguments), 'is_string')) === count($arguments);
        if (!is_array($arguments) || !$is_assoc) {
            throw new InvalidArgumentException(sprintf(
                'The $arguments parameter accepts arguments of type array (associative), %s given!',
                is_object($arguments) ? get_class($arguments) : gettype($arguments)
            ));
        }
        $parameters = $this->getParameters($class, $ignore_definition);
        $parameters = array_map(function ($parameter) {
            return $parameter->getName();
        }, $parameters);
        $arguments = array_intersect_key($arguments, array_flip($parameters));
        ksort($arguments);
        $key = $class . ($arguments ? '_' . md5(serialize($arguments)) : '');
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }
        $value = $this->make($class, $arguments, $ignore_definition);
        $this->resolved[$key] = $value;
        return $value;
    }

    /**
     * @param array $definitions
     */
    public function setDefinitions(array $definitions)
    {
        $this->definitions = $definitions;
        $this->definitions[ContainerInterface::class] = self::class;
    }

    /**
     * @param string $class
     * @param bool   $ignore_definition (optional)
     *
     * @throws NotFoundException
     *
     * @return Closure|string
     */
    private function getDefinition(string $class, bool $ignore_definition = false)
    {
        while (!$ignore_definition && is_string($class) && isset($this->definitions[$class])) {
            $class = $this->definitions[$class];
        }
        if (is_string($class) && !class_exists($class)) {
            throw new NotFoundException(sprintf(
                'Class %s not found! May be you provided string callable?',
                $class
            ));
        }
        return $class;
    }

    /**
     * @param string $class
     * @param bool   $ignore_definition (optional)
     * 
     * @throws NotFoundException
     *
     * @return array
     */
    private function getParameters(string $class, bool $ignore_definition = false): array
    {
        $definition = $this->getDefinition($class, $ignore_definition);
        $key = $class . (is_callable($definition) ? '_Closure' : '');
        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }
        if (is_callable($definition)) {
            $function = new ReflectionFunction($definition);
        } else {
            $function = (new ReflectionClass($definition))->getConstructor();
        }
        $parameters = $function ? $function->getParameters() : [];
        $this->parameters[$key] = $parameters;
        return $this->parameters[$key];
    }

    /**
     * @param string $class
     * @param array  $arguments (optional)
     *
     * @throws ContainerException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function make($class, $arguments = [], bool $ignore_definition = false)
    {
        if (!is_string($class)) {
            throw new InvalidArgumentException(sprintf(
                'The $class parameter accepts arguments of type string, %s given!',
                is_object($class) ? get_class($class) : gettype($class)
            ));
        }
        $is_assoc = count(array_filter(array_keys($arguments), 'is_string')) === count($arguments);
        if (!is_array($arguments) || !$is_assoc) {
            throw new InvalidArgumentException(sprintf(
                'The $arguments parameter accepts arguments of type array (associative), %s given!',
                is_object($arguments) ? get_class($arguments) : gettype($arguments)
            ));
        }
        $args = [];
        $parameters = $this->getParameters($class, $ignore_definition);
        $optional = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            $type_string = (string) $type;
            $name = $parameter->getName();
            if (array_key_exists($name, $arguments)) {
                $argument = $arguments[$name];
                if (is_object($argument)) {
                    $argument_type = (string) get_class($argument);
                } else {
                    $argument_type = gettype($argument);
                    $_ = ['integer' => 'int', 'boolean' => 'bool'];
                    $argument_type = $_[$argument_type] ?? $argument_type;
                }
                if (is_object($argument) && in_array($type_string, class_implements($argument))) {
                } elseif ($type && $type->allowsNull() && $argument_type === 'NULL') {
                } elseif ($type_string && $argument_type !== $type_string) {
                    throw new InvalidArgumentException(sprintf(
                        'Error initiating %s. The $%s parameter accepts arguments of type %s, %s given!',
                        $class,
                        $name,
                        $type_string,
                        $argument_type
                    ));
                }
                if ($optional) {
                    $args = array_merge($args, $optional);
                    $optional = [];
                }
                $args[] = $argument;
                continue;
            }
            if ($parameter->isOptional()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $optional[] = $parameter->getDefaultValue();
                }
                continue;
            }
            if ($type->isBuiltin()) {
                throw new ContainerException(sprintf(
                    'Error instantiating %s. The $%s parameter is not provided!',
                    $class,
                    $name
                ));
            }
            if ($optional) {
                $args = array_merge($args, $optional);
                $optional = [];
            }
            $args[] = $this->get($type_string);
        }
        $definition = $this->getDefinition($class, $ignore_definition);
        if (is_callable($definition)) {
            $object = $definition->call($this, ...$args);
        } else {
            $object = new $definition(...$args);
        }
        if (isset($this->definitions[$class]) && !$ignore_definition && !$object instanceof $class) {
            throw new ContainerException(sprintf(
                'Error instantiating %s. Got instance of %s!',
                $class,
                get_class($object)
            ));
        }
        return $object;
    }

    /**
     * @todo
     */
    public function has($class, $arguments = []): bool
    {
        return true;
    }
}
