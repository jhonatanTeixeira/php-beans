<?php

namespace PhpBeans\Container;

use ArrayIterator;
use Closure;
use IteratorAggregate;
use PhpBeans\Event\AfterInstanceBeanEvent;
use PhpBeans\Event\BeforeInstanceBeanEvent;
use PhpBeans\Metadata\ClassMetadata;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionFunction;
use ReflectionParameter;
use Traversable;
use Vox\Metadata\FunctionMetadata;
use Vox\Metadata\MethodMetadata;
use Vox\Metadata\ParamMetadata;

class Container implements ContainerInterface, ContainerWriterInterface, IteratorAggregate
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

    private CacheInterface $cache;
    
    private const WAITING = 1;
    private const INSTANTIATING = 1;
    private const INSTANTIATED = 2;
    
    public function __construct(EventDispatcherInterface $eventDispatcher, CacheInterface $cache) {
        $this->eventDispatcher = $eventDispatcher;
        $this->cache = $cache;
        $this->beans[ContainerInterface::class] = $this;
        $this->beans[get_class($this)] = $this;
    }

    public function get($id) {
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
        $constructorParams = $this->instantiateDependencies($metadata->getConstructorParams(), $id);
        
        $instance = $this->getMetadata($id)->getReflection()->newInstanceArgs($constructorParams);
        $this->statuses[$id] = self::INSTANTIATED;
        $this->eventDispatcher->dispatch(new AfterInstanceBeanEvent($instance, $id));
        
        return $instance;
    }
    
    protected function newInstanceFromFactory($id) {
        $this->statuses[$id] = self::INSTANTIATING;

        $factory = Closure::fromCallable($this->factories[$id]);
        $params = (new FunctionMetadata($factory))->params;

        $deps = $this->instantiateDependencies($params, $id);

        $instance = $factory(...$deps);

        $this->statuses[$id] = self::INSTANTIATED;
        $this->eventDispatcher->dispatch(new AfterInstanceBeanEvent($instance, $id));
        
        return $instance;
    }
    
    protected function newInstanceFromMethodMatadata($id) {
        $this->statuses[$id] = self::INSTANTIATING;
        
        /* @var $factory MethodMetadata */
        $factory = $this->methodMetadatas[$id];

        $deps = $this->instantiateDependencies($factory->params, $id);

        $instance = $factory->invoke($this->get($factory->class), $deps);

        $this->statuses[$id] = self::INSTANTIATED;
        $this->eventDispatcher->dispatch(new AfterInstanceBeanEvent($instance, $id));
        
        return $instance;
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
            } else {
                throw new NotFoundContainerException($param->name, $param->type);
            }
            
            if (!$this->isScalar($dependsOn) && $this->isInstantianting($dependsOn)) {
                throw new CircularReferenceException($dependsOn, $id);
            }
            
            $constructorParams[] = $this->isScalar($dependsOn)
                ? $this->get($dependsOn)
                : $this->newInstance($dependsOn);
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
    
    public function set(string $id, $value): Container {
        if ($value instanceof ClassMetadata) {
            $this->metadatas[$id] = $value;
        } elseif($value instanceof MethodMetadata) {
            $this->methodMetadatas[$id] = $value;
        } elseif (is_callable($value)) {
            $this->factories[$id] = $value;
        } else {
            $this->beans[$id] = $value;
        }
        
        return $this;
    }
    
    public function hasMetadata(string $id) {
        return isset($this->metadatas[$id]);
    }
    
    public function hasBean(string $id) {
        return isset($this->beans[$id]);
    }
    
    public function hasFactory(string $id) {
        return isset($this->factories[$id]);
    }
    
    private function isInstantianting(string $name) {
        return isset($this->statuses[$name]) && $this->statuses[$name] === self::INSTANTIATING;
    }
    
    public function getMetadata(string $id): ClassMetadata {
        return $this->metadatas[$id];
    }
    
    public function getBean(string $id) {
        return $this->beans[$id];
    }
    
    public function getFactory(string $id): callable {
        return $this->factories[$id];
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
        return array_filter(
            $this->metadatas, 
            fn(ClassMetadata $metadata) => $metadata->hasAnnotation($component) 
                || $metadata->isInstanceOf($component)
        );
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
        return $this->hasMetadata($id) && $this->getMetadata($id)->isFresh();
    }

    private function addToCache(string $id) {
        try {
            $this->cache->set("container.bean.$id", $this->get($id));
        } catch (\Throwable $e) {
            // catch all
        }
    }

    private function getFromCache(string $id) {
        if ($this->isFresh($id)) {
            return $this->cache->get($id);
        }
    }
}
