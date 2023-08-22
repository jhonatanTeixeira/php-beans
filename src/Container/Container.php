<?php

namespace PhpBeans\Container;

use Closure;
use IteratorAggregate;
use Metadata\MetadataFactory;
use PhpBeans\Annotation\Configuration;
use PhpBeans\Annotation\Imports;
use PhpBeans\Annotation\Injects;
use PhpBeans\Event\AfterInstanceBeanEvent;
use PhpBeans\Event\BeforeInstanceBeanEvent;
use PhpBeans\Metadata\ClassMetadata;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Traversable;
use Vox\Log\Logger;
use Vox\Metadata\FunctionMetadata;
use Vox\Metadata\MethodMetadata;
use Vox\Metadata\ParamMetadata;
use Serializable;

class Container implements ContainerInterface, ContainerWriterInterface, IteratorAggregate, Serializable
{
    private $beans = [];

    /**
     * @var ClassMetadata[]
     */
    private array $metadatas = [];

    private array $statuses = [];

    /**
     * @var callable[]
     */
    private array $factories = [];

    /**
     * @var MethodMetadata[]
     */
    private array $methodMetadatas = [];

    private EventDispatcherInterface $eventDispatcher;

    public bool $debug;

    private LoggerInterface $logger;

    /**
     * @var ClassMetadata[]
     */
    private array $interfaces = [];

    private const WAITING = 1;
    private const INSTANTIATING = 1;
    private const INSTANTIATED = 2;

    public function __construct(EventDispatcherInterface $eventDispatcher, $debug = false) {
        $this->eventDispatcher = $eventDispatcher;
        $this->beans[get_class($this)] = $this;
        $this->debug = $debug;
        $this->logger = Logger::getLogger(__CLASS__);
    }

    public function get($id) {
        if ($this->isPath($id)) {
            return $this->parsePath($id);
        }

        if (!$this->has($id)) {
            throw new NotFoundContainerException($id);
        }

        if (!isset($this->beans[$id])) {
            if (isset($this->factories[$id])) {
                $this->beans[$id] = $this->newInstanceFromFactory($id);
            } elseif (isset($this->methodMetadatas[$id])) {
                $this->beans[$id] = $this->newInstanceFromMethodMatadata($id);
            } else {
                $this->beans[$id] = $this->newInstance($id);
            }

            if (!isset($this->metadatas[$id])) {
                $this->metadatas[$id] = $this->getMetadataFactory()->getMetadataForClass(get_class($this->beans[$id]));
            }

            if ($this->beans[$id] instanceof ContainerAwareInterface) {
                $this->beans[$id]->setContainer($this);
            }
        }

        return $this->beans[$id];
    }

    protected function newInstance(string $id) {
        $this->eventDispatcher->dispatch(new BeforeInstanceBeanEvent($this->getMetadata($id), $id));

        $this->statuses[$id] = self::INSTANTIATING;
        $metadata = $this->metadatas[$id];

        try {
            $constructorParams = $this->instantiateDependencies($metadata->getConstructorParams(), $id);
        } catch (NotFoundContainerException $e) {
            throw new ContainerException("Dependency for {$metadata->name} not found", 0, $e);
        }

        $instance = $this->getMetadata($id)->getReflection()->newInstanceArgs($constructorParams);
        $this->statuses[$id] = self::INSTANTIATED;
        $this->eventDispatcher->dispatch(new AfterInstanceBeanEvent($instance, $id));

        return $instance;
    }

    protected function newInstanceFromFactory($id) {
        $this->statuses[$id] = self::INSTANTIATING;

        $factory = Closure::fromCallable($this->factories[$id]);
        $params = (new FunctionMetadata($factory))->params;

        try {
            $deps = $this->instantiateDependencies($params, $id);
        } catch (NotFoundContainerException $e) {
            throw new ContainerException("Dependency for {$id} not found", 0, $e);
        }

        $instance = $factory(...$deps);

        $this->statuses[$id] = self::INSTANTIATED;
        $this->eventDispatcher->dispatch(new AfterInstanceBeanEvent($instance, $id));

        return $instance;
    }

