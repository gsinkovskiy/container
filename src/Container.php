<?php
declare(strict_types=1);

namespace HazyCastrel\Container;

use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{

    private array $servicesDefinitions = [];

    private array $services = [];

    public function get($id)
    {
        if (!isset($this->services[$id])) {
            $this->services[$id] = $this->createService($id);
        }

        return $this->services[$id];
    }

    public function has($id)
    {
        return isset($this->servicesDefinitions[$id]) || isset($this->services[$id]) || class_exists($id);
    }

    public function register(array $definitions)
    {
        foreach ($definitions as $id => $classNameOrCallable) {
            if ($classNameOrCallable === null) {
                $classNameOrCallable = $id;
            }

            $this->servicesDefinitions[$id] = $classNameOrCallable;
        }
    }

    private function createService(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException('Service "' . $id . '" is not registered.');
        }

        $classNameOrCallable = $this->servicesDefinitions[$id] ?? $id;

        if (is_callable($classNameOrCallable)) {
            return $classNameOrCallable($this);
        }

        $reflector = new \ReflectionClass($classNameOrCallable);
        $constructorReflector = $reflector->getConstructor();
        $arguments = [];

        if ($constructorReflector) {
            foreach ($constructorReflector->getParameters() as $parameter) {
                $arguments[] = $this->get($parameter->getClass()->getName());
            }
        }

        $service = $reflector->newInstanceArgs($arguments);

        foreach ($reflector->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            if (substr($methodName, 0, 3) === 'set') {
                $arguments = [];

                foreach ($method->getParameters() as $parameter) {
                    $arguments[] = $this->get($parameter->getClass()->getName());
                }

                $method->invokeArgs($service, $arguments);
            }
        }

        return $service;
    }

}