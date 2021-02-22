<?php

namespace PhpBeans\Bean;

use Metadata\MetadataFactory;
use PhpBeans\Annotation\Bean;
use PhpBeans\Annotation\Component;
use PhpBeans\Annotation\Configuration;
use PhpBeans\Annotation\Imports;
use PhpBeans\Annotation\PostBeanProcessor;
use PhpBeans\Annotation\Value;
use PhpBeans\Container\Container;
use PhpBeans\Metadata\ClassMetadata;
use PhpBeans\Processor\AbstractStereotypeProcessor;
use PhpBeans\Scanner\ComponentScanner;

class BeanRegisterer 
{
    use ValueProcessorTrait;

    /**
     * @var string[]
     */
    private array $namespaces = [];
    
    /**
     * @var string[]
     */
    private array $componentClasses = [];
    
    private ComponentScanner $componentScanner;
    
    private Container $container;

    private MetadataFactory $metadataFactory;
    
    public function __construct(ComponentScanner $componentScanner, Container $container,
                                array $namespaces = [], array $componentClasses = [],
                                MetadataFactory $metadataFactory) {
        $this->componentScanner = $componentScanner;
        $this->container = $container;
        
        $this->namespaces = array_merge(['PhpBeans\\'], $namespaces);
        $this->componentClasses = array_merge(
            [
                Component::class,
                Configuration::class,
                PostBeanProcessor::class,
                AbstractStereotypeProcessor::class,
            ],
            $componentClasses
        );

        $this->metadataFactory = $metadataFactory;
    }

    
    public function addNamespace(string $namespace) {
        $this->namespaces[] = $namespace;

        return $this;
    }
    
    public function addComponentClass(string $class) {
        $this->componentClasses[] = $class;

        return $this;
    }
    
    public function registerComponents() {
        $configurators = $this->componentScanner
            ->scanComponentsFor(BeanRegistererConfiguratorInterface::class, ...$this->namespaces);

        /* @var $configurator BeanRegistererConfiguratorInterface */
        foreach ($configurators as $configurator) {
            $configurator->configure($this);
        }

        foreach ($this->componentClasses as $componentClass) {
            $namespaces = $this->namespaces;
            
            $components = $this->componentScanner
                ->scanComponentsFor($componentClass, ...$namespaces);

            foreach ($components as $metadata) {
                $this->registerComponent($metadata);
                /* @var $componentMetadata ClassMetadata */
                $componentMetadata = $this->metadataFactory->getMetadataForClass($componentClass);

                if ($componentMetadata->hasAnnotation(Imports::class)) {
                    foreach ($componentMetadata->getAnnotation(Imports::class)->configurations as $configuration) {
                        $configMetadata = $this->metadataFactory->getMetadataForClass($configuration);
                        $configMetadata->annotations[Configuration::class] = new Configuration();
                        $this->registerComponent($configMetadata);
                    }
                }
            }
        }
    }
    
    public function registerFactories() {
        foreach ($this->container->getMetadataByComponent(Configuration::class) as $cfgid => $config) {
            foreach ($config->getAnnotatedMethods(Bean::class) as $factory) {
                $type = $factory->getReturnType();
                $id = $factory->getAnnotation(Bean::class)->name ?? $type ?? $factory->name;
                $this->container->set($id, $factory);
            }
        }
    }
    
    public function registerComponent(ClassMetadata $metadata) {
        $this->container->set($metadata->name, $metadata);

        return $this;
    }
    
    public function resolveConfigurationValues() {
        foreach ($this->container->getBeansByComponent(Configuration::class) as $configuration) {
            $metadata = $this->metadataFactory->getMetadataForClass(get_class($configuration));

            foreach($metadata->getAnnotatedProperties(Value::class) as $property) {
                $this->process($property, $configuration, $this->container, $property->getAnnotation(Value::class));
            }
        }
    }

    public function registerBeans() {
        $this->registerComponents();
        $this->resolveConfigurationValues();
        $this->registerFactories();
        
        /* @var $processor AbstractStereotypeProcessor */
        foreach ($this->container->getBeansByComponent(AbstractStereotypeProcessor::class) as $processor) {
            $processor->findAndProcess();
        }
    }
}