    protected function newInstanceFromMethodMatadata($id) {
        $this->statuses[$id] = self::INSTANTIATING;

        /* @var $factory MethodMetadata */
        $factory = $this->methodMetadatas[$id];

        try {
            $deps = $this->instantiateDependencies($factory->params, $id);
        } catch (NotFoundContainerException $e) {
            throw new ContainerException("Dependency for {$factory->class}::{$factory->name} not found", 0, $e);
        }

        $instance = $factory->invoke($this->get($factory->class), $deps);

        $this->statuses[$id] = self::INSTANTIATED;
        $this->eventDispatcher->dispatch(new AfterInstanceBeanEvent($instance, $id));

        return $instance;
    }

    private function getInjects(\ReflectionParameter $param) {
        if (version_compare(phpversion(), '8.0.0', '>=')) {
            $injects = $param->getAttributes(Injects::class)[0] ?? null;

            return $injects?->newInstance()->beanId;
        }

        return null;
    }

    private function isPath(string $path): bool {
        return (bool) strpos($path, '.');
    }

    private function parsePath(string $path, $currentData = null) {
        $props = explode('.', $path);
        $prop = array_shift($props);

        if (!$currentData) {
            $currentData = $this->get($prop);

            if (empty($props)) {
                return $currentData;
            }

            $prop = array_shift($props);
        }

        if (is_array($currentData)) {
            $currentData = $currentData[$prop];
        }

        if (is_object($currentData)) {
            $currentData = $currentData->$prop;
        }

        if (empty($props)) {
            return $currentData;
        }

        return $this->parsePath(implode('.', $props), $currentData);
    }

    /**
     * @param ParamMetadata[] $params
     * @param string $id
     */
    private function instantiateDependencies(iterable $params, string $id) {
        $constructorParams = [];

        foreach ($params as $param) {
            $dependsOn = null;

            if ($param->type && $this->has($param->type)) {
                $dependsOn = $param->type;
            } elseif ($this->has($param->name)) {
                $dependsOn = $param->name;
            } elseif ($beanId = $this->getInjects($param->reflection)) {
                $dependsOn = $beanId;
            } elseif ($param->reflection->isOptional()) {
                continue;
            } else {
                throw new NotFoundContainerException($param->name, $param->type);
            }

            if ($this->hasBean($dependsOn)) {
                $constructorParams[] = $this->beans[$dependsOn];
                continue;
            }

            if (!$this->isScalar($dependsOn) && $this->isInstantianting($dependsOn)) {
                throw new CircularReferenceException($dependsOn, $id);
            }

            $constructorParams[] = $this->get($dependsOn);
        }

        return $constructorParams;
    }

    public function isScalar(string $id) {
        return isset($this->beans[$id]) && is_scalar($this->beans[$id]);
    }

    public function has($id): bool {
        return isset($this->beans[$id])
            || isset($this->metadatas[$id])
            || isset($this->factories[$id])
            || isset($this->methodMetadatas[$id]);
    }

    public function setBean(string $id, $bean) {
        $this->beans[$id] = $bean;

        return $this;
    }

    public function setFactory(string $id, callable $factory) {
        $this->factories[$id] = $factory;

        return $this;
    }

    public function set(string $id, $value): Container {
        if ($value instanceof ClassMetadata && $value->getReflection()->isInterface()) {
            $this->interfaces[$id] = $value;
        } elseif ($value instanceof ClassMetadata) {
            $this->setComponent($id, $value);
        } elseif($value instanceof MethodMetadata) {
            $this->methodMetadatas[$id] = $value;
        } else {
            $this->setBean($id, $value);
        }

        return $this;
    }

    public function getMetadataFactory(): MetadataFactory {
        return $this->get(MetadataFactory::class);
    }

