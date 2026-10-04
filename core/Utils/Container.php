<?php 

namespace Zap\Core\Utils;

use Exception;
use ReflectionClass;

class Container {

    private static ?Container $instance = null;
    private array $bindings = [];
    private array $instances = [];

    private function __construct(){}
    private function __clone(){}

    public static function getInstance(): Container
    {
        if(self::$instance === null){
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    public function bind(string $key, mixed $resolver): void
    {
        $this->bindings[$key] = $resolver;
        unset($this->instances[$key]);
    }

    public function singleton(string $key, mixed $resolver): void
    {
        $this->bind($key, $resolver);
        $this->bindings[$key] = function ($container, array $parameters = []) use ($resolver, $key){
            if(!isset($this->instances[$key])){
                $this->instances[$key] = is_callable($resolver)
                    ? $resolver($container, $parameters)
                    : $container->build($resolver, $parameters);                
            }
            return $this->instances[$key];
        };
    }

    /**
     * @template T of object
     * @param class-string<T>|string $key
     * @param array $parameters
     * @return T
     */
    public function make(string $key, array $parameters = []){
        if(isset($this->bindings[$key])){
            $resolver = $this->bindings[$key];
            return is_callable($resolver)
                ? $resolver($this, $parameters)
                : $this->build($resolver, $parameters);
        }
        return $this->build($key, $parameters);
    }

    private function build(string $concrete, array $parameters=[]){
        if(!class_exists($concrete)){
            throw new Exception("Target class [{$concrete}] does not exist");
        }

        $reflector = new ReflectionClass($concrete);
        if(!$reflector->isInstantiable()){
            throw new Exception("Target [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if(is_null($constructor)){
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = [];

        foreach($dependencies as $parameter){
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();

            if(array_key_exists($paramName, $parameters)){
                $instances[] = $parameters[$paramName];
            }

            if($paramType && !$paramType->isBuiltin()){
                $instances[] = $this->make($paramType->getName());
                continue;
            }

            if($parameter->isDefaultValueAvailable()){
                $instances[] = $parameter->getDefaultValue();
                continue;
            }

            throw new Exception("Cannot resolve primitive parameter \${$paramName} for class {$concrete}");
        }

        return $reflector->newInstanceArgs($instances);
    }
}