    public function setComponent($id, ClassMetadata $metadata, array $components = []): Container {
        if (empty($components)) {
            $components = $metadata->getAnnotations();
        }

        foreach ($components as $component) {
            $componentMetadata = $this->getMetadataFactory()->getMetadataForClass(get_class($component));

            if ($componentMetadata->hasAnnotation(Imports::class)) {
                foreach ($componentMetadata->getAnnotation(Imports::class)->configurations as $configuration) {
                    $configMetadata = $this->get(MetadataFactory::class)->getMetadataForClass($configuration);
                    $configMetadata->annotations[Configuration::class] = new Configuration();
                    $this->set($configMetadata->name, $configMetadata);
                }
            }
        }

        $this->metadatas[$id] = $metadata;

        return $this;
    }

    public function hasMetadata(string $id) {
        return isset($this->metadatas[$id]);
    }

    public function hasBean(string $id) {
        return isset($this->beans[$id]);
    }

    public function hasFactory(string $id) {
        return isset($this->methodMetadatas[$id]);
    }

    private function isInstantianting(string $name) {
        return isset($this->statuses[$name]) && $this->statuses[$name] === self::INSTANTIATING;
    }

    public function getMetadata(string $id): ClassMetadata {
        return $this->metadatas[$id] ?? $this->getMetadataFactory()->getMetadataForClass($id);
    }

    public function getBean(string $id) {
        return $this->beans[$id];
    }

    public function getFactory(string $id): ?MethodMetadata {
        return $this->methodMetadatas[$id] ?? null;
    }

    /**
     * @return ClassMetadata[]
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    public function hasInterface($id): bool
    {
        return isset($this->interfaces[$id]);
    }

    public function __get($id) {
        return $this->get($id);
    }

    public function __set($id, $value) {
        $this->set($id, $value);
    }

    public function getIterator(): Traversable {
        $ids = array_merge(
            array_keys($this->beans),
            array_keys($this->metadatas),
            array_keys($this->methodMetadatas),
            array_keys($this->factories)
        );

        foreach ($ids as $id) {
            yield $id => $this->get($id);
        }
    }

    /**
     * @param string $component
     *
     * @return ClassMetadata[]
     */
    public function getMetadataByComponent(string $component) {
        $classMetadatas = array_filter(
            $this->metadatas,
            fn(ClassMetadata $metadata) => $metadata->hasAnnotation($component)
                || $metadata->isInstanceOf($component)
        );

        foreach ($this->methodMetadatas as $id => $methodMetadata) {
            $returnType = $methodMetadata->getReturnType();

            if ($returnType && class_exists($returnType) && is_a($returnType, $component, true)) {
                $instance = $this->newInstanceFromMethodMatadata($id);
                $this->metadatas[$id] = $this->getMetadataFactory()->getMetadataForClass(get_class($instance));
                $classMetadatas[$id] = $this->metadatas[$id];
            }
        }

        return $classMetadatas;
    }

    public function getBeansByComponent(string $component) {
        return array_map(
            fn(string $id) => $this->get($id),
            array_keys($this->getMetadataByComponent($component))
        );
    }

    public function guessBeanName($bean) {
        if ($bean instanceof ClassMetadata) {
            return $bean->name;
        }

        if ($bean instanceof MethodMetadata) {
            return $bean->class->name;
        }
    }

    private function isFresh(string $id): bool {
        if (!$this->debug) {
            return true;
        }

        return $this->hasMetadata($id) && $this->getMetadata($id)->isFresh();
    }

    public function serialize()
    {
        return serialize([
            $this->metadatas,
            array_filter($this->beans, fn($i) => !$i instanceof self),
            $this->methodMetadatas,
            $this->interfaces,
            $this->eventDispatcher,
            $this->debug,
            $this->logger,
        ]);
    }

    public function unserialize($data)
    {
        [
            $this->metadatas,
            $this->beans,
            $this->methodMetadatas,
            $this->interfaces,
            $this->eventDispatcher,
            $this->debug,
            $this->logger,
        ] = unserialize($data);

        $this->beans[self::class] = $this;
        $this->beans[ContainerInterface::class] = $this;
    }
}
